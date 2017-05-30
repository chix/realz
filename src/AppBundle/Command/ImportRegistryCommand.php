<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

use AppBundle\Entity\Region;
use AppBundle\Entity\District;
use AppBundle\Entity\City;

class ImportRegistryCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('app:import:registry')
            ->setDescription('Import czech regions, districts and cities.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cityRepository = $em->getRepository('AppBundle:City');
        $districtRepository = $em->getRepository('AppBundle:District');
        $regionRepository = $em->getRepository('AppBundle:Region');
        $kernel = $this->getContainer()->get('kernel');
        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);

        $path = $kernel->locateResource('@AppBundle/Resources/data/registry.csv');
        $data = $serializer->decode(file_get_contents($path), 'csv');

        $max = count($data);
        $batchSize = 25;
        $progressBar = new ProgressBar($output, $max);
        $progressBar->setFormat('Importing registry [%bar% %percent:3s%%] %current%/%max% %remaining:6s%');
        $progressBar->setRedrawFrequency(ceil($max / 100));
        $regionMap = [];
        $districtMap = [];
        foreach ($data as $i => $row) {
            $progressBar->advance();

            if (!isset($regionMap[$row['region_code']])) {
                $region = $regionRepository->findOneByCode($row['region_code']);
                if (!$region) {
                    $region = new Region();
                    $region->setName($row['region']);
                    $region->setCode($row['region_code']);
                    $em->persist($region);
                }
                $regionMap[$row['region_code']] = $region;
            }

            if (!isset($districtMap[$row['district_code']])) {
                $district = $districtRepository->findOneByCode($row['district_code']);
                if (!$district) {
                    $district = new District();
                    $district->setName($row['district']);
                    $district->setCode($row['district_code']);
                    $district->setRegion($regionMap[$row['region_code']]);
                    $em->persist($district);
                }
                $districtMap[$row['district_code']] = $district;
            }

            $city = $cityRepository->findOneByCode($row['city_code']);
            if (!$city) {
                $city = new City();
                $city->setName($row['city']);
                $city->setCode($row['city_code']);
                $city->setDistrict($districtMap[$row['district_code']]);
                $city->setLatitude(floatval($row['latitude']));
                $city->setLongitude(floatval($row['longitude']));
                $em->persist($city);
            }

            if (($i % $batchSize) === 0) {
                $districtMap = [];
                $regionMap = [];
                $em->flush();
                $em->clear();
            }
        }
        $em->flush();
        $em->clear();
        $progressBar->finish();
        $output->writeln(' <info>✓</info>');
    }
}
