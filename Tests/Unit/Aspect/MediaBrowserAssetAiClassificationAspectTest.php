<?php

namespace Networkteam\Neos\AssetAiClassification\Tests\Unit\Aspect;

use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Browser\Controller\AssetController;
use Neos\Media\Domain\Model\Asset;
use Networkteam\Neos\AssetAiClassification\Aspect\MediaBrowserAssetAiClassificationAspect;
use Networkteam\Neos\AssetAiClassification\Domain\Model\AssetAiClassification;
use Networkteam\Neos\AssetAiClassification\Domain\Service\AssetAiClassificationService;

class MediaBrowserAssetAiClassificationAspectTest extends UnitTestCase
{
    /**
     * @test
     */
    public function submittedClassificationIsPersisted(): void
    {
        $asset = $this->createMock(Asset::class);
        $service = $this->createMock(AssetAiClassificationService::class);
        // The controller emits assetUpdated itself, so the aspect has to suppress the signal
        $service->expects(self::once())->method('setClassification')
            ->with($asset, AssetAiClassification::AI_MODIFIED, false);

        $this->createAspect($service)->persistAiClassification(
            $this->createJoinPoint($asset, AssetAiClassification::AI_MODIFIED)
        );
    }

    /**
     * @test
     */
    public function unknownClassificationsAreIgnored(): void
    {
        $service = $this->createServiceExpectingNoWrite();

        $this->createAspect($service)->persistAiClassification(
            $this->createJoinPoint($this->createMock(Asset::class), 'unknown')
        );
    }

    /**
     * @test
     */
    public function nonStringClassificationsAreIgnored(): void
    {
        $service = $this->createServiceExpectingNoWrite();

        $this->createAspect($service)->persistAiClassification(
            $this->createJoinPoint($this->createMock(Asset::class), ['generated'])
        );
    }

    /**
     * @test
     */
    public function requestsWithoutTheFormFieldAreIgnored(): void
    {
        $service = $this->createServiceExpectingNoWrite();

        $this->createAspect($service)->persistAiClassification(
            $this->createJoinPoint($this->createMock(Asset::class), null, false)
        );
    }

    /**
     * @test
     */
    public function otherMethodArgumentsThanAssetsAreIgnored(): void
    {
        $service = $this->createServiceExpectingNoWrite();

        $this->createAspect($service)->persistAiClassification(
            $this->createJoinPoint(null, AssetAiClassification::AI_GENERATED)
        );
    }

    private function createServiceExpectingNoWrite(): AssetAiClassificationService
    {
        $service = $this->createMock(AssetAiClassificationService::class);
        $service->expects(self::never())->method('setClassification');

        return $service;
    }

    private function createAspect(
        AssetAiClassificationService $service
    ): MediaBrowserAssetAiClassificationAspect {
        $aspect = new MediaBrowserAssetAiClassificationAspect();
        $this->inject($aspect, 'classificationService', $service);

        return $aspect;
    }

    /**
     * @param mixed $asset the "asset" argument of the advised updateAction()
     * @param mixed $classification the submitted form value
     */
    private function createJoinPoint($asset, $classification, bool $hasArgument = true): JoinPointInterface
    {
        $request = $this->createMock(ActionRequest::class);
        $request->method('hasArgument')->with('assetAiClassification')->willReturn($hasArgument);
        $request->method('getArgument')->with('assetAiClassification')->willReturn($classification);

        $controllerContext = $this->createMock(ControllerContext::class);
        $controllerContext->method('getRequest')->willReturn($request);

        $controller = $this->createMock(AssetController::class);
        $controller->method('getControllerContext')->willReturn($controllerContext);

        $joinPoint = $this->createMock(JoinPointInterface::class);
        $joinPoint->method('getProxy')->willReturn($controller);
        $joinPoint->method('getMethodArgument')->with('asset')->willReturn($asset);

        return $joinPoint;
    }
}
