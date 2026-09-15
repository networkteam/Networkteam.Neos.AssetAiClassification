<?php

namespace Networkteam\Neos\AssetAiClassification\Domain\Service;

/*
 * This file is part of the Networkteam.Neos.AssetAiClassification package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Service\AssetService;
use Networkteam\Neos\AssetAiClassification\Domain\Model\AssetAiClassification;
use Networkteam\Neos\AssetAiClassification\Domain\Repository\AssetAiClassificationRepository;

/**
 * Central access to the AI classification of assets.
 *
 * Image variants are resolved to their original asset for both reading and writing.
 *
 * @Flow\Scope("singleton")
 */
class AssetAiClassificationService
{
    /**
     * @Flow\Inject
     * @var AssetAiClassificationRepository
     */
    protected $classificationRepository;

    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Classification values by asset identifier.
     *
     * Deliberately caches the value and not the entity: this service is a singleton, so cached entities would
     * survive a persistenceManager->clearState() in long running CLI processes and become detached.
     *
     * @var array<string, string>
     */
    private array $classificationCache = [];

    /**
     * The classification record of an asset, including pending inserts, null if it is not classified.
     */
    public function get(?AssetInterface $asset): ?AssetAiClassification
    {
        $asset = $this->resolveOriginalAsset($asset);

        return $asset === null ? null : $this->classificationRepository->findOneByAsset($asset);
    }

    /**
     * One of the AssetAiClassification constants, WITHOUT_AI for unclassified assets
     */
    public function getClassification(?AssetInterface $asset): string
    {
        $asset = $this->resolveOriginalAsset($asset);
        if ($asset === null) {
            return AssetAiClassification::WITHOUT_AI;
        }

        $cacheKey = $this->getCacheKey($asset);
        if ($cacheKey === null) {
            return $this->readClassification($asset);
        }

        if (!isset($this->classificationCache[$cacheKey])) {
            $this->classificationCache[$cacheKey] = $this->readClassification($asset);
        }

        return $this->classificationCache[$cacheKey];
    }

    public function isAiGenerated(?AssetInterface $asset): bool
    {
        return $this->getClassification($asset) === AssetAiClassification::AI_GENERATED;
    }

    public function isAiModified(?AssetInterface $asset): bool
    {
        return $this->getClassification($asset) === AssetAiClassification::AI_MODIFIED;
    }

    /**
     * Classifies the given asset, removing the record again when it is reset to WITHOUT_AI
     *
     * @param bool $emitAssetUpdated whether to emit the assetUpdated signal, so content using the asset is
     *                               invalidated. Disable it if the caller updates the asset anyway.
     * @throws \InvalidArgumentException if the given classification is unknown
     */
    public function setClassification(Asset $asset, string $classification, bool $emitAssetUpdated = true): void
    {
        $asset = $this->resolveOriginalAsset($asset);
        if ($asset === null) {
            return;
        }

        $existingClassification = $this->get($asset);

        if ($existingClassification === null) {
            if ($classification === AssetAiClassification::WITHOUT_AI) {
                return;
            }

            $this->classificationRepository->add(new AssetAiClassification($asset, $classification));
        } elseif ($existingClassification->getClassification() === $classification) {
            return;
        } elseif ($classification === AssetAiClassification::WITHOUT_AI) {
            $this->classificationRepository->remove($existingClassification);
        } else {
            $existingClassification->setClassification($classification);
            $this->classificationRepository->update($existingClassification);
        }

        $cacheKey = $this->getCacheKey($asset);
        if ($cacheKey !== null) {
            $this->classificationCache[$cacheKey] = $classification;
        }

        if ($emitAssetUpdated) {
            $this->assetService->emitAssetUpdated($asset);
        }
    }

    private function resolveOriginalAsset(?AssetInterface $asset): ?Asset
    {
        if ($asset instanceof AssetVariantInterface) {
            $asset = $asset->getOriginalAsset();
        }

        return $asset instanceof Asset ? $asset : null;
    }

    private function readClassification(Asset $asset): string
    {
        return $this->classificationRepository->findOneByAsset($asset)?->getClassification()
            ?? AssetAiClassification::WITHOUT_AI;
    }

    private function getCacheKey(Asset $asset): ?string
    {
        $identifier = $this->persistenceManager->getIdentifierByObject($asset);

        return is_string($identifier) ? $identifier : null;
    }
}
