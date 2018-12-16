<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use AppBundle\Service\CrawlerInterface;

class ImportNewAdvertsCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManagerInterface;
     */
    protected $em;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected function configure()
    {
        $this
            ->setName('app:import:adverts')
            ->setDescription('Import new adverts.')
            ->addOption(
                'crawlers',
                'c',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Limit crawlers, allowed values: sreality, bezrealitky, bazos'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('monolog.logger.crawler');
        $crawlers = [];
        $crawlersInput = $input->getOption('crawlers');
        foreach ($crawlersInput as $code) {
            $crawlerKey = 'crawler_' . $code;
            if ($this->getContainer()->has($crawlerKey)) {
                $crawlers[] = $this->getContainer()->get($crawlerKey);
            } else {
                $output->writeln(sprintf('<comment>Crawler "%s" not found.</comment>', $code));
            }
        }
        if (empty($crawlers)) {
            if (!empty($crawlersInput)) {
                $output->writeln(sprintf('<comment>Loading all crawlers.</comment>', $code));
            }
            $crawlers[] = $this->getContainer()->get('crawler_sreality');
            $crawlers[] = $this->getContainer()->get('crawler_bezrealitky');
            $crawlers[] = $this->getContainer()->get('crawler_bazos');
        }

        foreach ($crawlers as $crawler) { /* @var $crawler CrawlerInterface */
            $this->logger->debug('Starting ' . $crawler->getIdentifier());

            $adverts = $crawler->getNewAdverts();

            $this->logger->debug(count($adverts) . ' new ads found');

            foreach ($adverts as $advert) {
                $this->em->persist($advert);
                $this->em->persist($advert->getProperty());
                $this->em->persist($advert->getProperty()->getLocation());
            }
            $this->em->flush();
        }
    }
}
