<?php

namespace Networkteam\Neos\AssetAiClassification\EelHelper;

/*
 * This file is part of the Networkteam.Neos.AssetAiClassification package.
 */

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\AssetInterface;
use Networkteam\Neos\AssetAiClassification\Domain\Model\AssetAiClassification;
use Networkteam\Neos\AssetAiClassification\Domain\Service\AssetAiClassificationService;

/**
 * Read access to the AI classification of assets from Fusion.
 *
 * Accepts assets and image variants, variants are resolved to their original asset.
 */
class AssetAiClassificationHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var AssetAiClassificationService
     */
    protected $classificationService;

    public function get(?AssetInterface $asset): ?AssetAiClassification
    {
        return $this->classificationService->get($asset);
    }

    /**
     * One of "none", "generated" or "modified"
     */
    public function getClassification(?AssetInterface $asset): string
    {
        return $this->classificationService->getClassification($asset);
    }

    public function isAiGenerated(?AssetInterface $asset): bool
    {
        return $this->classificationService->isAiGenerated($asset);
    }

    public function isAiModified(?AssetInterface $asset): bool
    {
        return $this->classificationService->isAiModified($asset);
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return in_array($methodName, ['get', 'getClassification', 'isAiGenerated', 'isAiModified'], true);
    }
}
