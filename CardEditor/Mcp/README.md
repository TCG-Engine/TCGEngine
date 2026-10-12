# Hosted card editing MCP

This server edits content in the hosted Card Editor. It is independent of the developer/programming
MCP in `McpServer/`: no ability macros, schema codegen, engine actions, shell commands, or simulator
tests are exposed. Both servers can be connected separately.

## Deployment

Deploy `CardEditor/Mcp/` along with the updated `CardEditor/Database/CardAuthoringDB.php` and
`CardEditor/UI/index.html` to the existing PHP 8.1+ host. No Node service or npm installation is needed
for the hosted endpoint. It uses the same `MYSQL_*` configuration and authoring database as the UI.
The database account must be able to create `ce_mcp_tokens` and perform the editor's existing schema
setup. The first authenticated request/token management visit initializes token storage.

The Streamable HTTP URL is:

```text
https://YOUR_HOST/TCGEngine/CardEditor/Mcp/index.php
```

Use the final URL directly, avoiding redirects that may discard authorization headers. The server
returns JSON responses and is stateless: POST handles requests; GET and DELETE return 405 because
there are no SSE streams or persistent sessions. It negotiates MCP versions 2025-03-26, 2025-06-18,
and 2025-11-25. Notifications receive an empty 202. Each request is authenticated independently.

Remote requests require HTTPS. Local development HTTP is accepted only when both the connecting
address and Host are loopback. Behind a TLS-terminating proxy, configure your trusted web server to
set PHP's `HTTPS=on` for verified HTTPS traffic. The application deliberately does not trust arbitrary
`X-Forwarded-Proto` headers.

Non-browser clients normally send no Origin. Browser requests with an Origin are rejected by
default; set `CARD_EDITOR_MCP_ALLOWED_ORIGINS` to an explicit comma-separated list of trusted origins
if needed. This does not enable cross-origin browser CORS; a browser-based client would also need a
separately configured CORS policy at the trusted proxy.

Request bodies are limited to 8 MiB. Configure Apache/proxy/PHP limits accordingly if using artwork
upload tools. Ensure PHP can write to `CardEditor/Assets/`. Image uploads allow up to 5 MiB decoded
PNG, JPEG, WebP, or GIF content and dimensions up to 10000 pixels. They inspect the image header and
do not invoke GD WebP decoding or fetch arbitrary remote URLs.

## Account access

1. Sign in to the site's existing TCGEngine account.
2. Open **MCP** in the card editor sidebar, or visit `CardEditor/Mcp/access.php`.
3. Create a named **Read** or **Read and write** token, expiring in 1–365 days.
4. Copy the secret shown once and store it in the MCP client's secret environment.

These are separate `tce_` credentials; existing hosted Card Code `tcc_` tokens do not work here.
Only SHA-256 hashes are stored. Tokens are tied to accounts, not fixed snapshots of permissions:
current ownership and team rules are checked by `CardAuthoringDB` on every tool call. Revocation,
expiration, or account deletion invalidates a token. Read tokens expose only read tools, and the
server also rejects direct attempts to call write tools. Token management uses the browser session
and CSRF protection; MCP credentials cannot administer tokens. Creating tokens is limited to 20
per account per hour. Never share browser session cookies or database credentials with AI clients.

This release uses explicitly configured bearer/API tokens, not OAuth discovery or interactive
account linking. Clients requiring OAuth need a future OAuth integration.

## Codex configuration

Set `TCGENGINE_CARD_EDITOR_TOKEN` in the environment inherited by Codex, then add:

```toml
[mcp_servers.tcgengine_card_editing]
url = "https://YOUR_HOST/TCGEngine/CardEditor/Mcp/index.php"
bearer_token_env_var = "TCGENGINE_CARD_EDITOR_TOKEN"
```

When Apache does not forward Authorization to PHP, use the dedicated header instead:

```toml
[mcp_servers.tcgengine_card_editing]
url = "https://YOUR_HOST/TCGEngine/CardEditor/Mcp/index.php"
env_http_headers = { "X-Card-Editor-Token" = "TCGENGINE_CARD_EDITOR_TOKEN" }
```

Other clients can use the same endpoint with `Authorization: Bearer <token>` or
`X-Card-Editor-Token`. Keep secrets out of config committed to Git.

## Authoring workflow

Start with `list_games`, then discover sets and templates. List tools return `items`, `total`, and
`nextOffset`, with a default page of 50 and maximum of 100; pass `query` to filter names.

- Games: list/get/create/update metadata. Sharing and ownership changes stay in the browser UI.
- Sets: list/get/create/update/export.
- Templates: list/get/create/update/duplicate, replace fields, replace layout, delete.
- Cards: list/get/create/update/delete and save selected typed field values.
- Artwork: list/upload/delete assets. Image field values use the uploaded asset's `relative_path`.

Read `get_template` and `get_card` to discover field IDs/types and existing values. Rules text and
stats are template field values, not simulator PHP. `get_card` includes the template and layout
needed to inspect card content; visual rendering remains in the existing editor (no server-rendered
preview image is provided by this MCP).

Updates and field/layout saves require `expectedUpdatedAt` from the latest record's `updated_at`.
On a "changed elsewhere" error, reload and reconcile before saving. This uses the editor's existing
timestamp conflict guard, not a transactional version counter; simultaneous writes within the same
second are not distinguished. Creation/upload/deletion are not retry-idempotent: after an ambiguous
network failure, inspect the current state before repeating them.

`save_template_fields` replaces the entire field list and deletes omitted fields along with related
card values/layout. Preserve existing field IDs. `save_template_layout` replaces the entire layout;
`save_card_fields` updates only supplied values. Delete and replacement tools carry destructive
annotations. Tool annotations help clients, but the server still enforces authorization.

## Verification

```sh
php DevTools/tdd-regression/test_card_editing_mcp.php
php DevTools/tdd-regression/test_card_editing_mcp_db.php
# Requires the existing developer MCP dependencies (cd McpServer && npm install).
node --test DevTools/tests/card-editing-mcp.test.mjs
```

The DB test creates and drops a uniquely named disposable database; it never selects an existing
application database. Set MySQL host/user/password environment variables for the local test server.
The SDK test checks real MCP client serialization and responses through a CLI PHP bridge; no
listening socket, credentials, or database is needed. For deployment verification, connect MCP
Inspector to the HTTPS endpoint using a temporary read token, then verify a write token against a
test game you own. Revoke both tokens afterward.
