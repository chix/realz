<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Region;
use App\Entity\District;
use App\Entity\City;
use App\Entity\CityDistrict;
use App\Repository\CityRepository;
use App\Repository\CityDistrictRepository;
use App\Repository\DistrictRepository;
use App\Repository\RegionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class ImportRegistryCommand extends Command
{
    protected static $defaultName = 'app:import:registry';

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var FileLocator */
    protected $fileLocator;

    /** @var CityRepository */
    protected $cityRepository;

    /** @var CityDistrictRepository */
    protected $cityDistrictRepository;

    /** @var DistrictRepository */
    protected $districtRepository;

    /** @var RegionRepository */
    protected $regionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        FileLocator $fileLocator,
        CityRepository $cityRepository,
        CityDistrictRepository $cityDistrictRepository,
        DistrictRepository $districtRepository,
        RegionRepository $regionRepository
    ) {
        $this->entityManager = $entityManager;
        $this->fileLocator = $fileLocator;
        $this->cityRepository = $cityRepository;
        $this->cityDistrictRepository = $cityDistrictRepository;
        $this->districtRepository = $districtRepository;
        $this->regionRepository = $regionRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Import czech regions, districts and cities.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);

        /** @var string $path */
        $path = $this->fileLocator->locate('../Resources/data/registry.csv', __DIR__);
        $data = $serializer->decode((string)file_get_contents($path), 'csv');
        /** @var string $pathCityDistricts */
        $pathCityDistricts = $this->fileLocator->locate('../Resources/data/registry_city_districts.csv', __DIR__);
        $dataCityDistricts = $serializer->decode((string)file_get_contents($pathCityDistricts), 'csv');

        $max = count($data) + count($dataCityDistricts);
        $batchSize = 25;
        $progressBar = new ProgressBar($output, $max);
        $progressBar->setFormat('Importing registry [%bar% %percent:3s%%] %current%/%max% %remaining:6s%');
        $progressBar->setRedrawFrequency((int)ceil($max / 100));
        $regionMap = [];
        $districtMap = [];
        foreach ($data as $i => $row) {
            $progressBar->advance();

            if (!isset($regionMap[$row['region_code']])) {
                $region = $this->regionRepository->findOneByCode($row['region_code']);
                if (!$region) {
                    $region = new Region();
                    $region->setName($row['region']);
                    $region->setCode($row['region_code']);
                    $this->entityManager->persist($region);
                }
                $regionMap[$row['region_code']] = $region;
            }

            if (!isset($districtMap[$row['district_code']])) {
                $district = $this->districtRepository->findOneByCode($row['district_code']);
                if (!$district) {
                    $district = new District();
                    $district->setName($row['district']);
                    $district->setCode($row['district_code']);
                    $district->setRegion($regionMap[$row['region_code']]);
                    $this->entityManager->persist($district);
                }
                $districtMap[$row['district_code']] = $district;
            }

            $city = $this->cityRepository->findOneByCode($row['city_code']);
            if (!$city) {
                $city = new City();
                $city->setName($row['city']);
                $city->setCode($row['city_code']);
                $city->setDistrict($districtMap[$row['district_code']]);
                $city->setLatitude(floatval($row['latitude']));
                $city->setLongitude(floatval($row['longitude']));
                $this->entityManager->persist($city);
            }

            if (($i % $batchSize) === 0) {
                $districtMap = [];
                $regionMap = [];
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        foreach ($dataCityDistricts as $i => $row) {
            $progressBar->advance();

            $city = $this->cityRepository->findOneByCode($row['city_code']);
            if ($city === null) {
                continue;
            }
            $cityDistrict = $this->cityDistrictRepository->findOneByCode($row['district_code']);
            if ($cityDistrict === null) {
                $cityDistrict = new CityDistrict();
                $cityDistrict->setName($row['district']);
                $cityDistrict->setCode($row['district_code']);
                $cityDistrict->setCity($city);
            }
            $cityDistrict->setQueries(explode('|', $row['queries']));
            $this->entityManager->persist($cityDistrict);

            if (($i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
        $output->writeln(' <info>✓</info>');

        return 0;
    }
}
