<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260911130344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AI classification for media assets';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQL57Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQL57Platform'."
        );

        $this->addSql('CREATE TABLE networkteam_neos_assetaiclassification (persistence_object_identifier VARCHAR(40) NOT NULL, asset VARCHAR(40) NOT NULL, classification VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_ASSET_AI_CLASSIFICATION_ASSET (asset), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE networkteam_neos_assetaiclassification ADD CONSTRAINT FK_ASSET_AI_CLASSIFICATION_ASSET FOREIGN KEY (asset) REFERENCES neos_media_domain_model_asset (persistence_object_identifier) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQL57Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQL57Platform'."
        );

        $this->addSql('DROP TABLE networkteam_neos_assetaiclassification');
    }
}
