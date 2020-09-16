<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Source;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200914130809 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(sprintf(
            'INSERT INTO source (name, code, created_at, updated_at) VALUES '.
            '("%s", "%s", NOW(), NOW())',
            'ceskereality.cz',
            Source::SOURCE_CESKEREALITY
        ));
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(sprintf(
            'DELETE FROM source WHERE code IN ("%s")',
            Source::SOURCE_CESKEREALITY
        ));
    }
}
