#!/usr/bin/env node

import fs from "fs";
import os from "os";
import path from "path";
import { spawnSync } from "child_process";
import { fileURLToPath } from "url";
import { listCards, getCardAbilities, getCardInfo } from "../McpServer/dist/tools.js";
import { closePool } from "../McpServer/dist/db.js";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const ENGINE_ROOT = path.resolve(__dirname, "..");

function printUsage() {
  console.log(`Usage: node DevTools/codex-implement-unimplemented.mjs [options]

Options:
  --root <name>          Root/sim to implement from (default: AzukiSim)
  --set <code>           Optional set filter
  --card-name <text>     Optional case-insensitive card name substring filter
  --max-cards <n>        Maximum cards to attempt this run (default: unlimited)
  --model <model>        Optional Codex model override
  --sandbox <mode>       Codex sandbox mode (default: workspace-write)
  --approval <policy>    Codex approval policy (default: never)
  --codex-path <path>    Path to codex.exe / codex binary (default: codex)
  --output-dir <dir>     Directory for prompts, responses, and logs
  --dry-run              Build prompt files without launching Codex
  --help                 Show this help

Examples:
  node DevTools/codex-implement-unimplemented.mjs --root AzukiSim --max-cards 1
  node DevTools/codex-implement-unimplemented.mjs --root AzukiSim --set Booster --model gpt-5.4
`);
}

function getDefaultCodexPath() {
  if (process.env.CODEX_CLI_PATH && fs.existsSync(process.env.CODEX_CLI_PATH)) {
    return process.env.CODEX_CLI_PATH;
  }

  const configPath = path.join(os.homedir(), ".codex", "config.toml");
  if (fs.existsSync(configPath)) {
    const configText = fs.readFileSync(configPath, "utf-8");
    const match = configText.match(/CODEX_CLI_PATH\s*=\s*'([^']+)'/);
    if (match && fs.existsSync(match[1])) {
      return match[1];
    }
  }

  const knownWindowsPath = path.join(
    os.homedir(),
    "AppData",
    "Local",
    "OpenAI",
    "Codex",
    "bin",
    "fb2111b91430cb17",
    "codex.exe"
  );
  if (fs.existsSync(knownWindowsPath)) {
    return knownWindowsPath;
  }

  return "codex";
}

function parseArgs(argv) {
  const options = {
    root: "AzukiSim",
    set: undefined,
    cardName: undefined,
    maxCards: Number.POSITIVE_INFINITY,
    model: undefined,
    sandbox: "workspace-write",
    approval: "never",
    codexPath: getDefaultCodexPath(),
    outputDir: path.join(ENGINE_ROOT, "DevTools", "codex-runs", timestampForPath()),
    dryRun: false,
  };

  for (let i = 0; i < argv.length; i++) {
    const arg = argv[i];
    const next = () => {
      i += 1;
      if (i >= argv.length) {
        throw new Error(`Missing value for ${arg}`);
      }
      return argv[i];
    };

    switch (arg) {
      case "--root":
        options.root = next();
        break;
      case "--set":
        options.set = next();
        break;
      case "--card-name":
        options.cardName = next();
        break;
      case "--max-cards": {
        const value = Number(next());
        if (!Number.isInteger(value) || value < 1) {
          throw new Error("--max-cards must be a positive integer");
        }
        options.maxCards = value;
        break;
      }
      case "--model":
        options.model = next();
        break;
      case "--sandbox":
        options.sandbox = next();
        break;
      case "--approval":
        options.approval = next();
        break;
      case "--codex-path":
        options.codexPath = next();
        break;
      case "--output-dir":
        options.outputDir = path.resolve(next());
        break;
      case "--dry-run":
        options.dryRun = true;
        break;
      case "--help":
      case "-h":
        options.help = true;
        break;
      default:
        throw new Error(`Unknown argument: ${arg}`);
    }
  }

  return options;
}

function timestampForPath() {
  const now = new Date();
  const parts = [
    now.getFullYear(),
    pad(now.getMonth() + 1),
    pad(now.getDate()),
    "-",
    pad(now.getHours()),
    pad(now.getMinutes()),
    pad(now.getSeconds()),
  ];
  return parts.join("");
}

function pad(value) {
  return String(value).padStart(2, "0");
}

function slugify(value) {
  return String(value)
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 80) || "card";
}

function formatValue(value) {
  if (value === undefined || value === null || value === "") return "n/a";
  if (Array.isArray(value)) return value.join(", ");
  if (typeof value === "object") return JSON.stringify(value);
  return String(value);
}

function summarizeAbilities(abilities) {
  if (!abilities.length) return "None saved yet.";
  return abilities
    .map((ability, index) => {
      const name = ability.abilityName ? ` (${ability.abilityName})` : "";
      const prereq = ability.prereqCode ? "\nPrereq:\n" + ability.prereqCode : "";
      return [
        `Ability ${index + 1}: ${ability.macroName}${name}`,
        `Implemented: ${ability.isImplemented ? "yes" : "no"}`,
        "Code:",
        ability.abilityCode || "[empty]",
        prereq,
      ].filter(Boolean).join("\n");
    })
    .join("\n\n");
}

function getBuiltInCardData(root, cardId) {
  const cachePath = path.join(ENGINE_ROOT, root, "GeneratedCode", "cardArrayCache.json");
  if (!fs.existsSync(cachePath)) {
    return null;
  }

  try {
    const parsed = JSON.parse(fs.readFileSync(cachePath, "utf-8"));
    const cards = Array.isArray(parsed.cardArray) ? parsed.cardArray : [];
    return cards.find((entry) => entry.id === cardId) ?? null;
  } catch {
    return null;
  }
}

function buildPrompt({ root, card, cardInfo, abilityData }) {
  const effectText = formatValue(cardInfo.effect ?? cardInfo.cardText);
  const setText = formatValue(cardInfo.set ?? card.set);
  const nameText = formatValue(cardInfo.name ?? card.name);
  const typeText = formatValue(cardInfo.type ?? cardInfo.category);
  const elementText = formatValue(cardInfo.element);
  const classesText = formatValue(cardInfo.classes);
  const subtypesText = formatValue(cardInfo.subtypes);
  const memoryCostText = formatValue(cardInfo.costMemory ?? cardInfo.ikzCost);
  const reserveCostText = formatValue(cardInfo.costReserve);
  const levelText = formatValue(cardInfo.level);
  const powerText = formatValue(cardInfo.power ?? cardInfo.attack);
  const lifeText = formatValue(cardInfo.life ?? cardInfo.health);
  const builtInAbilities = Array.isArray(cardInfo.builtInAbilities) ? cardInfo.builtInAbilities : [];
  const builtInAbilitiesText = builtInAbilities.length ? builtInAbilities.join(", ") : "none";

  return `Implement exactly one unimplemented card in ${root}.

Card ID: ${card.cardId}
Card name: ${nameText}
Set: ${setText}
Type: ${typeText}
Element: ${elementText}
Classes: ${classesText}
Subtypes: ${subtypesText}
Memory cost: ${memoryCostText}
Reserve cost: ${reserveCostText}
Level: ${levelText}
Power: ${powerText}
Life: ${lifeText}

Rules text:
${effectText}

Built-in card dictionary abilities/keywords:
${builtInAbilitiesText}

Existing saved abilities:
${summarizeAbilities(abilityData.abilities)}

Follow the repo instructions in AGENTS.md and .github/copilot-instructions.md.

Required workflow:
1. Use the tcgengine-card-editor MCP workflow for this card.
2. Inspect at least get_card_info, get_card_abilities, get_zone_schema, get_helper_functions, and get_implemented_examples before saving.
3. Save changes through save_card_abilities.
4. If helper logic is needed, add it under ${root}/Custom in the right file.
5. Do not manually edit generated files.
6. Verify with readback and syntax/runtime-safe checks you can do locally.

Execution rules:
- Work only on this card unless a minimal shared engine/helper change is required.
- If the card is vanilla, mark it implemented through the saved-ability path instead of inventing behavior.
- Do not confuse built-in card-dictionary abilities/keywords with saved card-editor abilities. Preserve built-in keywords like Defender/Charge when deciding whether the card is vanilla.
- If you hit a blocker, make the smallest justified shared change or explain the blocker clearly.
- Prefer durable prereq/macro-layer fixes over ad hoc runtime guards.
- Respect the await/codegen constraints from .github/copilot-instructions.md.
- Do not call an existing custom DQ handler directly unless you also queue the exact matching decision/UI step it expects first. Custom handlers usually depend on a specific lastDecision shape from YESNO, MZCHOOSE, MZMAYCHOOSE, MZMULTICHOOSE, or MZMODAL.
- If a flow needs the player to choose cards to discard/select, queue the chooser decision explicitly instead of jumping straight to a shared discard/resolve handler.

When you finish, summarize:
- what changed
- which files changed
- what you verified
- any remaining uncertainty
`;
}

async function getNextUnimplementedCard(options, offset = 0) {
  const result = await listCards({
    root: options.root,
    hideImplemented: true,
    limit: 1,
    offset,
    set: options.set,
    cardName: options.cardName,
  });
  return result.cards[0] ?? null;
}

async function isImplemented(root, cardId) {
  const abilityData = await getCardAbilities(root, cardId);
  return abilityData.abilities.some((ability) => Boolean(ability.isImplemented));
}

function runCodex(options, prompt, lastMessagePath) {
  const args = ["-a", options.approval];
  if (options.model) {
    args.push("-m", options.model);
  }
  args.push(
    "exec",
    "-C",
    ENGINE_ROOT,
    "-s",
    options.sandbox,
    "--color",
    "never",
    "-o",
    lastMessagePath
  );

  return spawnSync(options.codexPath, args, {
    cwd: ENGINE_ROOT,
    input: prompt,
    stdio: ["pipe", "inherit", "inherit"],
    encoding: "utf-8",
  });
}

async function main() {
  const options = parseArgs(process.argv.slice(2));
  if (options.help) {
    printUsage();
    return;
  }

  fs.mkdirSync(options.outputDir, { recursive: true });

  let attempted = 0;
  let implementedCount = 0;

  try {
    while (attempted < options.maxCards) {
      const lookupOffset = options.dryRun ? attempted : 0;
      const card = await getNextUnimplementedCard(options, lookupOffset);
      if (!card) {
        console.log(`No more unimplemented cards found for ${options.root}.`);
        break;
      }

      attempted += 1;
      const safeName = slugify(card.name || card.cardId);
      const prefix = `${pad(attempted)}-${safeName}`;
      const promptPath = path.join(options.outputDir, `${prefix}.prompt.txt`);
      const responsePath = path.join(options.outputDir, `${prefix}.last-message.txt`);
      const metaPath = path.join(options.outputDir, `${prefix}.json`);
      const stdoutPath = path.join(options.outputDir, `${prefix}.stdout.log`);
      const stderrPath = path.join(options.outputDir, `${prefix}.stderr.log`);

      const cardInfo = getCardInfo(options.root, card.cardId);
      const builtInCardData = getBuiltInCardData(options.root, card.cardId);
      cardInfo.builtInAbilities = Array.isArray(builtInCardData?.abilities) ? builtInCardData.abilities : [];
      cardInfo.cardText = builtInCardData?.cardText;
      cardInfo.category = builtInCardData?.category;
      cardInfo.ikzCost = builtInCardData?.ikzCost;
      cardInfo.attack = builtInCardData?.attack;
      cardInfo.health = builtInCardData?.health;
      const abilityData = await getCardAbilities(options.root, card.cardId);
      const prompt = buildPrompt({ root: options.root, card, cardInfo, abilityData });

      fs.writeFileSync(promptPath, prompt, "utf-8");
      fs.writeFileSync(metaPath, JSON.stringify({
        attemptedAt: new Date().toISOString(),
        root: options.root,
        card,
        cardInfo,
        builtInCardData,
        abilitiesBefore: abilityData.abilities,
        promptPath,
        responsePath,
        stdoutPath,
        stderrPath,
      }, null, 2));

      console.log(`\n=== [${attempted}] ${card.cardId} :: ${card.name ?? "Unknown"} ===`);
      console.log(`Prompt: ${promptPath}`);

      if (options.dryRun) {
        console.log("Dry run enabled; skipping Codex launch.");
        continue;
      }

      const result = runCodex(options, prompt, responsePath);
      if (result.error) {
        throw result.error;
      }
      fs.writeFileSync(stdoutPath, result.stdout ?? "", "utf-8");
      fs.writeFileSync(stderrPath, result.stderr ?? "", "utf-8");
      if (result.stdout) process.stdout.write(result.stdout);
      if (result.stderr) process.stderr.write(result.stderr);
      if (result.status !== 0) {
        throw new Error(`Codex exited with status ${result.status} for ${card.cardId}`);
      }

      const implemented = await isImplemented(options.root, card.cardId);
      const abilitiesAfter = await getCardAbilities(options.root, card.cardId);
      const responseText = fs.existsSync(responsePath)
        ? fs.readFileSync(responsePath, "utf-8").trim()
        : "";

      const metaAfter = JSON.parse(fs.readFileSync(metaPath, "utf-8"));
      metaAfter.completedAt = new Date().toISOString();
      metaAfter.abilitiesAfter = abilitiesAfter.abilities;
      metaAfter.implementedAfter = implemented;
      fs.writeFileSync(metaPath, JSON.stringify(metaAfter, null, 2), "utf-8");

      if (!implemented) {
        console.error(`Card still appears unimplemented after Codex run: ${card.cardId}`);
        if (responseText) {
          console.error("\nLast Codex message:\n");
          console.error(responseText);
        }
        process.exitCode = 2;
        return;
      }

      implementedCount += 1;
      console.log(`Implemented: ${card.cardId}`);
      if (responseText) {
        console.log("\nLast Codex message:\n");
        console.log(responseText);
      }
    }

    console.log(`\nRun complete. Attempted ${attempted}, newly implemented ${implementedCount}.`);
    console.log(`Artifacts: ${options.outputDir}`);
  } finally {
    await closePool();
  }
}

main().catch(async (error) => {
  console.error(error instanceof Error ? error.message : String(error));
  await closePool();
  process.exit(1);
});
