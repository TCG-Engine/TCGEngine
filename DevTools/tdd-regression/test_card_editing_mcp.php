<?php

require_once __DIR__ . '/../../CardEditor/Mcp/HttpServer.php';
$fails = 0;
$checks = 0;
function check_mcp($condition, $label) {
    global $fails, $checks;
    ++$checks;
    echo ($condition ? 'PASS' : 'FAIL') . ': ' . $label . "\n";
    if (!$condition) ++$fails;
}
class EditingTestDB {
    public $calls = [];
    public function listGames() { return [['id' => 1, 'name' => 'Alpha'], ['id' => 2, 'name' => 'Beta']]; }
    public function getCard($id) { return ['id' => $id]; }
    public function saveCardFieldValues($id, $values, $fresh) { $this->calls[] = [$id, $values, $fresh]; return ['id' => $id, 'values' => $values]; }
    public function requireGameEditAccess($id) { throw new RuntimeException('You do not have permission to edit this game'); }
}
$db = new EditingTestDB();
$factoryCalls = [];
$factory = function($id) use ($db, &$factoryCalls) { $factoryCalls[] = $id; return $db; };
$auth = function($token) { if ($token !== 'writer' && $token !== 'reader') throw new UnexpectedValueException('Rejected'); return ['userId' => 42, 'scope' => $token === 'writer' ? 'write' : 'read']; };
$headers = ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json, text/event-stream', 'HTTP_AUTHORIZATION' => 'Bearer writer'];
$request = function($method, $params = null, $overrides = []) use ($headers, $auth, $factory) {
    return CardEditingHttpServer::handle(array_replace($headers, $overrides), json_encode(['jsonrpc' => '2.0', 'id' => 7, 'method' => $method, 'params' => $params ?? new stdClass()]), $auth, $factory);
};
$r = $request('initialize', ['protocolVersion' => '2025-11-25', 'capabilities' => new stdClass(), 'clientInfo' => ['name' => 'test', 'version' => '1']]);
check_mcp($r['body']['result']['serverInfo']['name'] === 'tcgengine-card-editing', 'independent server identity');
check_mcp($r['body']['result']['protocolVersion'] === '2025-11-25', 'protocol negotiation');
$r = $request('tools/list');
$names = array_column($r['body']['result']['tools'], 'name');
check_mcp(in_array('create_card', $names) && in_array('upload_asset', $names) && !in_array('save_card_abilities', $names) && !in_array('run_test', $names), 'authoring tools only');
$r = $request('tools/list', null, ['HTTP_AUTHORIZATION' => 'Bearer reader']);
check_mcp(count($r['body']['result']['tools']) > 0 && !in_array('create_card', array_column($r['body']['result']['tools'], 'name')), 'read token advertises only read tools');
$r = $request('tools/call', ['name' => 'create_game', 'arguments' => ['name' => 'No']], ['HTTP_AUTHORIZATION' => 'Bearer reader']);
check_mcp($r['body']['result']['isError'] && !$factoryCalls, 'read scope rejects writes before DB construction');
$r = $request('tools/call', ['name' => 'get_card', 'arguments' => ['id' => '1']]);
check_mcp($r['body']['error']['code'] === -32602 && !$factoryCalls, 'reject string IDs before database access');
$r = $request('tools/call', ['name' => 'get_card', 'arguments' => ['id' => 1, 'userId' => 999]]);
check_mcp($r['body']['error']['code'] === -32602, 'client cannot supply authoring identity');
$r = $request('tools/call', ['name' => 'get_card', 'arguments' => ['id' => 1]]);
check_mcp($factoryCalls === [42] && json_decode($r['body']['result']['content'][0]['text'], true)['id'] === 1, 'server passes authenticated identity');
$r = $request('tools/call', ['name' => 'list_games', 'arguments' => ['query' => 'a', 'limit' => 1]]);
$page = json_decode($r['body']['result']['content'][0]['text'], true);
check_mcp(count($page['items']) === 1 && $page['nextOffset'] === 1 && $page['total'] === 2, 'search and pagination');
$r = $request('tools/call', ['name' => 'save_card_fields', 'arguments' => ['cardId' => 1, 'values' => [['fieldId' => 2, 'value' => 'Rules']], 'expectedUpdatedAt' => '2026-10-12 01:00:00']]);
check_mcp(!$r['body']['result']['isError'] && $db->calls[0][1][0]['value'] === 'Rules' && $db->calls[0][2] === '2026-10-12 01:00:00', 'field values and concurrency marker reach repository');
$r = $request('tools/call', ['name' => 'save_card_fields', 'arguments' => ['cardId' => 1, 'values' => []]]);
check_mcp($r['body']['error']['code'] === -32602, 'field saves require fresh read timestamp');
$r = $request('tools/call', ['name' => 'save_template_fields', 'arguments' => ['templateId' => 1, 'fields' => [['label' => 'X', 'fieldType' => 'php']], 'expectedUpdatedAt' => 'now']]);
check_mcp($r['body']['error']['code'] === -32602, 'unsupported field types rejected');
$r = $request('tools/call', ['name' => 'upload_asset', 'arguments' => ['gameId' => 9, 'filename' => '../art.png', 'base64' => 'bad']]);
check_mcp($r['body']['result']['isError'] && strpos($r['body']['result']['content'][0]['text'], 'permission') !== false, 'artwork permission checked before file processing');
foreach (['GET', 'DELETE'] as $method) {
    $r = $request('ping', null, ['REQUEST_METHOD' => $method]);
    check_mcp($r['status'] === 405, $method . ' returns 405 for stateless transport');
}
$r = $request('ping', null, ['HTTP_AUTHORIZATION' => '']);
check_mcp($r['status'] === 401, 'missing auth returns 401');
$r = $request('ping', null, ['HTTP_AUTHORIZATION' => 'Bearer wrong']);
check_mcp($r['status'] === 401, 'invalid token returns 401');
$r = $request('ping', null, ['HTTP_ORIGIN' => 'https://evil.example']);
check_mcp($r['status'] === 403, 'untrusted browser origin rejected');
$r = $request('ping', null, ['HTTP_MCP_PROTOCOL_VERSION' => 'unknown']);
check_mcp($r['status'] === 400, 'unsupported protocol header rejected');
$r = $request('ping', null, ['CONTENT_TYPE' => 'text/plain']);
check_mcp($r['status'] === 415, 'non-JSON request rejected');
$r = $request('ping', null, ['HTTP_ACCEPT' => 'application/json']);
check_mcp($r['status'] === 406, 'transport requires both accepted response types');
$r = CardEditingHttpServer::handle($headers, '{bad', $auth, $factory);
check_mcp($r['body']['error']['code'] === -32700, 'malformed JSON returns parse error');
$r = CardEditingHttpServer::handle($headers, '[]', $auth, $factory);
check_mcp($r['body']['error']['code'] === -32600, 'JSON-RPC batches rejected');
$before = count($db->calls);
$r = CardEditingHttpServer::handle($headers, '{"jsonrpc":"2.0","method":"tools/call","params":{"name":"save_card_fields"}}', $auth, $factory);
check_mcp($r['status'] === 400 && count($db->calls) === $before, 'tool notifications never execute');
$r = CardEditingHttpServer::handle($headers, '{"jsonrpc":"2.0","method":"notifications/initialized"}', $auth, $factory);
check_mcp($r['status'] === 202 && $r['body'] === null, 'initialized notification gets empty 202');
$r = CardEditingHttpServer::handle($headers, str_repeat('x', CardEditingHttpServer::MAX_BODY + 1), $auth, $factory);
check_mcp($r['status'] === 413, 'oversized body rejected');
check_mcp(!CardEditingSecureRequest(['REMOTE_ADDR' => '203.0.113.2', 'HTTP_HOST' => 'localhost']), 'forged loopback host cannot bypass HTTPS');
check_mcp(CardEditingSecureRequest(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_HOST' => 'localhost:8080']), 'loopback development supported');
check_mcp(CardEditingRequestToken(['HTTP_X_CARD_EDITOR_TOKEN' => 'dedicated', 'HTTP_AUTHORIZATION' => 'Bearer other']) === 'dedicated', 'dedicated header works without Apache Authorization forwarding');
echo "$checks checks, $fails failures\n";
exit($fails ? 1 : 0);
