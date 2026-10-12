<?php
// Real permissions, token lifecycle, and card CRUD in a new disposable database.
// Does not select or mutate any existing application database.
require_once __DIR__ . '/../../CardEditor/Mcp/HttpServer.php';
require_once __DIR__ . '/../../CardEditor/Database/CardAuthoringDB.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('MYSQL_SERVER_NAME') ?: 'localhost';
$user = getenv('MYSQL_SERVER_USER_NAME') ?: 'root';
$password = getenv('MYSQL_ROOT_PASSWORD') ?: '';
$scratch = 'tcgengine_card_editing_test_' . bin2hex(random_bytes(6));
$server = null;
$created = false;
$conn = null;
$fails = 0;
$checks = 0;
$uploaded = null;
$assetDirectory = null;
function editing_db_check($condition, $label) {
    global $fails, $checks;
    ++$checks;
    echo ($condition ? 'PASS' : 'FAIL') . ': ' . $label . "\n";
    if (!$condition) ++$fails;
}
function editing_db_rejects($callback): bool {
    try { $callback(); return false; } catch (Exception $e) { return true; }
}
try {
    $server = mysqli_connect($host, $user, $password);
    mysqli_query($server, "CREATE DATABASE `$scratch`");
    $created = true;
    $conn = mysqli_connect($host, $user, $password, $scratch);
    mysqli_query($conn, 'CREATE TABLE users (usersId BIGINT PRIMARY KEY, teamID BIGINT NULL)');
    mysqli_query($conn, 'INSERT INTO users VALUES (1, 10), (2, 20), (3, 10)');
    mysqli_query($conn, 'CREATE TABLE ownership (assetType INT, assetIdentifier BIGINT, assetOwner BIGINT, assetStatus INT, assetName VARCHAR(255), assetVisibility INT)');
    $tokens = new CardEditingTokens($conn);
    $issued = $tokens->create(1, 'Owner', 'write', 1);
    $reader = $tokens->create(1, 'Reader', 'read', 1);
    editing_db_check($tokens->authenticate($issued['token']) === ['userId' => 1, 'scope' => 'write'], 'token resolves real user identity');
    $serialized = json_encode($tokens->listForUser(1));
    editing_db_check(strpos($serialized, $issued['token']) === false && strpos($serialized, 'token_hash') === false, 'token listing contains no credentials');
    $tokens->revoke(2, $issued['id']);
    editing_db_check($tokens->authenticate($issued['token'])['userId'] === 1, 'another user cannot revoke token');
    $owner = new CardAuthoringDB($conn, 1);
    $other = new CardAuthoringDB($conn, 2);
    $teammate = new CardAuthoringDB($conn, 3);
    // Browser session is deliberately someone else; explicit token identity must win.
    $_SESSION = ['userid' => 2, 'useruid' => 'other'];
    $game = $owner->createGame(['name' => 'Private MCP game']);
    editing_db_check($game['ownership']['ownerId'] === 1, 'token user owns game despite unrelated browser session');
    editing_db_check(editing_db_rejects(function() use ($other, $game) { $other->getGame($game['id']); }), 'private game hidden from other account');
    editing_db_check(count($other->listGames()) === 0, 'private game absent from discovery');
    $set = $owner->createSet(['gameId' => $game['id'], 'name' => 'Set']);
    $template = $owner->createTemplate(['gameId' => $game['id'], 'name' => 'Unit']);
    $template = $owner->saveTemplateFields($template['id'], [['label' => 'Rules', 'fieldType' => 'longtext'], ['label' => 'Power', 'fieldType' => 'number']], $template['updated_at']);
    $card = $owner->createCard(['gameId' => $game['id'], 'setId' => $set['id'], 'templateId' => $template['id'], 'name' => 'Sample']);
    $card = CardEditingTools::call($owner, 'write', 'save_card_fields', json_decode(json_encode(['cardId' => $card['id'], 'expectedUpdatedAt' => $card['updated_at'], 'values' => [['fieldId' => $template['fields'][0]['id'], 'value' => 'Draw a card.']]])));
    editing_db_check($card['values'][0]['value_text'] === 'Draw a card.', 'MCP field save persists rules text');
    $call = function($name, $args) use ($owner) { return CardEditingTools::call($owner, 'write', $name, json_decode(json_encode($args))); };
    $template = $call('save_template_layout', ['templateId' => $template['id'], 'expectedUpdatedAt' => $template['updated_at'],
        'layout' => [['element_type' => 'field', 'field_id' => $template['fields'][0]['id'], 'x' => 20, 'y' => 30, 'width' => 200, 'height' => 80, 'styleJson' => (object)['fontSize' => '18px']]]]);
    editing_db_check(count($template['layout']) === 1, 'MCP layout save persists field geometry');
    $clone = $call('duplicate_template', ['id' => $template['id'], 'name' => 'Cloned unit']);
    editing_db_check(count($clone['fields']) === 2 && count($clone['layout']) === 1, 'MCP template duplication includes fields and layout');
    $call('update_set', ['id' => $set['id'], 'name' => 'Updated set', 'expectedUpdatedAt' => $set['updated_at']]);
    editing_db_check($owner->getSet($set['id'])['name'] === 'Updated set', 'MCP updates set metadata');
    $uploaded = $call('upload_asset', ['gameId' => $game['id'], 'filename' => '../art.png',
        'base64' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aPioAAAAASUVORK5CYII=']);
    $assetDirectory = dirname(__DIR__, 2) . '/CardEditor/Assets/' . $game['game_uuid'] . '/images';
    editing_db_check($uploaded['mime_type'] === 'image/png' && $uploaded['original_filename'] === 'art.png' && is_file(dirname(__DIR__, 2) . '/CardEditor/' . $uploaded['relative_path']), 'MCP uploads image under generated path with sanitized filename');
    editing_db_check(editing_db_rejects(function() use ($call, $game) { $call('upload_asset', ['gameId' => $game['id'], 'filename' => 'bad.png', 'base64' => base64_encode('<?php echo 1;')]); }), 'non-image content cannot be uploaded');
    $call('delete_asset', ['id' => $uploaded['id']]);
    $uploaded = null;
    editing_db_check(count($owner->listAssets($game['id'])) === 0, 'MCP asset deletion removes stored asset');
    editing_db_check(editing_db_rejects(function() use ($owner, $card) { $owner->saveCardFieldValues($card['id'], [], '2000-01-01 00:00:00'); }), 'stale field save rejected');
    editing_db_check(editing_db_rejects(function() use ($other, $card) { $other->updateCard($card['id'], ['name' => 'No']); }), 'other account cannot edit private card');
    $team = $owner->createGame(['name' => 'Team game', 'visibility' => 'team']);
    editing_db_check($teammate->getGame($team['id'])['can_edit'], 'team membership grants existing editor permissions');
    editing_db_check(editing_db_rejects(function() use ($other, $team) { $other->getGame($team['id']); }), 'another team cannot access game');
    mysqli_query($conn, 'UPDATE ownership SET assetVisibility = 2 WHERE assetIdentifier = ' . $game['id']);
    editing_db_check($other->getCard($card['id'])['name'] === 'Sample', 'public cards readable by other accounts');
    editing_db_check(editing_db_rejects(function() use ($other, $card) { $other->deleteCard($card['id']); }), 'public visibility does not grant delete access');
    $rejected = CardEditingHttpServer::handle(['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json,text/event-stream', 'HTTP_AUTHORIZATION' => 'Bearer ' . $reader['token']],
        '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"delete_card","arguments":{"id":' . $card['id'] . '}}}',
        function($secret) use ($tokens) { return $tokens->authenticate($secret); }, function($id) use ($conn) { return new CardAuthoringDB($conn, $id); });
    editing_db_check($rejected['body']['result']['isError'] && $owner->getCard($card['id'])['id'] === $card['id'], 'read token cannot delete even its owners card');
    $tokens->revoke(1, $issued['id']);
    editing_db_check(editing_db_rejects(function() use ($tokens, $issued) { $tokens->authenticate($issued['token']); }), 'revoked token rejected');
    mysqli_query($conn, 'UPDATE ce_mcp_tokens SET expires_at = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 MINUTE) WHERE id = ' . $reader['id']);
    editing_db_check(editing_db_rejects(function() use ($tokens, $reader) { $tokens->authenticate($reader['token']); }), 'expired token rejected');
    $deletedUser = $tokens->create(3, 'Deleted user', 'read', 1);
    mysqli_query($conn, 'DELETE FROM users WHERE usersId = 3');
    editing_db_check(editing_db_rejects(function() use ($tokens, $deletedUser) { $tokens->authenticate($deletedUser['token']); }), 'deleted account invalidates credentials');
} catch (Throwable $error) {
    ++$fails;
    echo 'FAIL: ' . $error->getMessage() . "\n";
} finally {
    if ($uploaded) {
        $file = dirname(__DIR__, 2) . '/CardEditor/' . $uploaded['relative_path'];
        if (is_file($file)) unlink($file);
    }
    // Remove only this test's newly created, empty UUID directory; never recursively delete assets.
    if ($assetDirectory && is_dir($assetDirectory)) {
        rmdir($assetDirectory);
        rmdir(dirname($assetDirectory));
    }
    if ($conn) mysqli_close($conn);
    if ($server && $created) mysqli_query($server, "DROP DATABASE `$scratch`");
    if ($server) mysqli_close($server);
}
echo "$checks checks, $fails failures\n";
exit($fails ? 1 : 0);
