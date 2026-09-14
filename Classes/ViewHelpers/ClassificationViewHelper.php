<?php

namespace Networkteam\Neos\AssetAiClassification\ViewHelpers;

/*
 * This file is part of the Networkteam.Neos.AssetAiClassification package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Media\Domain\Model\AssetInterface;
use Networkteam\Neos\AssetAiClassification\Domain\Service\AssetAiClassificationService;

/**
 * Renders the AI classification of an asset, e.g. "generated".
 *
 * <code>
 * <ai:classification asset="{assetProxy.asset}" />
 * </code>
 *
 * Renders "none" for unclassified assets and for assets that are not persisted locally.
 */
class ClassificationViewHelper extends AbstractViewHelper
{
    /**
     * @Flow\Inject
     * @var AssetAiClassificationService
     */
    protected $classificationService;

    public function initializeArguments(): void
    {
        $this->registerArgument('asset', AssetInterface::class, 'The asset to render the classification of');
    }

    public function render(): string
    {
        $asset = $this->arguments['asset'];

        return $this->classificationService->getClassification($asset instanceof AssetInterface ? $asset : null);
    }
}
