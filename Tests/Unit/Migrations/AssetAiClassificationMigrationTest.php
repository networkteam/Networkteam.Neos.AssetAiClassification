<?php

namespace Networkteam\Neos\AssetAiClassification\Tests\Unit\Migrations;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\AbortMigration;
use Neos\Flow\Persistence\Doctrine\Migrations\Version20260911130344;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

require_once dirname(__DIR__, 3) . '/Migrations/Mysql/Version20260911130344.php';

class AssetAiClassificationMigrationTest extends TestCase
{
    /**
     * @test
     * @dataProvider assetIdentifierEncodings
     */
    public function foreignKeyColumnUsesTheReferencedColumnsEncoding(string $charset, string $collation, MySQL57Platform $platform): void
    {
        $schema = new Schema();
        $schema->createTable('neos_media_domain_model_asset')->addColumn('persistence_object_identifier', 'string', [
            'length' => 40,
            'platformOptions' => ['charset' => $charset, 'collation' => $collation],
        ]);
        $migration = $this->createMigration($platform);

        $migration->up($schema);

        $queries = $migration->getSql();
        self::assertCount(2, $queries);
        self::assertStringContainsString("asset VARCHAR(40) CHARACTER SET `$charset` COLLATE `$collation` NOT NULL", $queries[0]->getStatement());
        self::assertStringContainsString('DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`', $queries[0]->getStatement());
        self::assertStringContainsString('REFERENCES neos_media_domain_model_asset (persistence_object_identifier) ON DELETE CASCADE', $queries[1]->getStatement());
    }

    public function assetIdentifierEncodings(): array
    {
        return [
            'MySQL 5.7 legacy' => ['utf8', 'utf8_unicode_ci', new MySQL57Platform()],
            'MySQL 8 legacy' => ['utf8mb3', 'utf8mb3_unicode_ci', new MySQL80Platform()],
            'MySQL 8 utf8mb4' => ['utf8mb4', 'utf8mb4_unicode_ci', new MySQL80Platform()],
            'MySQL 8 different collation' => ['utf8mb4', 'utf8mb4_0900_ai_ci', new MySQL80Platform()],
        ];
    }

    /** @test */
    public function missingEncodingAbortsBeforeCreatingTheTable(): void
    {
        $schema = new Schema();
        $schema->createTable('neos_media_domain_model_asset')->addColumn('persistence_object_identifier', 'string');
        $migration = $this->createMigration(new MySQL80Platform());

        try {
            $migration->up($schema);
            self::fail('Expected missing encoding to abort the migration.');
        } catch (AbortMigration $exception) {
            self::assertSame([], $migration->getSql());
        }
    }

    private function createMigration(MySQL57Platform $platform): Version20260911130344
    {
        // Supplying the platform avoids opening a database connection while planning SQL.
        $connection = DriverManager::getConnection(['driver' => 'pdo_mysql', 'platform' => $platform]);
        return new Version20260911130344($connection, new NullLogger());
    }
}
