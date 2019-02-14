<?php

namespace AppBundle\Command;

use AppBundle\Service\CrawlerInterface;
use AppBundle\Service\BazosCrawler;
use AppBundle\Service\BezrealitkyCrawler;
use AppBundle\Service\SrealityCrawler;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportNewAdvertsCommand extends Command
{
    protected static $defaultName = 'app:import:adverts';
    protected static $activeCrawlers = ['sreality', 'bezrealitky', 'bazos'];

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var BazosCrawler
     */
    protected $bazosCrawler;

    /**
     * @var BezrealitkyCrawler
     */
    protected $bezrealitkyCrawler;

    /**
     * @var SrealityCrawler
     */
    protected $srealityCrawler;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        EntityManager $entityManager,
        LoggerInterface $logger,
        BazosCrawler $bazosCrawler,
        BezrealitkyCrawler $bezrealityCrawler,
        SrealityCrawler $srealityCrawler
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->bazosCrawler = $bazosCrawler;
        $this->bezrealitkyCrawler = $bezrealityCrawler;
        $this->srealityCrawler = $srealityCrawler;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import new adverts.')
            ->addOption(
                'crawlers',
                'c',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Limit crawlers, allowed values: ' . implode(', ', self::$activeCrawlers)
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $crawlers = [];
        $crawlersInput = $input->getOption('crawlers');
        foreach ($crawlersInput as $code) {
            if (in_array($code, self::$activeCrawlers)) {
                $crawlers[] = $this->{$code.'Crawler'};
            } else {
                $output->writeln(sprintf('<comment>Crawler "%s" not found.</comment>', $code));
            }
        }
        if (empty($crawlers)) {
            if (!empty($crawlersInput)) {
                $output->writeln(sprintf('<comment>Loading all crawlers.</comment>', $code));
            }
            $crawlers[] = $this->bazosCrawler;
            $crawlers[] = $this->bezrealitkyCrawler;
            $crawlers[] = $this->srealityCrawler;
        }

        foreach ($crawlers as $crawler) { /* @var $crawler CrawlerInterface */
            $this->logger->debug('Starting ' . $crawler->getIdentifier());

            $adverts = $crawler->getNewAdverts();

            $this->logger->debug(count($adverts) . ' new ads found');

            foreach ($adverts as $advert) {
                $this->entityManager->persist($advert);
                $this->entityManager->persist($advert->getProperty());
                $this->entityManager->persist($advert->getProperty()->getLocation());
            }
            $this->entityManager->flush();
        }
    }
}
