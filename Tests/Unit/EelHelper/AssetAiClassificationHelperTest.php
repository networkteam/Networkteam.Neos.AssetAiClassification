<?php

namespace Networkteam\Neos\AssetAiClassification\Tests\Unit\EelHelper;

use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Asset;
use Networkteam\Neos\AssetAiClassification\Domain\Model\AssetAiClassification;
use Networkteam\Neos\AssetAiClassification\Domain\Service\AssetAiClassificationService;
use Networkteam\Neos\AssetAiClassification\EelHelper\AssetAiClassificationHelper;

class AssetAiClassificationHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getPassesTheAssetToTheService(): void
    {
        $asset = $this->createMock(Asset::class);
        $classification = new AssetAiClassification($asset, AssetAiClassification::AI_GENERATED);
        $service = $this->createMock(AssetAiClassificationService::class);
        $service->expects(self::once())->method('get')->with($asset)->willReturn($classification);

        self::assertSame($classification, $this->createHelper($service)->get($asset));
    }

    /**
     * @test
     */
    public function getClassificationPassesTheAssetToTheService(): void
    {
        $asset = $this->createMock(Asset::class);
        $service = $this->createMock(AssetAiClassificationService::class);
        $service->expects(self::once())->method('getClassification')->with($asset)
            ->willReturn(AssetAiClassification::AI_MODIFIED);

        self::assertSame(AssetAiClassification::AI_MODIFIED, $this->createHelper($service)->getClassification($asset));
    }

    /**
     * @test
     */
    public function onlyReadingMethodsCanBeCalledFromEel(): void
    {
        $helper = $this->createHelper($this->createMock(AssetAiClassificationService::class));

        self::assertTrue($helper->allowsCallOfMethod('get'));
        self::assertTrue($helper->allowsCallOfMethod('getClassification'));
        self::assertTrue($helper->allowsCallOfMethod('isAiGenerated'));
        self::assertTrue($helper->allowsCallOfMethod('isAiModified'));
        self::assertFalse($helper->allowsCallOfMethod('setClassification'));
    }

    private function createHelper(AssetAiClassificationService $service): AssetAiClassificationHelper
    {
        $helper = new AssetAiClassificationHelper();
        $this->inject($helper, 'classificationService', $service);

        return $helper;
    }
}
