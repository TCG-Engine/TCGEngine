# Asset auto-versioning

The engine owns canonical hashing, edit distance, parent selection, immutable
snapshots, reparent-on-delete behavior, basic W/L aggregates, API handling, and
the hierarchy UI. Apps opt in with a small adapter.

## App adapter

Create `<RootName>/Custom/AssetVersioning.php` and expose:

```php
function GetAssetVersioningAdapter() {
    return [
        'appKey' => 'StableAppNamespace',
        'assetType' => 1,
        'enabled' => true,
        'snapshot' => function($assetID) {
            return [
                'identities' => ['leader' => 'leader-id'],
                'zones' => ['mainDeck' => ['card-id' => 4]]
            ];
        },
        'applySnapshot' => function($assetID, $playerID, $snapshot) {
            return true;
        },
        'authorize' => function($assetID, $userID, $action) {
            return true;
        },
        'describeItem' => function($itemID) {
            return $itemID;
        }
    ];
}
```

Identity values cost one edit when changed. Each zone is treated as an
order-invariant multiset. One removal paired with one addition costs one
replacement edit.

The adapter may optionally provide `deleteVersionStats($conn, $assetID,
$versionID)` to clean app-specific derived statistics in the same transaction.

## Recording a result

The simulator or result-submission path calls:

```php
$adapter = GetAssetVersioningAdapter();
$version = AssetVersioningRecordResult($conn, $adapter, $assetID, $won);
```

Call this inside the result transaction. A null return means snapshot resolution
or aggregate recording failed.

## Editor integration

From the app's tracked layout include:

```php
require_once __DIR__ . '/../../Core/Versioning/AssetVersioningLayout.php';
RenderAssetVersioningUI('RootName');
```

The shared API loads the root's adapter by convention, applies its authorization
callback, and returns preformatted hierarchy rows. `EngineActionRunner.php`
automatically disables manual creation and handles `auto:<versionID>` loading
when the current root exposes an enabled adapter.

Apps without `Custom/AssetVersioning.php` retain their existing version behavior.
