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
single `f:render` line. Diff it against the upstream template of the new Neos version and re-apply that line.
That copy is what ties the package to a Neos version, not the PHP code — the `neos/*` constraints in
`composer.json` have to match the Neos version the template was copied from.
