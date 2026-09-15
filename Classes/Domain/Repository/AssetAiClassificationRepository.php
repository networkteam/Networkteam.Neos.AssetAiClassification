<?php

namespace Networkteam\Neos\AssetAiClassification\Domain\Repository;

/*
 * This file is part of the Networkteam.Neos.AssetAiClassification package.
 */

use Doctrine\ORM\EntityManagerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;
use Neos\Media\Domain\Model\Asset;
use Networkteam\Neos\AssetAiClassification\Domain\Model\AssetAiClassification;

/**
 * @Flow\Scope("singleton")
 */
class AssetAiClassificationRepository extends Repository
{
    /**
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function findOneByAsset(Asset $asset): ?AssetAiClassification
    {
        // Queries cannot see entities queued for insertion until Doctrine flushes them.
        // Consult the current UnitOfWork instead of caching entities across clearState().
        foreach ($this->entityManager->getUnitOfWork()->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof AssetAiClassification && $entity->getAsset() === $asset) {
                return $entity;
            }
        }

        $query = $this->createQuery();
        $query->matching($query->equals('asset', $asset));

        $classification = $query->execute()->getFirst();

        return $classification instanceof AssetAiClassification ? $classification : null;
    }
}
