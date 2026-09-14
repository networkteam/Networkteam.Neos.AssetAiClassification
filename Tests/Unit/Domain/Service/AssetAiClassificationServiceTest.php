<?php

namespace Networkteam\Neos\AssetAiClassification\Tests\Unit\Domain\Service;

use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Service\AssetService;
use Networkteam\Neos\AssetAiClassification\Domain\Model\AssetAiClassification;
use Networkteam\Neos\AssetAiClassification\Domain\Repository\AssetAiClassificationRepository;
use Networkteam\Neos\AssetAiClassification\Domain\Service\AssetAiClassificationService;

class AssetAiClassificationServiceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getReturnsClassificationFromRepository(): void
    {
        $asset = $this->createMock(Asset::class);
        $classification = new AssetAiClassification($asset, AssetAiClassification::AI_GENERATED);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->method('findOneByAsset')->with($asset)->willReturn($classification);

        self::assertSame($classification, $this->createService($repository)->get($asset));
    }

    /**
     * @test
     */
    public function getReturnsNullWithoutAsset(): void
    {
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->expects(self::never())->method('findOneByAsset');

        self::assertNull($this->createService($repository)->get(null));
    }

    /**
     * @test
     */
    public function getResolvesImageVariantsToTheOriginalAsset(): void
    {
        $asset = $this->createMock(Asset::class);
        $variant = $this->createMock(ImageVariant::class);
        $variant->method('getOriginalAsset')->willReturn($asset);
        $classification = new AssetAiClassification($asset, AssetAiClassification::AI_MODIFIED);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->expects(self::once())->method('findOneByAsset')->with($asset)->willReturn($classification);

        self::assertSame($classification, $this->createService($repository)->get($variant));
    }

    /**
     * @test
     */
    public function getClassificationQueriesTheRepositoryOnlyOncePerAsset(): void
    {
        $asset = $this->createMock(Asset::class);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->expects(self::once())->method('findOneByAsset')->with($asset)->willReturn(null);
        $service = $this->createService($repository);

        $service->getClassification($asset);
        $service->isAiGenerated($asset);

        self::assertSame(AssetAiClassification::WITHOUT_AI, $service->getClassification($asset));
    }

    /**
     * @test
     */
    public function setClassificationUpdatesTheCachedValue(): void
    {
        $asset = $this->createMock(Asset::class);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->method('findOneByAsset')->with($asset)->willReturn(null);
        $service = $this->createService($repository);

        $service->setClassification($asset, AssetAiClassification::AI_GENERATED);

        self::assertSame(AssetAiClassification::AI_GENERATED, $service->getClassification($asset));
    }

    /**
     * @test
     */
    public function getClassificationReturnsWithoutAiForUnclassifiedAssets(): void
    {
        $asset = $this->createMock(Asset::class);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->method('findOneByAsset')->with($asset)->willReturn(null);
        $service = $this->createService($repository);

        self::assertSame(AssetAiClassification::WITHOUT_AI, $service->getClassification($asset));
        self::assertFalse($service->isAiGenerated($asset));
        self::assertFalse($service->isAiModified($asset));
    }

    /**
     * @test
     */
    public function isAiGeneratedAndIsAiModifiedReflectThePersistedClassification(): void
    {
        $asset = $this->createMock(Asset::class);
        $classification = new AssetAiClassification($asset, AssetAiClassification::AI_MODIFIED);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->method('findOneByAsset')->with($asset)->willReturn($classification);
        $service = $this->createService($repository);

        self::assertFalse($service->isAiGenerated($asset));
        self::assertTrue($service->isAiModified($asset));
    }

    /**
     * @test
     */
    public function setClassificationCreatesMissingClassification(): void
    {
        $asset = $this->createMock(Asset::class);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->method('findOneByAsset')->with($asset)->willReturn(null);
        $repository->expects(self::once())->method('add')->with(self::callback(
            static fn (AssetAiClassification $classification): bool =>
                $classification->getAsset() === $asset && $classification->isAiGenerated()
        ));

        $this->createService($repository)->setClassification($asset, AssetAiClassification::AI_GENERATED);
    }

    /**
     * @test
     */
    public function setClassificationDoesNotCreateARecordForWithoutAi(): void
    {
        $asset = $this->createMock(Asset::class);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->method('findOneByAsset')->with($asset)->willReturn(null);
        $repository->expects(self::never())->method('add');
        $assetService = $this->createMock(AssetService::class);
        $assetService->expects(self::never())->method('emitAssetUpdated');

        $this->createService($repository, $assetService)
            ->setClassification($asset, AssetAiClassification::WITHOUT_AI);
    }

    /**
     * @test
     */
    public function setClassificationUpdatesExistingClassification(): void
    {
        $asset = $this->createMock(Asset::class);
        $classification = new AssetAiClassification($asset, AssetAiClassification::AI_GENERATED);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->method('findOneByAsset')->with($asset)->willReturn($classification);
        $repository->expects(self::once())->method('update')->with($classification);

        $this->createService($repository)->setClassification($asset, AssetAiClassification::AI_MODIFIED);

        self::assertFalse($classification->isAiGenerated());
        self::assertTrue($classification->isAiModified());
    }

    /**
     * @test
     */
    public function setClassificationRemovesTheRecordWhenResetToWithoutAi(): void
    {
        $asset = $this->createMock(Asset::class);
        $classification = new AssetAiClassification($asset, AssetAiClassification::AI_GENERATED);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->method('findOneByAsset')->with($asset)->willReturn($classification);
        $repository->expects(self::once())->method('remove')->with($classification);
        $repository->expects(self::never())->method('update');

        $this->createService($repository)->setClassification($asset, AssetAiClassification::WITHOUT_AI);
    }

    /**
     * @test
     */
    public function setClassificationDoesNothingIfTheClassificationIsUnchanged(): void
    {
        $asset = $this->createMock(Asset::class);
        $classification = new AssetAiClassification($asset, AssetAiClassification::AI_GENERATED);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->method('findOneByAsset')->with($asset)->willReturn($classification);
        $repository->expects(self::never())->method('update');
        $assetService = $this->createMock(AssetService::class);
        $assetService->expects(self::never())->method('emitAssetUpdated');

        $this->createService($repository, $assetService)
            ->setClassification($asset, AssetAiClassification::AI_GENERATED);
    }

    /**
     * @test
     */
    public function setClassificationRejectsUnknownClassification(): void
    {
        $asset = $this->createMock(Asset::class);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->method('findOneByAsset')->with($asset)->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);

        $this->createService($repository)->setClassification($asset, 'unknown');
    }

    /**
     * @test
     */
    public function setClassificationEmitsAssetUpdatedByDefault(): void
    {
        $asset = $this->createMock(Asset::class);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->method('findOneByAsset')->with($asset)->willReturn(null);
        $assetService = $this->createMock(AssetService::class);
        $assetService->expects(self::once())->method('emitAssetUpdated')->with($asset);

        $this->createService($repository, $assetService)
            ->setClassification($asset, AssetAiClassification::AI_GENERATED);
    }

    /**
     * @test
     */
    public function setClassificationCanSuppressAssetUpdatedSignal(): void
    {
        $asset = $this->createMock(Asset::class);
        $repository = $this->createMock(AssetAiClassificationRepository::class);
        $repository->method('findOneByAsset')->with($asset)->willReturn(null);
        $assetService = $this->createMock(AssetService::class);
        $assetService->expects(self::never())->method('emitAssetUpdated');

        $this->createService($repository, $assetService)
            ->setClassification($asset, AssetAiClassification::AI_GENERATED, false);
    }

    private function createService(
        AssetAiClassificationRepository $repository,
        ?AssetService $assetService = null
    ): AssetAiClassificationService {
        $persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $persistenceManager->method('getIdentifierByObject')->willReturn('some-asset-identifier');

        $service = new AssetAiClassificationService();
        $this->inject($service, 'classificationRepository', $repository);
        $this->inject($service, 'assetService', $assetService ?? $this->createMock(AssetService::class));
        $this->inject($service, 'persistenceManager', $persistenceManager);

        return $service;
    }
}
