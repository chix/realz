<?php

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Entity\Advert;
use AppBundle\Entity\PushNotificationToken;
use AppBundle\Repository\PushNotificationTokenRepository;
use Circle\RestClientBundle\Services\RestClient;
use Circle\RestClientBundle\Exceptions\CurlException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class PushNotificationsSendCommand extends Command
{
    protected static $defaultName = 'app:push-notifications:send';

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RestClient
     */
    private $restClient;

    /**
     * @var string
     */
    protected $expoBackendUrl;

    /**
     * @var PushNotificationTokenRepository
     */
    protected $tokenRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RestClient $restClient,
        LoggerInterface $logger,
        PushNotificationTokenRepository $tokenRepository,
        string $expoBackendUrl
    ) {
        $this->entityManager = $entityManager;
        $this->restClient = $restClient;
        $this->logger = $logger;
        $this->tokenRepository = $tokenRepository;
        $this->expoBackendUrl = $expoBackendUrl;

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
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);

        // load not yet notified adverts for active tokens into notification messages
        $tokenMap = [];
        $advertMap = [];
        $notifications = [];
        $activeTokens = $this->tokenRepository->getActiveAndEnabled();
        foreach ($activeTokens as $activeToken) { /* @var $activeToken PushNotificationToken */
            $adverts = $this->tokenRepository->getUnnotifiedAdvertsForToken($activeToken);
            foreach ($adverts as $advert) {
                $notification = new \stdClass();
                $notification->channelId = 'new-listing';
                $notification->priority = 'high';
                $notification->ttl = 3600;
                $notification->sound = 'default';
                $notification->vibrate = true;
                $notification->to = $activeToken->getToken();
                $notification->title = $advert->getTitle();
                $bodyParts = [];
                if ($advert->getPrice()) {
                    $bodyParts[] = $advert->getPrice() . $advert->getCurrency();
                }
                if ($advert->getProperty() && $advert->getProperty()->getLocation()) {
                    $bodyParts[] = $advert->getProperty()->getLocation()->getStreet();
                }
                $notification->body = implode(', ', $bodyParts);
                $notification->data = new \stdClass();
                $notification->data->id = $advert->getId();
                $notifications[] = $notification;
                $advertMap[$advert->getId()] = $advert;
                $tokenMap[$activeToken->getToken()] = $activeToken;
            }
        }

        $this->logger->debug(count($notifications) . ' notifications to be sent');

        if (empty($notifications)) {
            return 0;
        }

        //send notifications and mark adverts as already notified (or log delivery error)
        $json = $serializer->encode($notifications, 'json');
        try {
            $curlOptions = [
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Accept-Encoding: gzip, deflate',
                ],
            ];
            $response = $this->restClient->post($this->expoBackendUrl, $json, $curlOptions);
            $isGzip = 0 === mb_strpos($response->getContent(), "\x1f" . "\x8b" . "\x08");
            $responseContent = ($isGzip) ? gzdecode($response->getContent()) : $response->getContent();
            $responseJson = $serializer->decode($responseContent, 'json');

            // store response meta data
            if (!empty($responseJson['data'])) {
                foreach ($responseJson['data'] as $i => $responseData) {
                    /* @var $token PushNotificationToken */
                    $token = $tokenMap[$notifications[$i]->to];
                    /* @var $advert Advert */
                    $advert = $advertMap[$notifications[$i]->data->id];

                    $this->logger->debug('Notification sent to ' . $token->getToken(), json_decode(json_encode($notifications[$i]), true));

                    if ($responseData['status'] === 'error') {
                        $this->logger->debug('Incrementing error count on ' . $token->getToken(), $responseData);
                        $token->setErrorCount($token->getErrorCount() + 1);
                    } else {
                        $token->addAdvert($advert);
                    }
                    if ($token->getErrorCount() >= 10) {
                        $this->logger->debug('Deactivating ' . $token->getToken());
                        $token->setActive(0);
                    }
                    $token->setLastResponse($responseData);
                    $this->entityManager->persist($token);
                }
                $this->entityManager->flush();
            }
        } catch (CurlException $e) {
            $this->logger->debug($e->getMessage());
        }

        return 0;
    }
}
