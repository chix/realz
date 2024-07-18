<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\PropertyDisposition;
use App\Entity\Source;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181115120611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(sprintf(
            'INSERT INTO source (name, code, created_at, updated_at) VALUES '.
            '("%s", "%s", NOW(), NOW())',
            'bezrealitky.cz',
            Source::SOURCE_BEZREALITKY
        ));

        $this->addSql(sprintf('UPDATE property_disposition SET name = "2+1" WHERE code = "%s"', PropertyDisposition::DISPOSITION_2_1));
        $this->addSql(sprintf('UPDATE property_disposition SET name = "3+1" WHERE code = "%s"', PropertyDisposition::DISPOSITION_3_1));
        $this->addSql(sprintf('UPDATE property_disposition SET name = "4+1" WHERE code = "%s"', PropertyDisposition::DISPOSITION_4_1));
        $this->addSql(sprintf('UPDATE property_disposition SET name = "5+1" WHERE code = "%s"', PropertyDisposition::DISPOSITION_5_1));
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(sprintf(
            'DELETE FROM source WHERE code IN ("%s")',
            Source::SOURCE_BEZREALITKY
        ));
    }
}
