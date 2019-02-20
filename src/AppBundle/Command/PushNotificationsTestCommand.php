<?php

declare(strict_types=1);

namespace AppBundle\Command;

use Circle\RestClientBundle\Services\RestClient;
use Circle\RestClientBundle\Exceptions\CurlException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class PushNotificationsTestCommand extends Command
{
    protected static $defaultName = 'app:push-notifications:test';

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

    public function __construct(RestClient $restClient, LoggerInterface $logger, string $expoBackendUrl)
    {
        $this->restClient = $restClient;
        $this->logger = $logger;
        $this->expoBackendUrl = $expoBackendUrl;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send a push notification.')
            ->addArgument('token', InputArgument::REQUIRED, 'Expo token')
            ->addArgument('channel', InputArgument::REQUIRED, 'Android channel ID')
            ->addArgument('id', InputArgument::REQUIRED, 'Detail ID')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'How many?', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);

        $token = $input->getArgument('token');
        $channel = $input->getArgument('channel');
        $id = $input->getArgument('id');
        $count = intval($input->getOption('count'));

        $data = [];
        $message = new \stdClass();
        $message->to = $token;
        $message->channelId = $channel;
        $message->priority = 'high';
        $message->body = 'Test notification';
        $message->data = new \stdClass();
        $message->data->id = $id;
        $message->sound = 'default';
        $message->vibrate = true;
        for ($i = 1; $i <= $count; $i++) {
            $messageTmp = clone $message;
            $messageTmp->title = 'Notification ' . $i;
            $data[] = $messageTmp;
        }
        $json = $serializer->encode($data, 'json');

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

        return 0;
    }
}
