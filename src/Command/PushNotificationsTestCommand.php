<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PushNotificationsTestCommand extends Command
{
    protected static $defaultName = 'app:push-notifications:test';

    public function __construct(
        private HttpClientInterface $restClient,
        private string $expoBackendUrl
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send a push notification.')
            ->addArgument('token', InputArgument::REQUIRED, 'Expo token')
            ->addArgument('channel', InputArgument::REQUIRED, 'Android channel ID')
            ->addArgument('type', InputArgument::REQUIRED, 'Advert type')
            ->addArgument('id', InputArgument::REQUIRED, 'Advert ID')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'How many?', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $token = $input->getArgument('token');
        $channel = $input->getArgument('channel');
        $id = $input->getArgument('id');
        $type = $input->getArgument('type');
        $count = intval($input->getOption('count'));

        $data = [];
        $message = new \stdClass();
        $message->to = $token;
        $message->channelId = $channel;
        $message->priority = 'high';
        $message->body = 'Test notification';
        $message->data = new \stdClass();
        $message->data->id = $id;
        $message->data->type = $type;
        $message->sound = 'default';
        $message->vibrate = true;
        for ($i = 1; $i <= $count; ++$i) {
            $messageTmp = clone $message;
            $messageTmp->title = 'Notification '.$i;
            $data[] = $messageTmp;
        }

        try {
            $response = $this->restClient->request('POST', $this->expoBackendUrl, [
                'json' => $data,
                'headers' => [
                    'Accept: application/json',
                    'Content-Type: application/json',
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
