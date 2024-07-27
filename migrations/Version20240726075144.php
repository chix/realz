<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\PropertySubtype;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240726075144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE property_subtype (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_CA66FDA77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE location ADD district_id INT DEFAULT NULL AFTER street');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CBB08FA272 FOREIGN KEY (district_id) REFERENCES district (id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CBB08FA272 ON location (district_id)');
        $this->addSql('ALTER TABLE property ADD subtype_id INT DEFAULT NULL AFTER type_id');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE8E2E245C FOREIGN KEY (subtype_id) REFERENCES property_subtype (id)');
        $this->addSql('CREATE INDEX IDX_8BF21CDE8E2E245C ON property (subtype_id)');

        $this->addSql(sprintf(
            'INSERT INTO property_subtype (name, code, created_at, updated_at) VALUES '.
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
            'House',
            PropertySubtype::SUBTYPE_HOUSE,
            'Cottage',
            PropertySubtype::SUBTYPE_COTTAGE,
            'Garrage',
            PropertySubtype::SUBTYPE_GARRAGE,
            'Farm',
            PropertySubtype::SUBTYPE_FARM,
            'Property',
            PropertySubtype::SUBTYPE_PROPERTY,
            'Field',
            PropertySubtype::SUBTYPE_FIELD,
            'Woods',
            PropertySubtype::SUBTYPE_WOODS,
            'Plantation',
            PropertySubtype::SUBTYPE_PLANTATION,
            'Garden',
            PropertySubtype::SUBTYPE_GARDEN,
            'Other',
            PropertySubtype::SUBTYPE_OTHER,
        ));
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE8E2E245C');
        $this->addSql('DROP TABLE property_subtype');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CBB08FA272');
        $this->addSql('DROP INDEX IDX_5E9E89CBB08FA272 ON location');
        $this->addSql('ALTER TABLE location DROP district_id');
        $this->addSql('DROP INDEX IDX_8BF21CDE8E2E245C ON property');
        $this->addSql('ALTER TABLE property DROP subtype_id');
    }
}
