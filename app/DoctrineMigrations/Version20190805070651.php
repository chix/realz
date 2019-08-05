<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use AppBundle\Entity\AdvertType;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190805070651 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE advert_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_C5FE166477153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('ALTER TABLE advert ADD type_id INT DEFAULT NULL AFTER id');
        $this->addSql('ALTER TABLE advert ADD CONSTRAINT FK_54F1F40BC54C8C93 FOREIGN KEY (type_id) REFERENCES advert_type (id)');
        $this->addSql('CREATE INDEX IDX_54F1F40BC54C8C93 ON advert (type_id)');
        $this->addSql('CREATE INDEX updated_at_idx ON advert (updated_at)');
        $this->addSql(sprintf(
            'INSERT INTO advert_type (name, code, created_at, updated_at) VALUES '.
            '("%s", "%s", NOW(), NOW()), '.
            '("%s", "%s", NOW(), NOW())',
            'Sale', AdvertType::TYPE_SALE,
            'Rent', AdvertType::TYPE_RENT
        ));
        $this->addSql('UPDATE advert set type_id = 1');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE advert DROP FOREIGN KEY FK_54F1F40BC54C8C93');
        $this->addSql('DROP TABLE advert_type');
        $this->addSql('DROP INDEX IDX_54F1F40BC54C8C93 ON advert');
        $this->addSql('DROP INDEX updated_at_idx ON advert');
        $this->addSql('ALTER TABLE advert DROP type_id');
    }
}
