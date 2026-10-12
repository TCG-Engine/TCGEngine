<?php
// CLI-only SDK test bridge; never expose a fixture authoring identity over HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../../../CardEditor/Mcp/HttpServer.php';
class SdkEditingFixture {
    public function listGames() { return [['id' => 1, 'name' => 'SDK fixture']]; }
    public function getCard($id) { return ['id' => $id, 'name' => 'Test card']; }
}
$input = json_decode(stream_get_contents(STDIN), true, 64, JSON_THROW_ON_ERROR);
$response = CardEditingHttpServer::handle($input['server'], $input['body'],
    function($token) {
        if ($token !== 'sdk-fixture') throw new UnexpectedValueException('Invalid test token');
        return ['userId' => 42, 'scope' => 'read'];
    }, function($userId) { return new SdkEditingFixture(); });
echo json_encode($response, JSON_THROW_ON_ERROR);
