<?php

namespace AppBundle\Command;

use Circle\RestClientBundle\Services\RestClient;
use Circle\RestClientBundle\Exceptions\CurlException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class PushNotificationsReceiptsCommand extends ContainerAwareCommand
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RestClient
     */
    private $restClient;

    protected function configure()
    {
        $this
            ->setName('app:push-notifications:get-receipt')
            ->setDescription('Get a push notification receipt')
            ->addArgument('id', InputArgument::REQUIRED, 'Notification ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $this->restClient = $this->getContainer()->get('circle.restclient');
        $this->logger = $this->getContainer()->get('monolog.logger.notifications');
        $expoBackendUrl = $this->getContainer()->getParameter('expo.notifications.receiptsBackendUrl');

        $id = $input->getArgument('id');

        $message = new \stdClass();
        $message->ids = [$id];
        $json = $serializer->encode($message, 'json');

        try {
            $curlOptions = [
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Accept-Encoding: gzip, deflate',
                ],
            ];
            $response = $this->restClient->post($expoBackendUrl, $json, $curlOptions);
            $response->getContent();
            if ($response->getStatusCode() >= 400) {
                $output->writeln(sprintf('<error>%s</error>', $response->getContent()));
            } else {
                $output->writeln($response->getContent());
            }
        } catch (CurlException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }
    }
}
