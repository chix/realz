<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210518123128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $statement = $this->connection->prepare('SELECT * FROM push_notification_token');
        $resultSet = $statement->executeQuery();
        $tokens = $resultSet->fetchAllAssociative();

        foreach ($tokens as $token) {
            $filtersRaw = $token['filters'];
            if (empty($filtersRaw)) {
                continue;
            }

            $filters = json_decode($filtersRaw, true);
            $filtersNew = [];
            foreach ($filters as $cityCode => $cityFilters) {
                $cityFilters['cityCode'] = (string) $cityCode;
                ksort($cityFilters);
                $filtersNew[] = $cityFilters;
            }

            $statement = $this->connection->prepare('UPDATE push_notification_token set filters = :filters WHERE id = :id');
            $statement->bindValue('id', $token['id']);
            $statement->bindValue('filters', json_encode($filtersNew));
            $statement->executeQuery();
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $statement = $this->connection->prepare('SELECT * FROM push_notification_token');
        $resultSet = $statement->executeQuery();
        $tokens = $resultSet->fetchAllAssociative();

        foreach ($tokens as $token) {
            $filtersRaw = $token['filters'];
            if (empty($filtersRaw)) {
                continue;
            }

            $filters = json_decode($filtersRaw, true);
            $filtersNew = [];
            foreach ($filters as $cityFilters) {
                $cityCode = $cityFilters['cityCode'];
                unset($cityFilters['cityCode']);
                $filtersNew[(string) $cityCode] = $cityFilters;
            }

            $statement = $this->connection->prepare('UPDATE push_notification_token set filters = :filters WHERE id = :id');
            $statement->bindValue('id', $token['id']);
            $statement->bindValue('filters', json_encode($filtersNew));
            $resultSet = $statement->executeQuery();
        }
    }
}
