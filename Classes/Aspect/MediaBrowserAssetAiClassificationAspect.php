<?php

namespace Networkteam\Neos\AssetAiClassification\Aspect;

/*
 * This file is part of the Networkteam.Neos.AssetAiClassification package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Media\Browser\Controller\AssetController;
use Neos\Media\Domain\Model\Asset;
use Networkteam\Neos\AssetAiClassification\Domain\Model\AssetAiClassification;
use Networkteam\Neos\AssetAiClassification\Domain\Service\AssetAiClassificationService;

/**
 * Persists the AI classification submitted by the Media Browser asset edit form.
 *
 * The form field itself is added by the overridden asset edit template, see Configuration/Views.yaml.
 *
 * @Flow\Aspect
 */
class MediaBrowserAssetAiClassificationAspect
{
    private const AI_CLASSIFICATION_ARGUMENT_NAME = 'assetAiClassification';

    /**
     * @Flow\Inject
     * @var AssetAiClassificationService
     */
    protected $classificationService;

    /**
     * Flow pointcuts do not match subclasses, so ImageController has to be listed explicitly
     *
     * @Flow\Before("method(Neos\Media\Browser\Controller\AssetController->updateAction()) || method(Neos\Media\Browser\Controller\ImageController->updateAction())")
     */
    public function persistAiClassification(JoinPointInterface $joinPoint): void
    {
        $controller = $joinPoint->getProxy();
        if (!$controller instanceof AssetController) {
            return;
        }

        $asset = $joinPoint->getMethodArgument('asset');
        if (!$asset instanceof Asset) {
            return;
        }

        $request = $controller->getControllerContext()->getRequest();
        if (!$request->hasArgument(self::AI_CLASSIFICATION_ARGUMENT_NAME)) {
            return;
        }

        $classification = $request->getArgument(self::AI_CLASSIFICATION_ARGUMENT_NAME);
        if (!is_string($classification) || !AssetAiClassification::isValidClassification($classification)) {
            return;
        }

        // The controller updates the asset right afterwards and emits the assetUpdated signal itself
        $this->classificationService->setClassification($asset, $classification, false);
    }
}
