TCGEngine
A Generic Card Game Simulator Engine

Montserrat font by: https://www.fontspace.com/montserrat-font-f16544
Loading image by: https://commons.wikimedia.org/wiki/File:Loading_2.gif

## MCP Server (AI Card Editor)

The `McpServer/` directory contains an MCP (Model Context Protocol) server that allows AI agents (e.g. GitHub Copilot) to read and write card abilities through the Card Editor database.

### Prerequisites

- **Node.js** (v18+)
- **MySQL** running (same instance used by the PHP app)

### Setup

```bash
cd McpServer
npm install
npm run build
```

### Available Tools

| Tool | Description |
|------|-------------|
| `list_roots` | List all game roots (e.g. GrandArchiveSim, RBSim) with card counts |
| `list_cards` | List cards for a root with pagination and `hideImplemented` filter |
| `get_macros` | Get available macros for a root (parsed from GameSchema.txt) |
| `get_card_abilities` | Read a card's saved abilities (macro, code, implementation status) |
| `save_card_abilities` | Write/update/delete card abilities for a card |

### VS Code / GitHub Copilot Integration

The MCP server is auto-configured via `.vscode/mcp.json`. After running `npm install` and `npm run build` in the `McpServer/` directory, restart VS Code and the server will be available to Copilot.

If your MySQL credentials differ from the defaults (`root` / empty password / `swuonline` database), edit the `env` section in `.vscode/mcp.json`.

### Rebuilding

After editing any files in `McpServer/src/`, rebuild with:

```bash
cd McpServer
npm run build
```