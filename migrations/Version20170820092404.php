<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\PropertyType;
use App\Entity\PropertyCondition;
use App\Entity\PropertyDisposition;
use App\Entity\PropertyConstruction;
use App\Entity\Source;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170820092404 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(sprintf(
            'INSERT INTO property_disposition (name, code, created_at, updated_at) VALUES '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW())',
            'bachelor',
            PropertyDisposition::DISPOSITION_1,
            '1+kk',
            PropertyDisposition::DISPOSITION_1_kk,
            '1+1',
            PropertyDisposition::DISPOSITION_1_1,
            '2+kk',
            PropertyDisposition::DISPOSITION_2_kk,
            '2+2',
            PropertyDisposition::DISPOSITION_2_1,
            '3+kk',
            PropertyDisposition::DISPOSITION_3_kk,
            '3+3',
            PropertyDisposition::DISPOSITION_3_1,
            '4+kk',
            PropertyDisposition::DISPOSITION_4_kk,
            '4+4',
            PropertyDisposition::DISPOSITION_4_1,
            '5+kk',
            PropertyDisposition::DISPOSITION_5_kk,
            '5+5',
            PropertyDisposition::DISPOSITION_5_1,
            '6+',
            PropertyDisposition::DISPOSITION_6,
            'other',
            PropertyDisposition::DISPOSITION_other
        ));

        $this->addSql(sprintf(
            'INSERT INTO property_type (name, code, created_at, updated_at) VALUES '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW())',
            'Flat',
            PropertyType::TYPE_FLAT,
            'House',
            PropertyType::TYPE_HOUSE,
            'Land',
            PropertyType::TYPE_LAND
        ));

        $this->addSql(sprintf(
            'INSERT INTO property_condition (name, code, created_at, updated_at) VALUES '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW())',
            'For demolition',
            PropertyCondition::CONDITION_DEMOLITION,
            'In development',
            PropertyCondition::CONDITION_DEVELOPMENT,
            'Good',
            PropertyCondition::CONDITION_GOOD,
            'New',
            PropertyCondition::CONDITION_NEW,
            'Poor',
            PropertyCondition::CONDITION_POOR,
            'Renovated',
            PropertyCondition::CONDITION_RENOVATED,
            'Under construction',
            PropertyCondition::CONDITION_UNDER_CONSTRUCTION
        ));

        $this->addSql(sprintf(
            'INSERT INTO property_construction (name, code, created_at, updated_at) VALUES '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW())',
            'Brick',
            PropertyConstruction::CONSTRUCTION_BRICK,
            'Panel',
            PropertyConstruction::CONSTRUCTION_PANEL
        ));

        $this->addSql(sprintf(
            'INSERT INTO source (name, code, created_at, updated_at) VALUES '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW())',
            'sreality.cz',
            Source::SOURCE_SREALITY,
            'bazos.cz',
            Source::SOURCE_BAZOS
        ));
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(sprintf(
            'DELETE FROM property_disposition WHERE code IN ("%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s")',
            PropertyDisposition::DISPOSITION_1,
            PropertyDisposition::DISPOSITION_1_kk,
            PropertyDisposition::DISPOSITION_1_1,
            PropertyDisposition::DISPOSITION_2_kk,
            PropertyDisposition::DISPOSITION_2_1,
            PropertyDisposition::DISPOSITION_3_kk,
            PropertyDisposition::DISPOSITION_3_1,
            PropertyDisposition::DISPOSITION_4_kk,
            PropertyDisposition::DISPOSITION_4_1,
            PropertyDisposition::DISPOSITION_5_kk,
            PropertyDisposition::DISPOSITION_5_1,
            PropertyDisposition::DISPOSITION_6,
            PropertyDisposition::DISPOSITION_other
        ));

        $this->addSql(sprintf(
            'DELETE FROM property_type WHERE code IN ("%s", "%s", "%s")',
            PropertyType::TYPE_FLAT,
            PropertyType::TYPE_HOUSE,
            PropertyType::TYPE_LAND
        ));

        $this->addSql(sprintf(
            'DELETE FROM property_condition WHERE code IN ("%s", "%s", "%s", "%s", "%s", "%s", "%s")',
            PropertyCondition::CONDITION_DEMOLITION,
            PropertyCondition::CONDITION_DEVELOPMENT,
            PropertyCondition::CONDITION_GOOD,
            PropertyCondition::CONDITION_NEW,
            PropertyCondition::CONDITION_POOR,
            PropertyCondition::CONDITION_RENOVATED,
            PropertyCondition::CONDITION_UNDER_CONSTRUCTION
        ));

        $this->addSql(sprintf(
            'DELETE FROM property_construction WHERE code IN ("%s", "%s")',
            PropertyConstruction::CONSTRUCTION_BRICK,
            PropertyConstruction::CONSTRUCTION_PANEL
        ));

        $this->addSql(sprintf(
            'DELETE FROM source WHERE code IN ("%s", "%s")',
            Source::SOURCE_SREALITY,
            Source::SOURCE_BAZOS
        ));
    }
}
