# Networkteam.Neos.AssetAiClassification

AI classification for local Neos Media assets in Neos Media Browser.

An asset is classified as **Without AI**, **AI generated** or **AI modified**. The classification is exclusive and
belongs to the asset, so all uses and cropped image variants share it.

## Installation

```sh
composer require networkteam/neos-asset-ai-classification
./flow doctrine:migrate
./flow flow:cache:flush
```

The migration covers the Doctrine MySQL 5.7 and MySQL 8 platforms, other database platforms need their own.

## Usage

Fusion:

```fusion
aiClassification = ${AssetAiClassification.getClassification(q(node).property('image'))}

aiGenerated = ${AssetAiClassification.isAiGenerated(q(node).property('image'))}

aiModified = ${AssetAiClassification.isAiModified(q(node).property('image'))}
```

Fluid:

```html
{namespace ai=Networkteam\Neos\AssetAiClassification\ViewHelpers}
<ai:classification asset="{asset}" />
```

PHP, via `Domain\Service\AssetAiClassificationService`:

```php
$service->getClassification($asset); // 'none' | 'generated' | 'modified'
$service->setClassification($asset, AssetAiClassification::AI_GENERATED);
```

All read methods accept an asset, an image variant or `null`. Writing emits Neos Media's `assetUpdated` signal,
pass `false` as third argument to suppress it.

## Supporting a new Neos version

`Resources/Private/Templates/Asset/Edit.html` is a copy of the Media Browser template and differs from it by a
single `f:render` line. That copy is what ties the package to a Neos version, not the PHP code. The Media Browser
template is the same in Neos 8.4 and 9.2, so one copy serves both.

The CI workflow checks this for every Neos version in its matrix: it diffs the copy, minus the `f:render` line,
against the installed upstream template. To support a new Neos version, add it to the matrix and widen the
`neos/*` constraints in `composer.json`. If the template check fails, the upstream template has changed and a
single copy can no longer serve all supported versions.
