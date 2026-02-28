#!/usr/bin/env node

import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { z } from "zod";
import {
  listRoots,
  listCards,
  getMacros,
  getCardAbilities,
  saveCardAbilities,
  getCardInfo,
  listSets,
} from "./tools.js";
import { closePool } from "./db.js";

const server = new McpServer({
  name: "tcgengine-card-editor",
  version: "1.0.0",
});

// ---------------------------------------------------------------------------
// Tool: list_roots
// ---------------------------------------------------------------------------
server.tool(
  "list_roots",
  "List all available game roots (games/apps) with their card counts. Call this first to discover which roots exist.",
  {},
  { readOnlyHint: true, destructiveHint: false },
  async () => {
    try {
      const result = await listRoots();
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: list_cards
// ---------------------------------------------------------------------------
server.tool(
  "list_cards",
  "List cards for a given root with pagination and optional filters. Returns card IDs, names, sets, and implementation status. Use 'set' to filter by set code (call list_sets first to find valid codes). Use 'cardName' for name substring search.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim', 'RBSim')"),
    offset: z.number().int().min(0).optional().describe("Starting offset for pagination (default: 0)"),
    limit: z.number().int().min(1).max(200).optional().describe("Max cards to return (default: 50, max: 200)"),
    hideImplemented: z.boolean().optional().describe("If true, only show cards that are NOT yet implemented (default: false)"),
    set: z.string().optional().describe("Filter by set code (e.g. 'DOAa', 'HVN'). Call list_sets to see available set codes."),
    cardName: z.string().optional().describe("Filter by card name substring (case-insensitive)"),
  },
  { readOnlyHint: true, destructiveHint: false },
  async (params) => {
    try {
      const result = await listCards({
        root: params.root,
        offset: params.offset,
        limit: params.limit,
        hideImplemented: params.hideImplemented,
        set: params.set,
        cardName: params.cardName,
      });
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: get_macros
// ---------------------------------------------------------------------------
server.tool(
  "get_macros",
  "Get the list of available macros for a root. Macros are the event hooks (e.g. 'PlayCard', 'Enter', 'AllyDestroyed') that card abilities can be attached to. Each card ability must reference one of these macros.",
  {
    root: z.string().describe("The root/game name"),
  },
  { readOnlyHint: true, destructiveHint: false },
  async (params) => {
    try {
      const result = await getMacros(params.root);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: get_card_abilities
// ---------------------------------------------------------------------------
server.tool(
  "get_card_abilities",
  "Read all abilities (macro implementations / code) currently saved for a specific card. Returns the macro name, PHP code body, optional ability name, and implementation status for each ability on the card.",
  {
    root: z.string().describe("The root/game name"),
    cardId: z.string().describe("The card ID to load abilities for"),
  },
  { readOnlyHint: true, destructiveHint: false },
  async (params) => {
    try {
      const result = await getCardAbilities(params.root, params.cardId);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: save_card_abilities
// ---------------------------------------------------------------------------
server.tool(
  "save_card_abilities",
  "Save or update abilities for a card. Provide the full set of abilities — any previously saved abilities not included will be deleted. Each ability needs a macroName (from get_macros) and abilityCode (PHP function body). Include the 'id' field for existing abilities to update them rather than creating duplicates.",
  {
    root: z.string().describe("The root/game name"),
    cardId: z.string().describe("The card ID to save abilities for"),
    abilities: z
      .array(
        z.object({
          id: z.number().nullable().optional().describe("Existing ability ID (from get_card_abilities) to update. Omit or null for new abilities."),
          macroName: z.string().describe("The macro this ability hooks into (e.g. 'Enter', 'PlayCard')"),
          abilityCode: z.string().describe("The PHP code body for this ability"),
          abilityName: z.string().nullable().optional().describe("Optional human-readable name for this ability"),
          isImplemented: z.boolean().optional().describe("Whether this ability is considered implemented (default: false)"),
        })
      )
      .describe("Array of abilities to save. Send all abilities for the card — omitted ones will be deleted."),
    cardImplemented: z
      .boolean()
      .optional()
      .describe("Mark the card as implemented even with no abilities (e.g. vanilla cards with no effects). Default: false."),
  },
  { destructiveHint: true },
  async (params) => {
    try {
      const result = await saveCardAbilities({
        root: params.root,
        cardId: params.cardId,
        abilities: params.abilities,
        cardImplemented: params.cardImplemented,
      });
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: get_card_info
// ---------------------------------------------------------------------------
server.tool(
  "get_card_info",
  "Get detailed card information (name, set, rules/effect text, element, type, cost, power, life, level, classes, subtypes) from generated card dictionaries. Use this to look up what a card does before implementing it.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
    cardId: z.string().describe("The card ID to look up"),
  },
  { readOnlyHint: true, destructiveHint: false },
  async (params) => {
    try {
      const result = getCardInfo(params.root, params.cardId);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: list_sets
// ---------------------------------------------------------------------------
server.tool(
  "list_sets",
  "List all unique set codes available for a root. Use these set codes to filter list_cards results.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
  },
  { readOnlyHint: true, destructiveHint: false },
  async (params) => {
    try {
      const result = listSets(params.root);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Start the server on stdio transport
// ---------------------------------------------------------------------------
async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);

  // Graceful shutdown
  process.on("SIGINT", async () => {
    await closePool();
    process.exit(0);
  });
  process.on("SIGTERM", async () => {
    await closePool();
    process.exit(0);
  });
}

main().catch((err) => {
  console.error("Fatal error starting MCP server:", err);
  process.exit(1);
});
