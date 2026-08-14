<?php

declare(strict_types=1);

include_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/import-workbook.php';

$authError = CheckLoggedInUserMod();
if ($authError !== '') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "ERROR: {$authError}\n";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new InvalidArgumentException('Workbook import requires POST.');
    }

    CheckSession();
    $sessionToken = (string)($_SESSION['generator_admin_csrf'] ?? '');
    $requestToken = (string)($_POST['csrf'] ?? '');
    if ($sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
        throw new InvalidArgumentException('Invalid import security token; reload the admin page and try again.');
    }

    $hasUpload = isset($_FILES['workbook'])
        && is_array($_FILES['workbook'])
        && intval($_FILES['workbook']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($hasUpload) {
        $upload = $_FILES['workbook'];
        $uploadError = intval($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            $message = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The workbook exceeds the server upload limit (' . ini_get('upload_max_filesize') . ').',
                UPLOAD_ERR_PARTIAL => 'The workbook upload was incomplete; try again.',
                default => 'The workbook upload failed (code ' . $uploadError . ').',
            };
            throw new InvalidArgumentException($message);
        }

        $originalName = (string)($upload['name'] ?? '');
        $temporaryPath = (string)($upload['tmp_name'] ?? '');
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new InvalidArgumentException('The selected file must use the .xlsx extension.');
        }
        if (!is_uploaded_file($temporaryPath)) {
            throw new InvalidArgumentException('The uploaded workbook could not be verified.');
        }
        $signature = file_get_contents($temporaryPath, false, null, 0, 4);
        if (!is_string($signature) || !str_starts_with($signature, 'PK')) {
            throw new InvalidArgumentException('The selected file is not a valid XLSX workbook.');
        }
        $report = importHellbreakWorkbook($temporaryPath, sourceLabel: $originalName);
    } else {
        $report = importHellbreakWorkbook(HELLBREAK_SOURCE_URL, sourceLabel: 'Public Hellbreak OneDrive workbook');
    }
    if (intval($report['cards'] ?? 0) < 1) {
        throw new RuntimeException('The workbook import produced no cards.');
    }
    printHellbreakImportSummary($report);
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    echo 'ERROR: ' . $error->getMessage() . "\n";
} catch (Throwable $error) {
    error_log('Hellbreak workbook admin import failed: ' . $error->getMessage());
    http_response_code(500);
    echo 'ERROR: Hellbreak workbook import failed: ' . $error->getMessage() . "\n";
}
