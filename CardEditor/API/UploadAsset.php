<?php
include_once('AuthoringEndpoint.php');

ce_json_headers();

try {
    ce_require_method('POST');
    $gameId = (int)($_POST['gameId'] ?? 0);
    if ($gameId <= 0) throw new Exception("Missing gameId parameter");
    if (!isset($_FILES['asset']) || $_FILES['asset']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload failed");
    }

    $file = $_FILES['asset'];
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo || empty($allowed[$imageInfo['mime']])) {
        throw new Exception("Unsupported image type");
    }

    $conn = GetLocalMySQLConnection();
    $db = new CardAuthoringDB($conn);
    $game = $db->getGame($gameId);
    $assetUuid = CardAuthoringDB::uuidv4();
    $extension = $allowed[$imageInfo['mime']];
    $relativeDir = 'Assets/' . $game['game_uuid'] . '/images';
    $absoluteDir = dirname(__DIR__) . '/' . $relativeDir;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true)) {
        throw new Exception("Could not create asset directory");
    }

    $relativePath = $relativeDir . '/' . $assetUuid . '.' . $extension;
    $absolutePath = dirname(__DIR__) . '/' . $relativePath;
    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        throw new Exception("Could not store uploaded file");
    }

    $asset = $db->createAsset([
        'gameId' => $gameId,
        'assetUuid' => $assetUuid,
        'assetKind' => 'image',
        'originalFilename' => basename($file['name']),
        'mimeType' => $imageInfo['mime'],
        'extension' => $extension,
        'relativePath' => $relativePath,
        'width' => (int)$imageInfo[0],
        'height' => (int)$imageInfo[1],
        'fileSize' => (int)$file['size']
    ]);

    ce_success($asset);
    mysqli_close($conn);
} catch (Exception $e) {
    ce_error($e->getMessage());
}

?>
