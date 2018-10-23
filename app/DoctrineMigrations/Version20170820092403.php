<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170820092403 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE property_disposition (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_F33D7F5677153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE property (id INT AUTO_INCREMENT NOT NULL, type_id INT DEFAULT NULL, disposition_id INT DEFAULT NULL, construction_id INT DEFAULT NULL, condition_id INT DEFAULT NULL, location_id INT DEFAULT NULL, ownership VARCHAR(255) DEFAULT NULL, floor INT DEFAULT NULL, area INT DEFAULT NULL, balcony TINYINT(1) DEFAULT \'0\' NOT NULL, terrace TINYINT(1) DEFAULT \'0\' NOT NULL, elevator TINYINT(1) DEFAULT \'0\' NOT NULL, parking TINYINT(1) DEFAULT \'0\' NOT NULL, loggia TINYINT(1) DEFAULT \'0\' NOT NULL, images LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_8BF21CDEC54C8C93 (type_id), INDEX IDX_8BF21CDE287B65ED (disposition_id), INDEX IDX_8BF21CDECF48117A (construction_id), INDEX IDX_8BF21CDE887793B6 (condition_id), INDEX IDX_8BF21CDE64D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE advert (id INT AUTO_INCREMENT NOT NULL, source_id INT DEFAULT NULL, property_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price INT DEFAULT NULL, currency VARCHAR(8) DEFAULT NULL, sourceUrl VARCHAR(1024) NOT NULL, externalUrl VARCHAR(1024) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_54F1F40B3F46DA1F (sourceUrl), INDEX IDX_54F1F40B953C1C61 (source_id), INDEX IDX_54F1F40B549213EC (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, city_id INT DEFAULT NULL, street VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, INDEX IDX_5E9E89CB8BAC62AF (city_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE district (id INT AUTO_INCREMENT NOT NULL, region_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_31C1548777153098 (code), INDEX IDX_31C1548798260155 (region_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE property_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_93C6E81377153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE region (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_F62F17677153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE source (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_5F8A7F7377153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE property_condition (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_CC398AFE77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, district_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, UNIQUE INDEX UNIQ_2D5B023477153098 (code), INDEX IDX_2D5B0234B08FA272 (district_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE push_notification_token (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(255) NOT NULL, active TINYINT(1) DEFAULT \'1\' NOT NULL, error_count INT DEFAULT 0 NOT NULL, last_response LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', enabled TINYINT(1) DEFAULT \'1\' NOT NULL, filters LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX token_idx (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE push_notification_tokens_adverts (push_notification_token_id INT NOT NULL, advert_id INT NOT NULL, INDEX IDX_348772055193F563 (push_notification_token_id), INDEX IDX_34877205D07ECCB6 (advert_id), PRIMARY KEY(push_notification_token_id, advert_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE property_construction (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_1394123777153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDEC54C8C93 FOREIGN KEY (type_id) REFERENCES property_type (id)');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE287B65ED FOREIGN KEY (disposition_id) REFERENCES property_disposition (id)');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDECF48117A FOREIGN KEY (construction_id) REFERENCES property_construction (id)');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE887793B6 FOREIGN KEY (condition_id) REFERENCES property_condition (id)');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE64D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE advert ADD CONSTRAINT FK_54F1F40B953C1C61 FOREIGN KEY (source_id) REFERENCES source (id)');
        $this->addSql('ALTER TABLE advert ADD CONSTRAINT FK_54F1F40B549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB8BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE district ADD CONSTRAINT FK_31C1548798260155 FOREIGN KEY (region_id) REFERENCES region (id)');
        $this->addSql('ALTER TABLE city ADD CONSTRAINT FK_2D5B0234B08FA272 FOREIGN KEY (district_id) REFERENCES district (id)');
        $this->addSql('ALTER TABLE push_notification_tokens_adverts ADD CONSTRAINT FK_348772055193F563 FOREIGN KEY (push_notification_token_id) REFERENCES push_notification_token (id)');
        $this->addSql('ALTER TABLE push_notification_tokens_adverts ADD CONSTRAINT FK_34877205D07ECCB6 FOREIGN KEY (advert_id) REFERENCES advert (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE287B65ED');
        $this->addSql('ALTER TABLE advert DROP FOREIGN KEY FK_54F1F40B549213EC');
        $this->addSql('ALTER TABLE push_notification_tokens_adverts DROP FOREIGN KEY FK_34877205D07ECCB6');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE64D218E');
        $this->addSql('ALTER TABLE city DROP FOREIGN KEY FK_2D5B0234B08FA272');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDEC54C8C93');
        $this->addSql('ALTER TABLE district DROP FOREIGN KEY FK_31C1548798260155');
        $this->addSql('ALTER TABLE advert DROP FOREIGN KEY FK_54F1F40B953C1C61');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE887793B6');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CB8BAC62AF');
        $this->addSql('ALTER TABLE push_notification_tokens_adverts DROP FOREIGN KEY FK_348772055193F563');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDECF48117A');
        $this->addSql('DROP TABLE property_disposition');
        $this->addSql('DROP TABLE property');
        $this->addSql('DROP TABLE advert');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE district');
        $this->addSql('DROP TABLE property_type');
        $this->addSql('DROP TABLE region');
        $this->addSql('DROP TABLE source');
        $this->addSql('DROP TABLE property_condition');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE push_notification_token');
        $this->addSql('DROP TABLE push_notification_tokens_adverts');
        $this->addSql('DROP TABLE property_construction');
    }
}
