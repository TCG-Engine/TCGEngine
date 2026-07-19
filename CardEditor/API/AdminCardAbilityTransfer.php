<?php

include_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../Database/ConnectionManager.php';
include_once __DIR__ . '/../Database/CardAbilitySqlTransfer.php';

$authError = CheckLoggedInUserMod();
if ($authError !== '') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => $authError]);
    exit;
}

$action = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : '';
$rootName = isset($_REQUEST['app']) ? (string)$_REQUEST['app'] : '';
if (!preg_match('/^[A-Za-z0-9_-]+$/', $rootName) || !is_dir(__DIR__ . '/../../Schemas/' . $rootName)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid or unknown app']);
    exit;
}

try {
    if ($action === 'export') {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') throw new InvalidArgumentException('Export requires GET');
        $conn = GetLocalMySQLConnection();
        if (!$conn) throw new RuntimeException('Database connection failed');

        $stmt = mysqli_prepare($conn, "
            SELECT root_name, card_id, macro_name, ability_type, ability_code, prereq_code,
                   listener_zones, ability_name, is_implemented
            FROM card_abilities
            WHERE root_name = ?
            ORDER BY card_id ASC, created_at ASC, id ASC
        ");
        if (!$stmt) throw new RuntimeException('Could not prepare card ability export');
        mysqli_stmt_bind_param($stmt, 's', $rootName);
        if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Could not read card abilities');
        $result = mysqli_stmt_get_result($stmt);
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        $fileName = $rootName . '-card-abilities-' . gmdate('Y-m-d-His') . '.sql';
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('X-Content-Type-Options: nosniff');
        echo CardAbilitySqlTransfer::export($rootName, $rows);
        exit;
    }

    if ($action === 'import') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new InvalidArgumentException('Import requires POST');
        CheckSession();
        $sessionToken = isset($_SESSION['generator_admin_csrf']) ? (string)$_SESSION['generator_admin_csrf'] : '';
        $requestToken = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
        if ($sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
            throw new InvalidArgumentException('Invalid import security token; reload the admin page and try again');
        }
        if (!isset($_FILES['sqlFile']) || $_FILES['sqlFile']['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Choose a valid SQL export file');
        }
        if ((int)$_FILES['sqlFile']['size'] > 25 * 1024 * 1024) {
            throw new InvalidArgumentException('Import file exceeds the 25 MB limit');
        }

        $contents = file_get_contents($_FILES['sqlFile']['tmp_name']);
        if ($contents === false) throw new RuntimeException('Could not read the uploaded import file');
        $rows = CardAbilitySqlTransfer::import($contents, $rootName);

        $conn = GetLocalMySQLConnection();
        if (!$conn) throw new RuntimeException('Database connection failed');
        mysqli_begin_transaction($conn);
        try {
            $delete = mysqli_prepare($conn, 'DELETE FROM card_abilities WHERE root_name = ?');
            if (!$delete) throw new RuntimeException('Could not prepare app-scoped replacement');
            mysqli_stmt_bind_param($delete, 's', $rootName);
            if (!mysqli_stmt_execute($delete)) throw new RuntimeException('Could not replace existing app abilities');
            $deletedCount = mysqli_stmt_affected_rows($delete);
            mysqli_stmt_close($delete);

            $insert = mysqli_prepare($conn, "
                INSERT INTO card_abilities
                    (root_name, card_id, macro_name, ability_type, ability_code, prereq_code,
                     listener_zones, ability_name, is_implemented)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$insert) throw new RuntimeException('Could not prepare card ability import');
            foreach ($rows as $row) {
                $rowRoot = $row['root_name'];
                $cardId = $row['card_id'];
                $macroName = $row['macro_name'];
                $abilityType = $row['ability_type'];
                $abilityCode = $row['ability_code'];
                $prereqCode = $row['prereq_code'];
                $listenerZones = $row['listener_zones'];
                $abilityName = $row['ability_name'];
                $isImplemented = $row['is_implemented'];
                mysqli_stmt_bind_param($insert, 'ssssssssi', $rowRoot, $cardId, $macroName, $abilityType, $abilityCode, $prereqCode, $listenerZones, $abilityName, $isImplemented);
                if (!mysqli_stmt_execute($insert)) throw new RuntimeException('Could not import card ' . $cardId);
            }
            mysqli_stmt_close($insert);
            mysqli_commit($conn);
        } catch (Throwable $error) {
            mysqli_rollback($conn);
            throw $error;
        } finally {
            mysqli_close($conn);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'app' => $rootName,
            'importedCount' => count($rows),
            'replacedCount' => $deletedCount,
        ]);
        exit;
    }

    throw new InvalidArgumentException('Unknown transfer action');
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('AdminCardAbilityTransfer error: ' . $error->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Card ability transfer failed']);
}

