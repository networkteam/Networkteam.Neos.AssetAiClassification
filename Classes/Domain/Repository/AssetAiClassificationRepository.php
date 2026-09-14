<?php

namespace Networkteam\Neos\AssetAiClassification\Domain\Repository;

/*
 * This file is part of the Networkteam.Neos.AssetAiClassification package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;
use Neos\Media\Domain\Model\Asset;
use Networkteam\Neos\AssetAiClassification\Domain\Model\AssetAiClassification;

/**
 * @Flow\Scope("singleton")
 */
class AssetAiClassificationRepository extends Repository
{
    public function findOneByAsset(Asset $asset): ?AssetAiClassification
    {
        $query = $this->createQuery();
        $query->matching($query->equals('asset', $asset));

        $classification = $query->execute()->getFirst();

        return $classification instanceof AssetAiClassification ? $classification : null;
    }
}
