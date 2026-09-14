<?php

namespace Networkteam\Neos\AssetAiClassification\Domain\Model;

/*
 * This file is part of the Networkteam.Neos.AssetAiClassification package.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\Asset;

/**
 * The AI classification of an asset.
 *
 * The classification is exclusive: an asset is either not AI assisted at all, AI generated or AI modified.
 * A record only exists for classified assets, an asset without a record counts as WITHOUT_AI.
 *
 * @Flow\Entity
 * @ORM\Table(name="networkteam_neos_assetaiclassification")
 */
class AssetAiClassification
{
    public const WITHOUT_AI = 'none';
    public const AI_GENERATED = 'generated';
    public const AI_MODIFIED = 'modified';

    /**
     * @var Asset
     * @ORM\OneToOne
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected Asset $asset;

    /**
     * @var string
     * @ORM\Column(length=20)
     */
    protected string $classification;

    public function __construct(Asset $asset, string $classification)
    {
        $this->asset = $asset;
        $this->setClassification($classification);
    }

    /**
     * @return string[]
     */
    public static function getAvailableClassifications(): array
    {
        return [self::WITHOUT_AI, self::AI_GENERATED, self::AI_MODIFIED];
    }

    public static function isValidClassification(string $classification): bool
    {
        return in_array($classification, self::getAvailableClassifications(), true);
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function getClassification(): string
    {
        return $this->classification;
    }

    /**
     * @throws \InvalidArgumentException if the given classification is unknown
     */
    public function setClassification(string $classification): void
    {
        if (!self::isValidClassification($classification)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a known AI classification', $classification),
                1789084801
            );
        }

        $this->classification = $classification;
    }

    public function isAiGenerated(): bool
    {
        return $this->classification === self::AI_GENERATED;
    }

    public function isAiModified(): bool
    {
        return $this->classification === self::AI_MODIFIED;
    }
}
