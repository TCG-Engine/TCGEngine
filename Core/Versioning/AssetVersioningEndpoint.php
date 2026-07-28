<?php

require_once __DIR__ . '/AssetVersioningCapability.php';
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';

function AssetVersioningEndpointRespond($payload, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function AssetVersioningLoadAdapterForRoot($folderPath) {
    $folderPath = trim((string)$folderPath);
    if(!preg_match('/^[A-Za-z0-9_]+$/', $folderPath)) return null;
    $engineRoot = realpath(__DIR__ . '/../..');
    if($engineRoot === false) return null;
    $adapterPath = realpath($engineRoot . '/' . $folderPath . '/Custom/AssetVersioning.php');
    if($adapterPath === false) return null;
    $expectedPrefix = rtrim(str_replace('\\', '/', $engineRoot), '/') . '/';
    $normalizedPath = str_replace('\\', '/', $adapterPath);
    if(!str_starts_with($normalizedPath, $expectedPrefix)) return null;
    require_once $adapterPath;
    return AssetVersioningGetLoadedAdapter();
}

function AssetVersioningRunEndpoint() {
    if(!IsUserLoggedIn()) {
        AssetVersioningEndpointRespond(['success' => false, 'error' => 'You must be logged in.'], 401);
    }

    $request = array_merge($_GET, $_POST);
    $folderPath = (string)($request['folderPath'] ?? '');
    $assetID = intval($request['assetID'] ?? 0);
    $action = strtolower(trim((string)($request['action'] ?? 'list')));
    if($assetID <= 0) {
        AssetVersioningEndpointRespond(['success' => false, 'error' => 'Missing asset.'], 400);
    }

    $adapter = AssetVersioningLoadAdapterForRoot($folderPath);
    if(!AssetVersioningAdapterEnabled($adapter)) {
        AssetVersioningEndpointRespond(['success' => false, 'error' => 'Automatic versioning is not enabled for this app.'], 404);
    }
    if(!AssetVersioningAuthorize($adapter, $assetID, LoggedInUser(), $action)) {
        AssetVersioningEndpointRespond(['success' => false, 'error' => 'You do not have access to this version history.'], 403);
    }

    if($action === 'list') {
        AssetVersioningEndpointRespond([
            'success' => true,
            'versions' => AssetVersioningBuildClientPayload($adapter, $assetID)
        ]);
    }

    if($action === 'delete') {
        if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            AssetVersioningEndpointRespond(['success' => false, 'error' => 'Version deletion requires POST.'], 405);
        }
        $versionID = intval($request['versionID'] ?? 0);
        if($versionID <= 0) {
            AssetVersioningEndpointRespond(['success' => false, 'error' => 'Missing version.'], 400);
        }
        if(!AssetVersioningDeleteVersion($adapter, $assetID, $versionID)) {
            AssetVersioningEndpointRespond(['success' => false, 'error' => 'The version could not be deleted.'], 404);
        }
        AssetVersioningEndpointRespond(['success' => true]);
    }

    if($action === 'rename') {
        if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            AssetVersioningEndpointRespond(['success' => false, 'error' => 'Version renaming requires POST.'], 405);
        }
        $versionID = intval($request['versionID'] ?? 0);
        $versionName = trim((string)($request['versionName'] ?? ''));
        $nameLength = function_exists('mb_strlen')
            ? mb_strlen($versionName, 'UTF-8')
            : strlen($versionName);
        if($versionID <= 0 || $versionName === '' || $nameLength > 255) {
            AssetVersioningEndpointRespond([
                'success' => false,
                'error' => 'Enter a version name between 1 and 255 characters.'
            ], 400);
        }
        if(!AssetVersioningRenameVersion($adapter, $assetID, $versionID, $versionName)) {
            AssetVersioningEndpointRespond(['success' => false, 'error' => 'The version could not be renamed.'], 404);
        }
        AssetVersioningEndpointRespond([
            'success' => true,
            'versionName' => $versionName
        ]);
    }

    AssetVersioningEndpointRespond(['success' => false, 'error' => 'Unsupported versioning action.'], 400);
}

?>
