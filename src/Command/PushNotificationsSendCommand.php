<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Advert;
use App\Entity\PushNotificationToken;
use App\Repository\PushNotificationTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PushNotificationsSendCommand extends Command
{
    protected static $defaultName = 'app:push-notifications:send';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $restClient,
        private LoggerInterface $logger,
        private PushNotificationTokenRepository $tokenRepository,
        private string $expoBackendUrl
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send push notifications.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        // load not yet notified adverts for active tokens into notification messages
        $tokenMap = [];
        $advertMap = [];
        $notifications = [];
        $activeTokens = $this->tokenRepository->getActiveAndEnabled();
        foreach ($activeTokens as $activeToken) {
            $adverts = $this->tokenRepository->getUnnotifiedAdvertsForToken($activeToken);
            foreach ($adverts as $advert) {
                $notification = new \stdClass();
                $notification->channelId = 'new-listing';
                $notification->priority = 'high';
                $notification->ttl = 3600;
                $notification->sound = 'default';
                $notification->vibrate = true;
                $notification->to = $activeToken->getToken();
                $source = $advert->getSource();
                $notification->title = $advert->getTitle().($source ? sprintf(' (%s)', $source->getName()) : '');
                $bodyParts = [];
                if ($advert->getPrice()) {
                    $bodyParts[] = $advert->getPrice().$advert->getCurrency();
                }
                if (null !== $advert->getProperty() && null !== $advert->getProperty()->getLocation()) {
                    $bodyParts[] = $advert->getProperty()->getLocation()->getStreet();
                }
                $notification->body = implode(', ', $bodyParts);
                $notification->data = new \stdClass();
                $notification->data->id = $advert->getId();
                $notification->data->type = $advert->getType()->getCode();
                $notifications[] = $notification;
                $advertMap[$advert->getId()] = $advert;
                $tokenMap[$activeToken->getToken()] = $activeToken;
            }
        }

        $this->logger->debug(count($notifications).' notifications to be sent');

        if (empty($notifications)) {
            return 0;
        }

        // send notifications and mark adverts as already notified (or log delivery error)
        try {
            $response = $this->restClient->request('POST', $this->expoBackendUrl, [
                'json' => $notifications,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);
            $responseJson = $response->toArray();

            // store response meta data
            if (!empty($responseJson['data'])) {
                foreach ($responseJson['data'] as $i => $responseData) {
                    /** @var PushNotificationToken $token */
                    $token = $tokenMap[$notifications[$i]->to];
                    /** @var Advert $advert */
                    $advert = $advertMap[$notifications[$i]->data->id];

                    $this->logger->debug(
                        'Notification sent to '.$token->getToken(),
                        json_decode((string) json_encode($notifications[$i]), true)
                    );

                    if ('error' === $responseData['status']) {
                        $this->logger->debug('Incrementing error count on '.$token->getToken(), $responseData);
                        $token->setErrorCount($token->getErrorCount() + 1);
                    } else {
                        $token->addAdvert($advert);
                    }
                    if ($token->getErrorCount() >= 10) {
                        $this->logger->debug('Deactivating '.$token->getToken());
                        $token->setActive(false);
                    }
                    $token->setLastResponse($responseData);
                    $this->entityManager->persist($token);
                }
                $this->entityManager->flush();
            }
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }

        return 0;
    }
}
