<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AdvertType;
use App\Entity\PropertyType;
use App\Service\CrawlerInterface;
use App\Service\BazosCrawler;
use App\Service\BezrealitkyCrawler;
use App\Service\CeskerealityCrawler;
use App\Service\SrealityCrawler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportNewAdvertsCommand extends Command
{
    protected static $defaultName = 'app:import:adverts';

    /**
     * @var array<string>
     */
    protected static $activeCrawlers = ['sreality', 'bezrealitky', 'bazos', 'ceskereality'];

    /**
     * @var EntityManagerInterface
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
     * @var CeskerealityCrawler
     */
    protected $ceskerealityCrawler;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        BazosCrawler $bazosCrawler,
        BezrealitkyCrawler $bezrealityCrawler,
        SrealityCrawler $srealityCrawler,
        CeskerealityCrawler $ceskerealityCrawler
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->bazosCrawler = $bazosCrawler;
        $this->bezrealitkyCrawler = $bezrealityCrawler;
        $this->srealityCrawler = $srealityCrawler;
        $this->ceskerealityCrawler = $ceskerealityCrawler;

        parent::__construct();
    }

    protected function configure(): void
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

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $crawlers = [];
        /** @var string[] $crawlersInput */
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
                $output->writeln('<comment>Loading all crawlers.</comment>');
            }
            $crawlers[] = $this->bazosCrawler;
            $crawlers[] = $this->bezrealitkyCrawler;
            $crawlers[] = $this->srealityCrawler;
            $crawlers[] = $this->ceskerealityCrawler;
        }

        foreach ($crawlers as $crawler) { /** @var CrawlerInterface $crawler */
            $this->logger->debug('Starting ' . $crawler->getIdentifier());

            $adverts = $crawler->getNewAdverts(AdvertType::TYPE_SALE, PropertyType::TYPE_FLAT);
            $adverts = array_merge($adverts, $crawler->getNewAdverts(AdvertType::TYPE_RENT, PropertyType::TYPE_FLAT));

            $this->logger->debug(count($adverts) . ' new ads found');

            foreach ($adverts as $advert) {
                $this->entityManager->persist($advert);
                $property = $advert->getProperty();
                if ($property !== null) {
                    $this->entityManager->persist($property);
                    $location = $property->getLocation();
                    if ($location !== null) {
                        $this->entityManager->persist($location);
                    }
                }
            }
            $this->entityManager->flush();
        }

        return 0;
    }
}
