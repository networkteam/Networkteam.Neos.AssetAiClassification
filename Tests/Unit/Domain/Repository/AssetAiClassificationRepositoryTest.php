<?php

namespace Networkteam\Neos\AssetAiClassification\Tests\Unit\Domain\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use Neos\Flow\Persistence\Aspect\PersistenceMagicInterface;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Persistence\Doctrine\Query;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Asset;
use Networkteam\Neos\AssetAiClassification\Domain\Model\AssetAiClassification;
use Networkteam\Neos\AssetAiClassification\Domain\Repository\AssetAiClassificationRepository;

/**
 * Exercises real Doctrine queries and deferred inserts using an in-memory database.
 * The fixtures supply the identifiers normally introduced by Flow's proxy compiler.
 */
class AssetAiClassificationRepositoryTest extends UnitTestCase
{
    private EntityManager $entityManager;
    private AssetAiClassificationRepository $repository;
    private ClassificationAssetFixture $asset;

    protected function setUp(): void
    {
        parent::setUp();
        $configuration = Setup::createConfiguration(true);
        $configuration->setMetadataDriverImpl(new StaticPHPDriver([__DIR__]));
        $this->entityManager = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $configuration);
        (new SchemaTool($this->entityManager))->createSchema([
            $this->entityManager->getClassMetadata(ClassificationAssetFixture::class),
            $this->entityManager->getClassMetadata(ClassificationFixture::class),
        ]);

        $persistenceManager = $this->getMockBuilder(PersistenceManager::class)
            ->onlyMethods(['createQueryForType'])->getMock();
        $this->inject($persistenceManager, 'entityManager', $this->entityManager);
        $persistenceManager->method('createQueryForType')->willReturnCallback(function (string $type): Query {
            $query = new Query($type);
            $query->injectEntityManager($this->entityManager);
            $query->injectSettings(['persistence' => ['cacheAllQueryResults' => false]]);
            return $query;
        });
        $this->repository = new AssetAiClassificationRepository();
        $this->inject($this->repository, 'entityClassName', ClassificationFixture::class);
        $this->inject($this->repository, 'persistenceManager', $persistenceManager);
        $this->inject($this->repository, 'entityManager', $this->entityManager);

        $this->asset = new ClassificationAssetFixture('asset');
        $this->entityManager->persist($this->asset);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
        parent::tearDown();
    }

    /** @test */
    public function repeatedSavesBeforeFlushDoNotInsertDuplicateClassifications(): void
    {
        foreach ([AssetAiClassification::AI_GENERATED, AssetAiClassification::AI_GENERATED, AssetAiClassification::AI_MODIFIED] as $index => $value) {
            $classification = $this->repository->findOneByAsset($this->asset);
            if ($classification === null) {
                $this->repository->add(new ClassificationFixture('classification-' . $index, $this->asset, $value));
            } else {
                $classification->setClassification($value);
                $this->repository->update($classification);
            }
        }

        $this->entityManager->flush();
        self::assertSame(1, (int)$this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM classification_fixture'));
        self::assertSame(AssetAiClassification::AI_MODIFIED, $this->repository->findOneByAsset($this->asset)->getClassification());
    }

    /** @test */
    public function lookupStillFindsPersistedClassificationsAfterClearingPersistenceState(): void
    {
        $this->repository->add(new ClassificationFixture('classification', $this->asset, AssetAiClassification::AI_GENERATED));
        $this->entityManager->flush();
        $this->entityManager->clear();
        $asset = $this->entityManager->find(ClassificationAssetFixture::class, 'asset');

        self::assertSame(AssetAiClassification::AI_GENERATED, $this->repository->findOneByAsset($asset)->getClassification());
    }

    /** @test */
    public function clearingPersistenceStateDiscardsUnflushedClassifications(): void
    {
        $this->repository->add(new ClassificationFixture('classification', $this->asset, AssetAiClassification::AI_GENERATED));
        $this->entityManager->clear();
        $asset = $this->entityManager->find(ClassificationAssetFixture::class, 'asset');

        self::assertNull($this->repository->findOneByAsset($asset));
    }

    /** @test */
    public function pendingClassificationsOfOtherAssetsAreIgnored(): void
    {
        $otherAsset = new ClassificationAssetFixture('other-asset');
        $this->entityManager->persist($otherAsset);
        $this->repository->add(new ClassificationFixture('other-classification', $otherAsset, AssetAiClassification::AI_GENERATED));

        self::assertNull($this->repository->findOneByAsset($this->asset));
    }

    /** @test */
    public function removingAnUnflushedClassificationAllowsItToBeCreatedAgain(): void
    {
        $classification = new ClassificationFixture('classification', $this->asset, AssetAiClassification::AI_GENERATED);
        $this->repository->add($classification);
        $this->repository->remove($classification);

        self::assertNull($this->repository->findOneByAsset($this->asset));
        $this->repository->add(new ClassificationFixture('replacement', $this->asset, AssetAiClassification::AI_MODIFIED));
        $this->entityManager->flush();

        self::assertSame(1, (int)$this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM classification_fixture'));
        self::assertSame(AssetAiClassification::AI_MODIFIED, $this->repository->findOneByAsset($this->asset)->getClassification());
    }
}

class ClassificationAssetFixture extends Asset implements PersistenceMagicInterface
{
    public string $Persistence_Object_Identifier;

    public function __construct(string $identifier)
    {
        $this->Persistence_Object_Identifier = $identifier;
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setPrimaryTable(['name' => 'asset_fixture']);
        $metadata->mapField(['fieldName' => 'Persistence_Object_Identifier', 'type' => 'string', 'id' => true]);
    }
}

class ClassificationFixture extends AssetAiClassification implements PersistenceMagicInterface
{
    public string $Persistence_Object_Identifier;

    public function __construct(string $identifier, Asset $asset, string $classification)
    {
        parent::__construct($asset, $classification);
        $this->Persistence_Object_Identifier = $identifier;
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setPrimaryTable(['name' => 'classification_fixture']);
        $metadata->mapField(['fieldName' => 'Persistence_Object_Identifier', 'type' => 'string', 'id' => true]);
        $metadata->mapField(['fieldName' => 'classification', 'type' => 'string', 'length' => 20]);
        $metadata->mapOneToOne([
            'fieldName' => 'asset',
            'targetEntity' => ClassificationAssetFixture::class,
            'joinColumns' => [['name' => 'asset', 'referencedColumnName' => 'Persistence_Object_Identifier', 'nullable' => false]],
        ]);
    }
}
