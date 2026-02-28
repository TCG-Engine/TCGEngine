import { getPool } from "./db.js";
import * as fs from "fs";
import * as path from "path";
import { fileURLToPath } from "url";

// Resolve the TCGEngine root directory (two levels up from McpServer/dist/)
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const ENGINE_ROOT = path.resolve(__dirname, "..", "..");

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
}

export async function listCards(params: ListCardsParams): Promise<{
  root: string;
  cards: { cardId: string; isImplemented: boolean }[];
  total: number;
  offset: number;
  limit: number;
}> {
  const { root, offset = 0, limit = 50, hideImplemented = false } = params;
  const pool = getPool();

  // Build WHERE clause
  let where = "WHERE root_name = ?";
  const queryParams: any[] = [root];

  if (hideImplemented) {
    where += " AND card_id NOT IN (SELECT DISTINCT card_id FROM card_abilities WHERE root_name = ? AND is_implemented = 1)";
    queryParams.push(root);
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
    cards: (rows as any[]).map((r) => ({
      cardId: r.card_id,
      isImplemented: Boolean(r.isImplemented),
    })),
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
    return { success: true, savedCount, deletedCount };
  } catch (err) {
    await conn.rollback();
    throw err;
  } finally {
    conn.release();
  }
}

// mysql2 type import for RowDataPacket / ResultSetHeader
import type * as mysql from "mysql2/promise";
