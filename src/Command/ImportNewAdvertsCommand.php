<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AdvertType;
use App\Entity\PropertySubtype;
use App\Entity\PropertyType;
use App\Service\BazosCrawler;
use App\Service\BezrealitkyCrawler;
use App\Service\CeskerealityCrawler;
use App\Service\CrawlerInterface;
use App\Service\SrealityCrawler;
use App\Service\UlovdomovCrawler;
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
    protected static $activeCrawlers = ['sreality', 'bezrealitky', 'bazos', 'ceskereality', 'ulovdomov'];

    /**
     * @var array<string,string>
     */
    protected static $supportedLocations = [
        'brno' => '582786',
        'olomouc' => '500496',
        'pardubice' => '555134',
        'hradec' => '569810',
        'hradiste' => '592005',
        'nachod' => '573868',
        'brno-venkov' => 'CZ0643',
        'blansko' => 'CZ0641',
        'vyskov' => 'CZ0646',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private BazosCrawler $bazosCrawler,
        private BezrealitkyCrawler $bezrealitkyCrawler,
        private SrealityCrawler $srealityCrawler,
        private CeskerealityCrawler $ceskerealityCrawler,
        private UlovdomovCrawler $ulovdomovCrawler
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Import new adverts.')
            ->addOption(
                'sources',
                's',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Limit sources, allowed values: '.implode(', ', self::$activeCrawlers)
            )
            ->addOption(
                'location',
                'l',
                InputOption::VALUE_REQUIRED,
                'Limit location, allowed values: '.implode(', ', array_keys(self::$supportedLocations))
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $crawlers = [];
        /** @var string[] $sources */
        $sources = $input->getOption('sources');
        foreach ($sources as $code) {
            if (in_array($code, self::$activeCrawlers)) {
                $crawlers[] = $this->{$code.'Crawler'};
            } else {
                $output->writeln(sprintf('<comment>Crawler "%s" not found.</comment>', $code));
            }
        }
        /** @var string|null $location */
        $location = $input->getOption('location');
        if (null !== $location && !array_key_exists($location, self::$supportedLocations)) {
            $output->writeln(sprintf('<comment>Location "%s" not supported.</comment>', $location));
        }
        if (empty($crawlers)) {
            if (!empty($sources)) {
                $output->writeln('<comment>Loading all crawlers.</comment>');
            }
            $crawlers[] = $this->bazosCrawler;
            $crawlers[] = $this->bezrealitkyCrawler;
            $crawlers[] = $this->srealityCrawler;
            $crawlers[] = $this->ceskerealityCrawler;
            $crawlers[] = $this->ulovdomovCrawler;
        }

        foreach ($crawlers as $crawler) { /* @var CrawlerInterface $crawler */
            $this->logger->debug('Starting '.$crawler->getIdentifier(), ($location) ? [$location] : []);

            $adverts = [];
            // flats
            if (null === $location || 'CZ' !== substr(self::$supportedLocations[$location], 0, 2)) { // all cities
                $adverts = array_merge($adverts, $crawler->getNewAdverts(
                    AdvertType::TYPE_SALE,
                    PropertyType::TYPE_FLAT,
                    null,
                    $location ? self::$supportedLocations[$location] : null
                ));
                $adverts = array_merge($adverts, $crawler->getNewAdverts(
                    AdvertType::TYPE_RENT,
                    PropertyType::TYPE_FLAT,
                    null,
                    $location ? self::$supportedLocations[$location] : null
                ));
            }

            // cottages + gardens
            if ((null !== $location && ('CZ' === substr(self::$supportedLocations[$location], 0, 2) || 'brno' === $location)) // regions + brno
                && $crawler instanceof SrealityCrawler
            ) {
                $adverts = array_merge($adverts, $crawler->getNewAdverts(
                    AdvertType::TYPE_SALE,
                    PropertyType::TYPE_HOUSE,
                    PropertySubtype::SUBTYPE_COTTAGE,
                    $location ? self::$supportedLocations[$location] : null
                ));
                $adverts = array_merge($adverts, $crawler->getNewAdverts(
                    AdvertType::TYPE_SALE,
                    PropertyType::TYPE_LAND,
                    PropertySubtype::SUBTYPE_GARDEN,
                    $location ? self::$supportedLocations[$location] : null
                ));
            }

            $this->logger->debug(count($adverts).' new ads found');

            foreach ($adverts as $advert) {
                $this->entityManager->persist($advert);
                $property = $advert->getProperty();
                if (null !== $property) {
                    $this->entityManager->persist($property);
                    $location = $property->getLocation();
                    if (null !== $location) {
                        $this->entityManager->persist($location);
                    }
                }
            }
            $this->entityManager->flush();
        }

        return 0;
    }
}
