import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { Client } from '../../McpServer/node_modules/@modelcontextprotocol/sdk/dist/esm/client/index.js';
import { StreamableHTTPClientTransport } from '../../McpServer/node_modules/@modelcontextprotocol/sdk/dist/esm/client/streamableHttp.js';

test('official SDK initializes, discovers tools, and calls the PHP Streamable HTTP server', async () => {
  const requests = [];
  const transport = new StreamableHTTPClientTransport(new URL('https://cards.example.test/CardEditor/Mcp/index.php'), {
    requestInit: { headers: { Authorization: 'Bearer sdk-fixture' } },
    // Exercise SDK HTTP serialization without a listening socket or database.
    fetch: async (_url, init) => {
      const headers = new Headers(init.headers);
      const server = {
        REQUEST_METHOD: init.method,
        CONTENT_TYPE: headers.get('content-type') ?? '',
        HTTP_ACCEPT: headers.get('accept') ?? '',
        HTTP_AUTHORIZATION: headers.get('authorization') ?? '',
        HTTP_MCP_PROTOCOL_VERSION: headers.get('mcp-protocol-version') ?? '2025-03-26',
      };
      const body = init.body ?? '';
      requests.push({ method: init.method, body: body ? JSON.parse(body) : null });
      const result = JSON.parse(execFileSync(process.env.PHP_BINARY || 'php', [fileURLToPath(new URL('./fixtures/card-editing-mcp-bridge.php', import.meta.url))], {
        input: JSON.stringify({ server, body }), encoding: 'utf8', maxBuffer: 4 * 1024 * 1024,
      }));
      return new Response(result.body === null ? null : JSON.stringify(result.body), { status: result.status, headers: result.headers });
    },
  });
  const client = new Client({ name: 'card-editor-sdk-test', version: '1.0.0' });
  try {
    await client.connect(transport);
    assert.equal(client.getServerVersion().name, 'tcgengine-card-editing');
    const listed = await client.listTools();
    assert.ok(listed.tools.some(tool => tool.name === 'get_card'));
    assert.ok(listed.tools.every(tool => tool.annotations.readOnlyHint));
    assert.ok(!listed.tools.some(tool => tool.name === 'save_card_abilities'));
    const card = await client.callTool({ name: 'get_card', arguments: { id: 3 } });
    assert.equal(card.isError, false);
    assert.deepEqual(JSON.parse(card.content[0].text), { id: 3, name: 'Test card' });
    const denied = await client.callTool({ name: 'create_game', arguments: { name: 'Rejected' } });
    assert.equal(denied.isError, true);
    await client.ping();
    assert.ok(requests.some(request => request.body?.method === 'notifications/initialized'));
  } finally {
    await client.close();
  }
});
