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
  getHelperFunctions,
  getImplementedExamples,
  getZoneSchema,
  listScenarioTemplates,
  newTestFromScenario,
  runTest,
  saveTest,
  enumerateLegalActions,
  applyEngineAction,
  getGameSnapshot,
  testGameAddToZone,
  testGameAddCounters,
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
  "Read all abilities (macro implementations / code) currently saved for a specific card. Returns the macro name, PHP code body, optional prerequisite code, optional ability name, and implementation status for each ability on the card.",
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
          prereqCode: z.string().nullable().optional().describe("Optional PHP code body that returns whether this macro ability can run in the current context."),
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
// Tool: get_helper_functions
// ---------------------------------------------------------------------------
server.tool(
  "get_helper_functions",
  "Scan the Custom/*.php files of a game root and return all available helper functions with their signatures, file locations, and doc comments. Use this BEFORE implementing a card ability to discover what helper functions already exist (e.g. DealChampionDamage, RecoverChampion, ZoneSearch, DoDrawCard, MZMove, etc). Optionally filter by a search term.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
    searchTerm: z.string().optional().describe("Optional search term to filter helpers by name, params, or body content (case-insensitive)"),
  },
  { readOnlyHint: true, destructiveHint: false },
  async (params) => {
    try {
      const result = getHelperFunctions(params.root, params.searchTerm);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: get_implemented_examples
// ---------------------------------------------------------------------------
server.tool(
  "get_implemented_examples",
  "Get example ability implementations for a given macro type (e.g. 'CardActivated', 'Enter', 'AllyDestroyed'). Returns the ability code, card name, and effect text for already-implemented cards. Use this to learn the correct coding patterns before implementing a new card ability. Examples are sorted by code length (simplest first) so you see basic patterns first.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
    macroName: z.string().describe("The macro name to get examples for (e.g. 'CardActivated', 'Enter')"),
    limit: z.number().int().min(1).max(10).optional().describe("Max number of examples to return (default: 3)"),
  },
  { readOnlyHint: true, destructiveHint: false },
  async (params) => {
    try {
      const result = await getImplementedExamples(params.root, params.macroName, params.limit);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: get_zone_schema
// ---------------------------------------------------------------------------
server.tool(
  "get_zone_schema",
  "Get the zone definitions and macro definitions from a game's schema. Returns all zones with their field types (e.g. Field has CardID, Status, Damage, Controller, TurnEffects), display settings, overlays, counters, and virtual properties. Also returns all macro definitions with their parameters. Use this to understand what data is available on cards in each zone and what macros exist.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
  },
  { readOnlyHint: true, destructiveHint: false },
  async (params) => {
    try {
      const result = getZoneSchema(params.root);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: list_scenario_templates
// ---------------------------------------------------------------------------
server.tool(
  "list_scenario_templates",
  "List available editable scenario templates for a root. Use this to discover proof-of-concept test setups before generating a new test.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
  },
  { readOnlyHint: true, destructiveHint: false },
  async (params) => {
    try {
      const result = listScenarioTemplates(params.root);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: new_test_from_scenario
// ---------------------------------------------------------------------------
server.tool(
  "new_test_from_scenario",
  "Create a draft integration test fixture and live draft game from an editable scenario template. For the first proof of concept, parameters mainly fill placeholders like the card in hand.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
    templateId: z.string().describe("Scenario template ID, for example 'play-from-hand/basic-hand-card'"),
    parameters: z.record(z.string(), z.string()).describe("Placeholder values and optional metadata like name or slug."),
  },
  { destructiveHint: true },
  async (params) => {
    try {
      const result = await newTestFromScenario(params.root, params.templateId, params.parameters as Record<string, string>);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: run_test
// ---------------------------------------------------------------------------
server.tool(
  "run_test",
  "Run a saved integration test fixture through the existing CLI runner.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
    slug: z.string().describe("Fixture slug under Tests/Integration/<root>/"),
  },
  { destructiveHint: false },
  async (params) => {
    try {
      const result = await runTest(params.root, params.slug);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }], isError: !result.success };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: save_test
// ---------------------------------------------------------------------------
server.tool(
  "save_test",
  "Finalize a draft test by snapshotting the current draft game's gamestate as expected_final_gamestate.txt.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
    slug: z.string().describe("Fixture slug under Tests/Integration/<root>/"),
  },
  { destructiveHint: true },
  async (params) => {
    try {
      const result = saveTest(params.root, params.slug);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: enumerate_legal_actions
// ---------------------------------------------------------------------------
server.tool(
  "enumerate_legal_actions",
  "Enumerate legal next actions for a live draft game. The proof of concept supports play-from-hand main-phase actions and a narrow subset of decision queue choices.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
    gameName: z.string().describe("Live draft game name under <root>/Games/"),
  },
  { readOnlyHint: true, destructiveHint: false },
  async (params) => {
    try {
      const result = await enumerateLegalActions(params.root, params.gameName);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: apply_engine_action
// ---------------------------------------------------------------------------
server.tool(
  "apply_engine_action",
  "Apply a single engine action to a live draft game.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
    gameName: z.string().describe("Live draft game name under <root>/Games/"),
    action: z.object({
      playerID: z.number().int(),
      mode: z.number().int(),
      buttonInput: z.string().optional(),
      cardID: z.string().optional(),
      chkInput: z.array(z.string()).optional(),
      inputText: z.string().optional(),
    }).describe("Normalized engine action to apply to the live draft game."),
  },
  { destructiveHint: true },
  async (params) => {
    try {
      const result = await applyEngineAction(params.root, params.gameName, params.action);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }], isError: result.success === false };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: get_game_snapshot
// ---------------------------------------------------------------------------
server.tool(
  "get_game_snapshot",
  "Read the current state of a live draft game. Use view='summary' for a compact state summary or view='full' for the raw gamestate text.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
    gameName: z.string().describe("Live draft game name under <root>/Games/"),
    view: z.string().optional().describe("Either 'summary' or 'full'. Defaults to 'summary'."),
  },
  { readOnlyHint: true, destructiveHint: false },
  async (params) => {
    try {
      const result = await getGameSnapshot(params.root, params.gameName, params.view);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: test_game_add_to_zone
// ---------------------------------------------------------------------------
server.tool(
  "test_game_add_to_zone",
  "Add a card to a zone in the live draft test game and sync the fixture's initial gamestate to that edited state.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
    gameName: z.string().describe("Live draft game name under <root>/Games/"),
    zone: z.string().describe("Perspective-aware zone name such as 'myHand', 'theirField', or 'myMastery'."),
    cardID: z.string().describe("Card ID to add to the zone."),
    perspectivePlayer: z.number().int().optional().describe("Player perspective to interpret my/their zones. Defaults to 1."),
  },
  { destructiveHint: true },
  async (params) => {
    try {
      const result = await testGameAddToZone(params.root, params.gameName, params.zone, params.cardID, params.perspectivePlayer);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }], isError: result.success === false };
    } catch (err: any) {
      return { content: [{ type: "text", text: `Error: ${err.message}` }], isError: true };
    }
  }
);

// ---------------------------------------------------------------------------
// Tool: test_game_add_counters
// ---------------------------------------------------------------------------
server.tool(
  "test_game_add_counters",
  "Add or remove counters on a live draft game object and sync the fixture's initial gamestate to that edited state.",
  {
    root: z.string().describe("The root/game name (e.g. 'GrandArchiveSim')"),
    gameName: z.string().describe("Live draft game name under <root>/Games/"),
    mzID: z.string().describe("MZID of the object to edit, such as 'myMastery-0' or 'theirField-1'."),
    counterType: z.string().describe("Counter type key, such as 'sheen'."),
    amount: z.number().int().describe("Counter delta to apply. Positive adds, negative removes."),
    perspectivePlayer: z.number().int().optional().describe("Player perspective used to resolve 'my' and 'their' in the mzID. Defaults to 1."),
  },
  { destructiveHint: true },
  async (params) => {
    try {
      const result = await testGameAddCounters(params.root, params.gameName, params.mzID, params.counterType, params.amount, params.perspectivePlayer);
      return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }], isError: result.success === false };
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
  process.stderr.write(`Fatal error starting MCP server: ${String(err)}\n`);
  process.exit(1);
});
