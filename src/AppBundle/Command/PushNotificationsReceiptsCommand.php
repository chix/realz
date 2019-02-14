<?php

namespace AppBundle\Command;

use Circle\RestClientBundle\Services\RestClient;
use Circle\RestClientBundle\Exceptions\CurlException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class PushNotificationsReceiptsCommand extends Command
{
    protected static $defaultName = 'app:push-notifications:get-receipt';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RestClient
     */
    protected $restClient;

    /**
     * @var string
     */
    protected $expoBackendUrl;

    public function __construct(RestClient $restClient, LoggerInterface $logger, $expoBackendUrl)
    {
        $this->restClient = $restClient;
        $this->logger = $logger;
        $this->expoBackendUrl = $expoBackendUrl;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Get a push notification receipt')
            ->addArgument('id', InputArgument::REQUIRED, 'Notification ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);

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
            $response = $this->restClient->post($this->expoBackendUrl, $json, $curlOptions);
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
