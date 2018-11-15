<?php

namespace Application\Migrations;

use AppBundle\Entity\PropertyDisposition;
use AppBundle\Entity\Source;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181115120611 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(sprintf(
            'INSERT INTO source (name, code, created_at, updated_at) VALUES '.
            '("%s", "%s", NOW(), NOW())',
            'bezrealitky.cz', Source::SOURCE_BEZREALITKY
        ));

        $this->addSql(sprintf('UPDATE property_disposition SET name = "2+1" WHERE code = "%s"', PropertyDisposition::DISPOSITION_2_1));
        $this->addSql(sprintf('UPDATE property_disposition SET name = "3+1" WHERE code = "%s"', PropertyDisposition::DISPOSITION_3_1));
        $this->addSql(sprintf('UPDATE property_disposition SET name = "4+1" WHERE code = "%s"', PropertyDisposition::DISPOSITION_4_1));
        $this->addSql(sprintf('UPDATE property_disposition SET name = "5+1" WHERE code = "%s"', PropertyDisposition::DISPOSITION_5_1));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(sprintf(
            'DELETE FROM source WHERE code IN ("%s")',
            Source::SOURCE_BEZREALITKY
        ));
    }
}
