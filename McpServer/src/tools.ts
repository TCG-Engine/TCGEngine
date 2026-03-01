import { getPool } from "./db.js";
import * as fs from "fs";
import * as path from "path";
import { fileURLToPath } from "url";
import { execFile } from "child_process";
import { promisify } from "util";

// Resolve the TCGEngine root directory (two levels up from McpServer/dist/)
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const ENGINE_ROOT = path.resolve(__dirname, "..", "..");

const execFileAsync = promisify(execFile);

// ---------------------------------------------------------------------------
// Card dictionary cache — parsed from GeneratedCardDictionaries_*.js files
// ---------------------------------------------------------------------------
interface CardDictionaries {
  nameData: Record<string, string>;
  setData: Record<string, string>;
  effectData: Record<string, string>;
  elementData: Record<string, string>;
  typeData: Record<string, string>;
  cost_memoryData: Record<string, number>;
  cost_reserveData: Record<string, number>;
  levelData: Record<string, number>;
  powerData: Record<string, number>;
  lifeData: Record<string, number>;
  classesData: Record<string, string>;
  subtypesData: Record<string, string>;
}

const dictCache = new Map<string, { data: CardDictionaries; mtime: number }>();

function findGeneratedJsFile(root: string): string | null {
  const genDir = path.join(ENGINE_ROOT, root, "GeneratedCode");
  if (!fs.existsSync(genDir)) return null;
  const files = fs.readdirSync(genDir).filter(f => f.startsWith("GeneratedCardDictionaries_") && f.endsWith(".js"));
  if (files.length === 0) return null;
  // Pick the most recent one (sorted desc by timestamp in filename)
  files.sort((a, b) => b.localeCompare(a));
  return path.join(genDir, files[0]);
}

function parseGeneratedJs(filePath: string): CardDictionaries {
  const content = fs.readFileSync(filePath, "utf-8");
  const lines = content.split(/\r?\n/);
  const result: any = {};
  const fieldNames = [
    "nameData", "setData", "effectData", "elementData", "typeData",
    "cost_memoryData", "cost_reserveData", "levelData", "powerData",
    "lifeData", "classesData", "subtypesData",
  ];
  for (const line of lines) {
    for (const field of fieldNames) {
      const prefix = `var ${field} = `;
      if (line.startsWith(prefix)) {
        // Extract JSON object from "var xxxData = {...};" or "var xxxData = {...}"
        let jsonStr = line.slice(prefix.length);
        if (jsonStr.endsWith(";")) jsonStr = jsonStr.slice(0, -1);
        try {
          result[field] = JSON.parse(jsonStr);
        } catch {
          result[field] = {};
        }
        break;
      }
    }
  }
  // Fill in any missing fields
  for (const field of fieldNames) {
    if (!result[field]) result[field] = {};
  }
  return result as CardDictionaries;
}

function getCardDictionaries(root: string): CardDictionaries | null {
  const filePath = findGeneratedJsFile(root);
  if (!filePath) return null;
  const stat = fs.statSync(filePath);
  const mtime = stat.mtimeMs;
  const cached = dictCache.get(root);
  if (cached && cached.mtime === mtime) return cached.data;
  const data = parseGeneratedJs(filePath);
  dictCache.set(root, { data, mtime });
  return data;
}

// ---------------------------------------------------------------------------
// list_roots — returns available game roots with card counts
// ---------------------------------------------------------------------------
export async function listRoots(): Promise<{
  roots: { name: string; cardCount: number }[];
}> {
  const pool = getPool();
  const [rows] = await pool.query<mysql.RowDataPacket[]>(
    `SELECT root_name, COUNT(DISTINCT card_id) AS card_count
     FROM card_abilities
     GROUP BY root_name
     ORDER BY root_name ASC`
  );
  return {
    roots: (rows as any[]).map((r) => ({
      name: r.root_name,
      cardCount: Number(r.card_count),
    })),
  };
}

// ---------------------------------------------------------------------------
// list_cards — paginated card list with basic filters
// ---------------------------------------------------------------------------
export interface ListCardsParams {
  root: string;
  offset?: number;
  limit?: number;
  hideImplemented?: boolean;
  set?: string;
  cardName?: string;
}

export async function listCards(params: ListCardsParams): Promise<{
  root: string;
  cards: { cardId: string; isImplemented: boolean }[];
  total: number;
  offset: number;
  limit: number;
}> {
  const { root, offset = 0, limit = 50, hideImplemented = false, set, cardName } = params;
  const pool = getPool();
  const dicts = getCardDictionaries(root);

  // Build WHERE clause
  let where = "WHERE root_name = ?";
  const queryParams: any[] = [root];

  if (hideImplemented) {
    where += " AND card_id NOT IN (SELECT DISTINCT card_id FROM card_abilities WHERE root_name = ? AND is_implemented = 1)";
    queryParams.push(root);
  }

  // Build set of card IDs matching set/name filters (applied in-memory via dictionaries)
  let filterCardIds: Set<string> | null = null;
  if ((set || cardName) && dicts) {
    filterCardIds = new Set<string>();
    const allIds = Object.keys(dicts.nameData);
    for (const id of allIds) {
      if (set && dicts.setData[id] !== set) continue;
      if (cardName) {
        const name = dicts.nameData[id];
        if (!name || !name.toLowerCase().includes(cardName.toLowerCase())) continue;
      }
      filterCardIds.add(id);
    }
    if (filterCardIds.size > 0) {
      const placeholders = [...filterCardIds].map(() => "?").join(",");
      where += ` AND card_id IN (${placeholders})`;
      queryParams.push(...filterCardIds);
    } else {
      // No cards match the filter — return empty
      return { root, cards: [], total: 0, offset, limit };
    }
  }

  // Get total count
  const [countRows] = await pool.query<mysql.RowDataPacket[]>(
    `SELECT COUNT(DISTINCT card_id) AS total FROM card_abilities ${where}`,
    queryParams
  );
  const total = Number((countRows as any[])[0]?.total ?? 0);

  // Get paginated results
  const [rows] = await pool.query<mysql.RowDataPacket[]>(
    `SELECT card_id, MAX(is_implemented) AS isImplemented
     FROM card_abilities
     ${where}
     GROUP BY card_id
     ORDER BY card_id ASC
     LIMIT ? OFFSET ?`,
    [...queryParams, limit, offset]
  );

  return {
    root,
    cards: (rows as any[]).map((r) => {
      const cardId = r.card_id;
      const entry: any = {
        cardId,
        isImplemented: Boolean(r.isImplemented),
      };
      // Include name and set from dictionaries if available
      if (dicts) {
        if (dicts.nameData[cardId]) entry.name = dicts.nameData[cardId];
        if (dicts.setData[cardId]) entry.set = dicts.setData[cardId];
      }
      return entry;
    }),
    total,
    offset,
    limit,
  };
}

// ---------------------------------------------------------------------------
// get_macros — parse GameSchema.txt to extract macro names for a root
// ---------------------------------------------------------------------------
export async function getMacros(root: string): Promise<{
  root: string;
  macros: string[];
}> {
  const schemaPath = path.join(ENGINE_ROOT, "Schemas", root, "GameSchema.txt");

  if (!fs.existsSync(schemaPath)) {
    return { root, macros: [] };
  }

  const content = fs.readFileSync(schemaPath, "utf-8");
  const lines = content.split(/\r?\n/);
  const macros = new Set<string>();

  for (const line of lines) {
    if (line.startsWith("Macro:")) {
      const match = line.match(/Name=([^(;]+)/);
      if (match) {
        macros.add(match[1].trim());
      }
    }
  }

  const sorted = [...macros].sort();
  return { root, macros: sorted };
}

// ---------------------------------------------------------------------------
// get_card_abilities — load all abilities for a specific card
// ---------------------------------------------------------------------------
export async function getCardAbilities(
  root: string,
  cardId: string
): Promise<{
  root: string;
  cardId: string;
  abilities: {
    id: number;
    macroName: string;
    abilityCode: string;
    abilityName: string | null;
    isImplemented: boolean;
  }[];
}> {
  const pool = getPool();
  const [rows] = await pool.query<mysql.RowDataPacket[]>(
    `SELECT id, macro_name, ability_code, ability_name, is_implemented
     FROM card_abilities
     WHERE root_name = ? AND card_id = ?
     ORDER BY created_at ASC`,
    [root, cardId]
  );

  return {
    root,
    cardId,
    abilities: (rows as any[]).map((r) => ({
      id: r.id,
      macroName: r.macro_name,
      abilityCode: r.ability_code,
      abilityName: r.ability_name,
      isImplemented: Boolean(r.is_implemented),
    })),
  };
}

// ---------------------------------------------------------------------------
// save_card_abilities — save/update abilities for a card (mirrors SaveAbilities.php)
// ---------------------------------------------------------------------------
export interface SaveAbilityInput {
  id?: number | null;
  macroName: string;
  abilityCode: string;
  abilityName?: string | null;
  isImplemented?: boolean;
}

export interface SaveCardAbilitiesParams {
  root: string;
  cardId: string;
  abilities: SaveAbilityInput[];
  cardImplemented?: boolean;
}

export async function saveCardAbilities(
  params: SaveCardAbilitiesParams
): Promise<{
  success: boolean;
  savedCount: number;
  deletedCount: number;
}> {
  const { root, cardId, abilities, cardImplemented = false } = params;
  const pool = getPool();
  const conn = await pool.getConnection();

  try {
    await conn.beginTransaction();

    // Get existing abilities
    const [existingRows] = await conn.query<mysql.RowDataPacket[]>(
      `SELECT id FROM card_abilities WHERE root_name = ? AND card_id = ?`,
      [root, cardId]
    );
    const existingIds = new Set(
      (existingRows as any[]).map((r) => r.id as number)
    );

    const savedIds = new Set<number>();
    let savedCount = 0;

    // Process each ability
    for (const ability of abilities) {
      if (!ability.macroName || !ability.abilityCode) {
        throw new Error("Each ability must have both macroName and abilityCode");
      }

      const isImpl = ability.isImplemented ? 1 : 0;
      const abilityName = ability.abilityName ?? null;

      if (ability.id) {
        // Update existing
        await conn.query(
          `UPDATE card_abilities
           SET macro_name = ?, ability_code = ?, ability_name = ?, is_implemented = ?
           WHERE id = ? AND root_name = ? AND card_id = ?`,
          [ability.macroName, ability.abilityCode, abilityName, isImpl, ability.id, root, cardId]
        );
        savedIds.add(ability.id);
      } else {
        // Insert new
        const [result] = await conn.query<mysql.ResultSetHeader>(
          `INSERT INTO card_abilities (root_name, card_id, macro_name, ability_code, ability_name, is_implemented)
           VALUES (?, ?, ?, ?, ?, ?)`,
          [root, cardId, ability.macroName, ability.abilityCode, abilityName, isImpl]
        );
        savedIds.add(result.insertId);
      }
      savedCount++;
    }

    // If card is marked as implemented but has no abilities, create a marker
    if (cardImplemented && abilities.length === 0) {
      const [result] = await conn.query<mysql.ResultSetHeader>(
        `INSERT INTO card_abilities (root_name, card_id, macro_name, ability_code, ability_name, is_implemented)
         VALUES (?, ?, '', '', '[Card Implemented]', 1)`,
        [root, cardId]
      );
      savedIds.add(result.insertId);
    }

    // Delete removed abilities
    let deletedCount = 0;
    for (const existingId of existingIds) {
      if (!savedIds.has(existingId)) {
        await conn.query(
          `DELETE FROM card_abilities WHERE id = ? AND root_name = ? AND card_id = ?`,
          [existingId, root, cardId]
        );
        deletedCount++;
      }
    }

    await conn.commit();
    
    // Trigger the code generator asynchronously after saving
    // This regenerates the GeneratedMacroCode.php and GeneratedMacroCount.js files
    runCodeGenerator(root).catch(err => {
      console.error(`Warning: Code generator failed for ${root}: ${err.message}`);
    });
    
    return { success: true, savedCount, deletedCount };
  } catch (err) {
    await conn.rollback();
    throw err;
  } finally {
    conn.release();
  }
}

// Run the code generator to regenerate GeneratedMacroCode.php and GeneratedMacroCount.js
async function runCodeGenerator(root: string): Promise<void> {
  try {
    const generatorPath = path.join(ENGINE_ROOT, "zzGameCodeGenerator.php");
    // Execute PHP CLI with rootName as argument
    // The PHP script will parse this and populate $_GET accordingly
    const { stdout, stderr } = await execFileAsync("php", [generatorPath, `rootName=${root}`], {
      cwd: ENGINE_ROOT,
      maxBuffer: 10 * 1024 * 1024, // 10MB buffer for large outputs
    });
    
    if (stderr && !stderr.includes("Deprecated")) {
      console.warn(`Code generator stderr: ${stderr}`);
    }
  } catch (err: any) {
    throw new Error(`Failed to run code generator: ${err.message}`);
  }
}

// ---------------------------------------------------------------------------
// get_card_info — get card metadata (name, set, effect, stats) from generated dictionaries
// ---------------------------------------------------------------------------
export function getCardInfo(
  root: string,
  cardId: string
): {
  root: string;
  cardId: string;
  found: boolean;
  name?: string;
  set?: string;
  effect?: string;
  element?: string;
  type?: string;
  cost_memory?: number;
  cost_reserve?: number;
  level?: number;
  power?: number;
  life?: number;
  classes?: string;
  subtypes?: string;
} {
  const dicts = getCardDictionaries(root);
  if (!dicts || !(cardId in dicts.nameData)) {
    return { root, cardId, found: false };
  }
  const numOrUndef = (v: number | undefined) => (v !== undefined && v !== -1) ? v : undefined;
  return {
    root,
    cardId,
    found: true,
    name: dicts.nameData[cardId] || undefined,
    set: dicts.setData[cardId] || undefined,
    effect: dicts.effectData[cardId] || undefined,
    element: dicts.elementData[cardId] || undefined,
    type: dicts.typeData[cardId] || undefined,
    cost_memory: numOrUndef(dicts.cost_memoryData[cardId]),
    cost_reserve: numOrUndef(dicts.cost_reserveData[cardId]),
    level: numOrUndef(dicts.levelData[cardId]),
    power: numOrUndef(dicts.powerData[cardId]),
    life: numOrUndef(dicts.lifeData[cardId]),
    classes: dicts.classesData[cardId] || undefined,
    subtypes: dicts.subtypesData[cardId] || undefined,
  };
}

// ---------------------------------------------------------------------------
// list_sets — list unique set codes available for a root
// ---------------------------------------------------------------------------
export function listSets(root: string): { root: string; sets: string[] } {
  const dicts = getCardDictionaries(root);
  if (!dicts) return { root, sets: [] };
  const sets = new Set(Object.values(dicts.setData));
  return { root, sets: [...sets].sort() };
}

// ---------------------------------------------------------------------------
// get_helper_functions — scan Custom/*.php for function signatures and helpers
// ---------------------------------------------------------------------------
export function getHelperFunctions(
  root: string, 
  searchTerm?: string
): {
  root: string;
  helpers: { name: string; signature: string; file: string; line: number; docComment?: string }[];
} {
  const customDir = path.join(ENGINE_ROOT, root, "Custom");
  if (!fs.existsSync(customDir)) return { root, helpers: [] };

  const helpers: { name: string; signature: string; file: string; line: number; docComment?: string }[] = [];
  const files = fs.readdirSync(customDir).filter(f => f.endsWith(".php"));

  for (const file of files) {
    const filePath = path.join(customDir, file);
    const content = fs.readFileSync(filePath, "utf-8");
    const lines = content.split(/\r?\n/);

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];
      const match = line.match(/^function\s+(\w+)\s*\(([^)]*)\)/);
      if (!match) continue;

      const funcName = match[1];
      const params = match[2];
      const signature = `${funcName}(${params})`;

      // Check search term filter
      if (searchTerm) {
        const term = searchTerm.toLowerCase();
        if (!funcName.toLowerCase().includes(term) && !params.toLowerCase().includes(term)) {
          // Also check the next few lines of the function body for the search term
          let bodySnippet = "";
          for (let j = i; j < Math.min(i + 10, lines.length); j++) {
            bodySnippet += lines[j] + "\n";
          }
          if (!bodySnippet.toLowerCase().includes(term)) continue;
        }
      }

      // Look for a doc comment above the function
      let docComment: string | undefined;
      if (i > 0) {
        // Check for // comment on the line above
        const prevLine = lines[i - 1].trim();
        if (prevLine.startsWith("//")) {
          docComment = prevLine.replace(/^\/\/\s*/, "");
        }
        // Check for multi-line /** */ comment
        else if (prevLine === "*/") {
          let commentLines: string[] = [];
          for (let j = i - 1; j >= 0; j--) {
            commentLines.unshift(lines[j].trim());
            if (lines[j].trim().startsWith("/**") || lines[j].trim().startsWith("/*")) break;
          }
          docComment = commentLines
            .map(l => l.replace(/^\/\*\*?\s?/, "").replace(/\*\/\s*$/, "").replace(/^\*\s?/, ""))
            .filter(l => l.length > 0)
            .join(" ");
        }
      }

      helpers.push({ name: funcName, signature, file: `Custom/${file}`, line: i + 1, docComment });
    }
  }

  // Also scan for $customDQHandlers registrations
  for (const file of files) {
    const filePath = path.join(customDir, file);
    const content = fs.readFileSync(filePath, "utf-8");
    const lines = content.split(/\r?\n/);

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];
      const match = line.match(/\$customDQHandlers\["(\w+)"\]\s*=/);
      if (!match) continue;

      const handlerName = match[1];
      if (searchTerm && !handlerName.toLowerCase().includes(searchTerm.toLowerCase())) continue;

      helpers.push({
        name: `$customDQHandlers["${handlerName}"]`,
        signature: `handler(player, parts, lastDecision)`,
        file: `Custom/${file}`,
        line: i + 1,
        docComment: `Custom DQ handler: ${handlerName}`,
      });
    }
  }

  return { root, helpers };
}

// ---------------------------------------------------------------------------
// get_implemented_examples — return N example ability implementations for a macro
// ---------------------------------------------------------------------------
export async function getImplementedExamples(
  root: string,
  macroName: string,
  limit: number = 3
): Promise<{
  root: string;
  macroName: string;
  examples: {
    cardId: string;
    cardName?: string;
    effect?: string;
    abilityCode: string;
  }[];
}> {
  const pool = getPool();
  const [rows] = await pool.query<mysql.RowDataPacket[]>(
    `SELECT card_id, ability_code
     FROM card_abilities
     WHERE root_name = ? AND macro_name = ? AND ability_code != ''
     ORDER BY CHAR_LENGTH(ability_code) ASC
     LIMIT ?`,
    [root, macroName, limit]
  );

  const dicts = getCardDictionaries(root);

  const examples = (rows as any[]).map((r) => {
    const entry: any = {
      cardId: r.card_id,
      abilityCode: r.ability_code,
    };
    if (dicts) {
      if (dicts.nameData[r.card_id]) entry.cardName = dicts.nameData[r.card_id];
      if (dicts.effectData[r.card_id]) entry.effect = dicts.effectData[r.card_id];
    }
    return entry;
  });

  return { root, macroName, examples };
}

// ---------------------------------------------------------------------------
// get_zone_schema — parse GameSchema.txt and return zone definitions
// ---------------------------------------------------------------------------
export function getZoneSchema(root: string): {
  root: string;
  zones: {
    name: string;
    fields: string[];
    display?: string;
    overlays?: string[];
    counters?: string[];
    virtuals?: string[];
    click?: string;
    afterAdd?: string;
  }[];
  macros: { name: string; params: string[]; choiceFunction?: string }[];
} {
  const schemaPath = path.join(ENGINE_ROOT, "Schemas", root, "GameSchema.txt");
  if (!fs.existsSync(schemaPath)) return { root, zones: [], macros: [] };

  const content = fs.readFileSync(schemaPath, "utf-8");
  const lines = content.split(/\r?\n/);

  const zones: any[] = [];
  const macros: any[] = [];
  let currentZone: any = null;

  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith("#")) continue;

    // Macro definitions
    if (trimmed.startsWith("Macro:")) {
      const nameMatch = trimmed.match(/Name=(\w+)(?:\(([^)]*)\))?/);
      const choiceMatch = trimmed.match(/ChoiceFunction=(\w+)/);
      if (nameMatch) {
        const params = nameMatch[2] ? nameMatch[2].split(",").map(p => p.trim()) : [];
        macros.push({
          name: nameMatch[1],
          params,
          choiceFunction: choiceMatch ? choiceMatch[1] : undefined,
        });
      }
      continue;
    }

    // Zone definition (line that starts with a word followed by " - ")
    const zoneMatch = trimmed.match(/^(\w+)\s*-\s*(.+)/);
    if (zoneMatch && !trimmed.startsWith("Display:") && !trimmed.startsWith("Overlay:")
        && !trimmed.startsWith("Counters:") && !trimmed.startsWith("Virtual:")
        && !trimmed.startsWith("Click:") && !trimmed.startsWith("AfterAdd:")
        && !trimmed.startsWith("Highlight:") && !trimmed.startsWith("Macros:")
        && !trimmed.startsWith("Sort:") && !trimmed.startsWith("Index:")
        && !trimmed.startsWith("Widgets:") && !trimmed.startsWith("Module:")
        && !trimmed.startsWith("ServerInclude:") && !trimmed.startsWith("AssetReflection:")) {
      currentZone = {
        name: zoneMatch[1],
        fields: zoneMatch[2].split(",").map((f: string) => f.trim()),
      };
      zones.push(currentZone);
      continue;
    }

    // Zone properties (indented or prefixed lines that augment the current zone)
    if (currentZone) {
      if (trimmed.startsWith("Display:")) {
        currentZone.display = trimmed.replace("Display:", "").trim();
      } else if (trimmed.startsWith("Overlay:")) {
        if (!currentZone.overlays) currentZone.overlays = [];
        currentZone.overlays.push(trimmed.replace("Overlay:", "").trim());
      } else if (trimmed.startsWith("Counters:")) {
        if (!currentZone.counters) currentZone.counters = [];
        currentZone.counters.push(trimmed.replace("Counters:", "").trim());
      } else if (trimmed.startsWith("Virtual:")) {
        if (!currentZone.virtuals) currentZone.virtuals = [];
        currentZone.virtuals.push(trimmed.replace("Virtual:", "").trim());
      } else if (trimmed.startsWith("Click:")) {
        currentZone.click = trimmed.replace("Click:", "").trim();
      } else if (trimmed.startsWith("AfterAdd:")) {
        currentZone.afterAdd = trimmed.replace("AfterAdd:", "").trim();
      } else if (zoneMatch || trimmed.startsWith("Module:") || trimmed.startsWith("ServerInclude:") || trimmed.startsWith("Macro:") || trimmed.startsWith("AssetReflection:")) {
        currentZone = null;
      }
    }
  }

  return { root, zones, macros };
}

// mysql2 type import for RowDataPacket / ResultSetHeader
import type * as mysql from "mysql2/promise";
