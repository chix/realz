<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PushNotificationsReceiptsCommand extends Command
{
    protected static $defaultName = 'app:push-notifications:get-receipt';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var HttpClientInterface
     */
    protected $restClient;

    /**
     * @var string
     */
    protected $expoBackendUrl;

    public function __construct(HttpClientInterface $restClient, LoggerInterface $logger, string $expoBackendUrl)
    {
        $this->restClient = $restClient;
        $this->logger = $logger;
        $this->expoBackendUrl = $expoBackendUrl;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Get a push notification receipt')
            ->addArgument('id', InputArgument::REQUIRED, 'Notification ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $id = $input->getArgument('id');

        $message = new \stdClass();
        $message->ids = [$id];

        try {
            $response = $this->restClient->request('POST', $this->expoBackendUrl, [
                'json' => $message,
                'headers' => [
                    'Accept: application/json',
                ],

            ]);
            $response->getContent();
            if ($response->getStatusCode() >= 400) {
                $output->writeln(sprintf('<error>%s</error>', $response->getContent()));
            } else {
                $output->writeln($response->getContent());
            }
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }

        return 0;
    }
}
