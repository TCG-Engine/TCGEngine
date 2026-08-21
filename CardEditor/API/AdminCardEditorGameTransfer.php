<?php

// Admin endpoint behind the Generator Workspace "CardEditor game" panel.
//
// Exports ONE authored game — its 12 ce_* tables, optionally with its uploaded asset files — as a
// zip, and imports one back. CardEditorGameBundle owns the format and every structural rule; this
// file owns only the SQL, because keeping the bundle layer database-free is what makes it testable
// (DevTools/tdd-regression/test_card_editor_game_bundle.php).
//
// Unlike AdminCardAbilityTransfer.php this is NOT app-scoped: the ce_* tables are keyed by game,
// not by root name, and there is no mapping between the two. The panel therefore picks a game.

// Bundling and re-writing a game's asset files runs well past the default 30s for a large corpus.
set_time_limit(10800);

include_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../Database/ConnectionManager.php';
include_once __DIR__ . '/../Database/CardEditorGameBundle.php';

$authError = CheckLoggedInUserMod();
if ($authError !== '') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => $authError]);
    exit;
}

// Every ce_* table scoped to one game. Ordered by id so an export is stable run to run, which in
// turn is what lets a re-export be diffed against an earlier bundle.
const CE_SCOPED_SELECTS = [
    'ce_games' => 'SELECT * FROM ce_games WHERE id = ? ORDER BY id ASC',
    'ce_assets' => 'SELECT * FROM ce_assets WHERE game_id = ? ORDER BY id ASC',
    'ce_sets' => 'SELECT * FROM ce_sets WHERE game_id = ? ORDER BY id ASC',
    'ce_templates' => 'SELECT * FROM ce_templates WHERE game_id = ? ORDER BY id ASC',
    'ce_template_fields' => 'SELECT f.* FROM ce_template_fields f JOIN ce_templates t ON t.id = f.template_id WHERE t.game_id = ? ORDER BY f.id ASC',
    'ce_template_layout_elements' => 'SELECT e.* FROM ce_template_layout_elements e JOIN ce_templates t ON t.id = e.template_id WHERE t.game_id = ? ORDER BY e.id ASC',
    'ce_game_tags' => 'SELECT * FROM ce_game_tags WHERE game_id = ? ORDER BY id ASC',
    'ce_game_enums' => 'SELECT * FROM ce_game_enums WHERE game_id = ? ORDER BY id ASC',
    'ce_game_enum_options' => 'SELECT o.* FROM ce_game_enum_options o JOIN ce_game_enums n ON n.id = o.enum_id WHERE n.game_id = ? ORDER BY o.id ASC',
    'ce_cards' => 'SELECT * FROM ce_cards WHERE game_id = ? ORDER BY id ASC',
    'ce_card_field_values' => 'SELECT v.* FROM ce_card_field_values v JOIN ce_cards c ON c.id = v.card_id WHERE c.game_id = ? ORDER BY v.id ASC',
    'ce_card_tags' => 'SELECT ct.* FROM ce_card_tags ct JOIN ce_cards c ON c.id = ct.card_id WHERE c.game_id = ? ORDER BY ct.card_id ASC, ct.tag_id ASC',
];

// The same scoping, as deletes. Applied in REVERSE table order when an import replaces a game that
// is already here. These tables carry no foreign keys, so nothing cascades — every table has to be
// named explicitly or its rows are orphaned rather than removed.
const CE_SCOPED_DELETES = [
    'ce_games' => 'DELETE FROM ce_games WHERE id = ?',
    'ce_assets' => 'DELETE FROM ce_assets WHERE game_id = ?',
    'ce_sets' => 'DELETE FROM ce_sets WHERE game_id = ?',
    'ce_templates' => 'DELETE FROM ce_templates WHERE game_id = ?',
    'ce_template_fields' => 'DELETE f FROM ce_template_fields f JOIN ce_templates t ON t.id = f.template_id WHERE t.game_id = ?',
    'ce_template_layout_elements' => 'DELETE e FROM ce_template_layout_elements e JOIN ce_templates t ON t.id = e.template_id WHERE t.game_id = ?',
    'ce_game_tags' => 'DELETE FROM ce_game_tags WHERE game_id = ?',
    'ce_game_enums' => 'DELETE FROM ce_game_enums WHERE game_id = ?',
    'ce_game_enum_options' => 'DELETE o FROM ce_game_enum_options o JOIN ce_game_enums n ON n.id = o.enum_id WHERE n.game_id = ?',
    'ce_cards' => 'DELETE FROM ce_cards WHERE game_id = ?',
    'ce_card_field_values' => 'DELETE v FROM ce_card_field_values v JOIN ce_cards c ON c.id = v.card_id WHERE c.game_id = ?',
    'ce_card_tags' => 'DELETE ct FROM ce_card_tags ct JOIN ce_cards c ON c.id = ct.card_id WHERE c.game_id = ?',
];

// relative_path is stored relative to CardEditor/ (see CardEditor/API/UploadAsset.php).
const CE_ASSET_BASE_DIRECTORY = __DIR__ . '/..';

function CeOpenConnection()
{
    $conn = GetLocalMySQLConnection();
    if (!$conn) throw new RuntimeException('Database connection failed');
    return $conn;
}

// The ce_* tables ship as a separate schema file, so a database that has never run the CardEditor
// simply does not have them. Say that plainly instead of surfacing a raw "table doesn't exist".
function CeAssertSchema($conn): void
{
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'ce_games'");
    $exists = $result && mysqli_num_rows($result) > 0;
    if ($result) mysqli_free_result($result);
    if (!$exists) {
        throw new InvalidArgumentException(
            'This database has no CardEditor tables yet. Run "Set up database" first, or import '
            . 'CardEditor/Database/card_authoring_schema.sql.'
        );
    }
}

function CeQuery($conn, string $sql, int $gameId): array
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new RuntimeException('Could not prepare: ' . mysqli_error($conn));
    mysqli_stmt_bind_param($stmt, 'i', $gameId);
    if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Could not read CardEditor rows');
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_stmt_close($stmt);
    return $rows;
}

function CeExecute($conn, string $sql, int $gameId): int
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new RuntimeException('Could not prepare: ' . mysqli_error($conn));
    mysqli_stmt_bind_param($stmt, 'i', $gameId);
    if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Could not clear CardEditor rows');
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return (int)$affected;
}

$action = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : '';

try {
    // ------------------------------------------------------------------ games (dropdown)
    if ($action === 'games') {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') throw new InvalidArgumentException('Listing games requires GET');
        $conn = CeOpenConnection();
        try {
            CeAssertSchema($conn);
            $result = mysqli_query($conn, "
                SELECT g.id, g.game_uuid, g.name, g.slug,
                       (SELECT COUNT(*) FROM ce_cards c WHERE c.game_id = g.id) AS card_count,
                       (SELECT COUNT(*) FROM ce_assets a WHERE a.game_id = g.id) AS asset_count
                FROM ce_games g
                ORDER BY g.name ASC, g.id ASC
            ");
            if (!$result) throw new RuntimeException('Could not list CardEditor games');
            $games = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $games[] = [
                    'id' => (int)$row['id'],
                    'gameUuid' => (string)$row['game_uuid'],
                    'name' => (string)$row['name'],
                    'slug' => (string)$row['slug'],
                    'cardCount' => (int)$row['card_count'],
                    'assetCount' => (int)$row['asset_count'],
                ];
            }
            mysqli_free_result($result);
        } finally {
            mysqli_close($conn);
        }

        header('Content-Type: application/json');
        echo json_encode(['games' => $games]);
        exit;
    }

    // ------------------------------------------------------------------------- export
    if ($action === 'export') {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') throw new InvalidArgumentException('Export requires GET');
        $gameId = isset($_GET['gameId']) ? (int)$_GET['gameId'] : 0;
        if ($gameId < 1) throw new InvalidArgumentException('Choose a game to export');

        $conn = CeOpenConnection();
        try {
            CeAssertSchema($conn);
            $rowsByTable = [];
            foreach (CardEditorGameBundle::tableOrder() as $table) {
                $rowsByTable[$table] = CeQuery($conn, CE_SCOPED_SELECTS[$table], $gameId);
            }
        } finally {
            mysqli_close($conn);
        }

        if (count($rowsByTable['ce_games']) !== 1) {
            throw new InvalidArgumentException('That game no longer exists — reload the page and pick another');
        }
        $game = $rowsByTable['ce_games'][0];

        // Assets are opt-in: they are by far the biggest part of a game, and are only worth moving
        // when the receiving machine cannot get the images any other way.
        $assetFiles = [];
        if (!empty($_GET['includeAssets'])) {
            $assetBytes = 0;
            foreach ($rowsByTable['ce_assets'] as $asset) {
                $relativePath = (string)$asset['relative_path'];
                $absolutePath = CE_ASSET_BASE_DIRECTORY . '/' . $relativePath;
                if (!is_file($absolutePath)) continue;
                $assetFiles[$relativePath] = $absolutePath;
                $assetBytes += (int)filesize($absolutePath);
            }
            if (!$assetFiles) {
                throw new InvalidArgumentException($game['name'] . ' has no asset files on disk to bundle');
            }
            if ($assetBytes > CardEditorGameBundle::MAX_ARCHIVE_BYTES) {
                throw new InvalidArgumentException(sprintf(
                    "%s's assets are %d MB, over the %d MB transfer limit. Export without assets and move the files another way.",
                    $game['name'],
                    (int)round($assetBytes / 1024 / 1024),
                    (int)(CardEditorGameBundle::MAX_ARCHIVE_BYTES / 1024 / 1024)
                ));
            }
        }

        $archive = CardEditorGameBundle::export($rowsByTable, gmdate('Y-m-d\TH:i:s\Z'), $assetFiles);

        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)$game['slug']) ?: 'game';
        $fileName = 'cardeditor-' . $slug . ($assetFiles ? '-with-assets' : '') . '-' . gmdate('Y-m-d-His') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($archive));
        header('X-Content-Type-Options: nosniff');
        echo $archive;
        exit;
    }

    // ------------------------------------------------------------------------- import
    if ($action === 'import') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new InvalidArgumentException('Import requires POST');

        // PHP silently discards the ENTIRE body — $_POST and $_FILES both — when it exceeds
        // post_max_size. Without this check an oversized asset bundle fails the CSRF test below and
        // reports a bogus "invalid security token" instead of the real problem.
        if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            throw new InvalidArgumentException(sprintf(
                'The upload (%d MB) exceeded this server\'s post_max_size of %s. Raise post_max_size and upload_max_filesize in php.ini, or export without assets.',
                (int)round(((int)$_SERVER['CONTENT_LENGTH']) / 1024 / 1024),
                (string)ini_get('post_max_size')
            ));
        }

        CheckSession();
        $sessionToken = isset($_SESSION['generator_admin_csrf']) ? (string)$_SESSION['generator_admin_csrf'] : '';
        $requestToken = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
        if ($sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
            throw new InvalidArgumentException('Invalid import security token; reload the admin page and try again');
        }
        if (!isset($_FILES['bundleFile']) || $_FILES['bundleFile']['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Choose a valid CardEditor game bundle');
        }
        if ((int)$_FILES['bundleFile']['size'] > CardEditorGameBundle::MAX_ARCHIVE_BYTES) {
            throw new InvalidArgumentException(sprintf(
                'Import file exceeds the %d MB limit',
                (int)(CardEditorGameBundle::MAX_ARCHIVE_BYTES / 1024 / 1024)
            ));
        }

        $bundle = CardEditorGameBundle::read($_FILES['bundleFile']['tmp_name'], (string)$_FILES['bundleFile']['name']);
        $tables = $bundle['tables'];
        $gameUuid = (string)$tables['ce_games'][0]['game_uuid'];
        $gameName = (string)$tables['ce_games'][0]['name'];
        $gameSlug = (string)$tables['ce_games'][0]['slug'];

        $conn = CeOpenConnection();
        $replacedGame = false;
        $importedCounts = [];
        try {
            CeAssertSchema($conn);

            // Identity first: the same game_uuid means this is a restore of a game already here, so
            // its subtree is replaced. A DIFFERENT game wearing the same slug is a genuine conflict —
            // ce_games.slug is UNIQUE, so proceeding would blow up mid-transaction with a driver
            // error instead of something the operator can act on.
            $existing = mysqli_prepare($conn, 'SELECT id, name FROM ce_games WHERE game_uuid = ?');
            mysqli_stmt_bind_param($existing, 's', $gameUuid);
            mysqli_stmt_execute($existing);
            $existingRow = mysqli_fetch_assoc(mysqli_stmt_get_result($existing));
            mysqli_stmt_close($existing);

            if (!$existingRow) {
                $clash = mysqli_prepare($conn, 'SELECT name FROM ce_games WHERE slug = ?');
                mysqli_stmt_bind_param($clash, 's', $gameSlug);
                mysqli_stmt_execute($clash);
                $clashRow = mysqli_fetch_assoc(mysqli_stmt_get_result($clash));
                mysqli_stmt_close($clash);
                if ($clashRow) {
                    throw new InvalidArgumentException(
                        'A different game ("' . $clashRow['name'] . '") already uses the slug "' . $gameSlug
                        . '". Rename it in the CardEditor, then import again.'
                    );
                }
            }

            mysqli_begin_transaction($conn);
            try {
                if ($existingRow) {
                    $replacedGame = true;
                    foreach (array_reverse(CardEditorGameBundle::tableOrder()) as $table) {
                        CeExecute($conn, CE_SCOPED_DELETES[$table], (int)$existingRow['id']);
                    }
                }

                // uuid => freshly assigned local id, per table. Built as we go: tableOrder()
                // guarantees a reference target is inserted before anything that points at it.
                $newIds = [];
                foreach (CardEditorGameBundle::tableOrder() as $table) {
                    $columns = CardEditorGameBundle::columnsFor($table);
                    $references = CardEditorGameBundle::referencesFor($table);
                    $uuidColumn = CardEditorGameBundle::uuidColumnFor($table);

                    $sql = 'INSERT INTO ' . $table . ' (`' . implode('`, `', $columns) . '`) VALUES ('
                        . implode(', ', array_fill(0, count($columns), '?')) . ')';
                    $insert = mysqli_prepare($conn, $sql);
                    if (!$insert) throw new RuntimeException('Could not prepare the ' . $table . ' import');

                    foreach ($tables[$table] as $row) {
                        $values = [];
                        foreach ($columns as $column) {
                            $value = $row[$column];
                            if (isset($references[$column])) {
                                // Null survived validation only where the column is nullable.
                                $value = ($value === null || $value === '')
                                    ? null
                                    : $newIds[$references[$column]][(string)$value];
                            }
                            $values[] = $value === null ? null : (string)$value;
                        }
                        // Every column binds as a string; MySQL coerces into the real column type,
                        // and null binds as NULL regardless of the declared type character.
                        mysqli_stmt_bind_param($insert, str_repeat('s', count($values)), ...$values);
                        if (!mysqli_stmt_execute($insert)) {
                            throw new RuntimeException('Could not import a ' . $table . ' row: ' . mysqli_stmt_error($insert));
                        }
                        if ($uuidColumn !== null) {
                            $newIds[$table][(string)$row[$uuidColumn]] = (int)mysqli_insert_id($conn);
                        }
                    }
                    mysqli_stmt_close($insert);
                    $importedCounts[$table] = count($tables[$table]);
                }

                mysqli_commit($conn);
            } catch (Throwable $error) {
                mysqli_rollback($conn);
                throw $error;
            }
        } finally {
            mysqli_close($conn);
        }

        // Files are written only AFTER the rows commit. A failed write leaves a row pointing at a
        // missing image, which re-importing the same bundle repairs; writing first and rolling back
        // would instead overwrite a live game's art with the bytes of an import that never landed.
        // Assets the incoming bundle no longer lists are left on disk rather than deleted — orphan
        // bytes are cheap, and removing a file an operator did not ask about is not.
        $writtenAssets = 0;
        $failedAssets = [];
        foreach ($bundle['assets'] as $relativePath => $contents) {
            $absolutePath = CE_ASSET_BASE_DIRECTORY . '/' . $relativePath;
            $directory = dirname($absolutePath);
            if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                $failedAssets[] = $relativePath;
                continue;
            }
            if (file_put_contents($absolutePath, $contents) === false) {
                $failedAssets[] = $relativePath;
                continue;
            }
            $writtenAssets++;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'gameName' => $gameName,
            'gameUuid' => $gameUuid,
            'replacedExisting' => $replacedGame,
            'importedCounts' => $importedCounts,
            'cardCount' => $importedCounts['ce_cards'] ?? 0,
            'assetsWritten' => $writtenAssets,
            'assetsFailed' => $failedAssets,
        ]);
        exit;
    }

    throw new InvalidArgumentException('Unknown transfer action');
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('AdminCardEditorGameTransfer error: ' . $error->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'CardEditor game transfer failed']);
}
