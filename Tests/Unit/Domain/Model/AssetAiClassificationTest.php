<?php

namespace Networkteam\Neos\AssetAiClassification\Tests\Unit\Domain\Model;

use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Asset;
use Networkteam\Neos\AssetAiClassification\Domain\Model\AssetAiClassification;

class AssetAiClassificationTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider classifications
     */
    public function classificationIsExclusive(
        string $classification,
        bool $expectedAiGenerated,
        bool $expectedAiModified
    ): void {
        $classificationEntity = new AssetAiClassification($this->createMock(Asset::class), $classification);

        self::assertSame($classification, $classificationEntity->getClassification());
        self::assertSame($expectedAiGenerated, $classificationEntity->isAiGenerated());
        self::assertSame($expectedAiModified, $classificationEntity->isAiModified());
    }

    /**
     * @return array<string, array{string, bool, bool}>
     */
    public static function classifications(): array
    {
        return [
            'without AI' => [AssetAiClassification::WITHOUT_AI, false, false],
            'AI generated' => [AssetAiClassification::AI_GENERATED, true, false],
            'AI modified' => [AssetAiClassification::AI_MODIFIED, false, true],
        ];
    }

    /**
     * @test
     */
    public function constructorRejectsUnknownClassification(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AssetAiClassification($this->createMock(Asset::class), 'unknown');
    }

    /**
     * @test
     */
    public function setClassificationRejectsUnknownClassification(): void
    {
        $classification = new AssetAiClassification(
            $this->createMock(Asset::class),
            AssetAiClassification::AI_GENERATED
        );

        $this->expectException(\InvalidArgumentException::class);

        $classification->setClassification('unknown');
    }

    /**
     * @test
     */
    public function setClassificationReplacesTheCurrentClassification(): void
    {
        $classification = new AssetAiClassification(
            $this->createMock(Asset::class),
            AssetAiClassification::AI_GENERATED
        );

        $classification->setClassification(AssetAiClassification::AI_MODIFIED);

        self::assertFalse($classification->isAiGenerated());
        self::assertTrue($classification->isAiModified());
    }
}
