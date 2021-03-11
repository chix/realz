<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210311083214 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $statement = $this->connection->prepare('SELECT * FROM advert ORDER BY id DESC LIMIT 300');
        $statement->execute();
        $adverts = $statement->fetchAll();

        foreach ($adverts as $advert) {
            $sourceUrl = $advert['sourceUrl'];
            if (!$sourceUrl) {
                continue;
            }

            $statement = $this->connection->prepare('SELECT * FROM advert WHERE sourceUrl = :sourceUrl AND id < :id ORDER BY id DESC LIMIT 1');
            $statement->execute(['id' => $advert['id'], 'sourceUrl' => $sourceUrl]);
            $previousAdvert = $statement->fetch();
            if ($previousAdvert && $previousAdvert['price'] !== null && $advert['price'] !== $previousAdvert['price']) {
                $statement = $this->connection->prepare('UPDATE advert set previous_price = :price WHERE id = :id');
                $statement->execute(['id' => $advert['id'], 'price' => $previousAdvert['price']]);
            }
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $statement = $this->connection->prepare('UPDATE advert set previous_price = NULL');
        $statement->execute();
    }
}
