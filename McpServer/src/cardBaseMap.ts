import * as fs from "fs";
import * as path from "path";
import { fileURLToPath } from "url";

// Variant printing -> base card resolution. Mirrors Core/CardBaseMap.php: a root whose cards have
// several printings (Hellbreak: base, borderless 2xx, alt art 4xx) ships
// <root>/GeneratedCode/CardBaseMap.json, and abilities are only ever stored on the base card.
// Roots without the file resolve every ID to itself.

const ENGINE_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..", "..");
const cache = new Map<string, { mtime: number; map: Map<string, string> }>();

export interface CardBaseResolution {
  cardId: string;
  requestedCardId: string;
  isVariant: boolean;
}

export function cardBaseMapPath(root: string, engineRoot: string = ENGINE_ROOT): string | null {
  if (!/^[A-Za-z0-9_-]+$/.test(root)) return null;
  return path.join(engineRoot, root, "GeneratedCode", "CardBaseMap.json");
}

export function loadCardBaseMap(root: string, engineRoot: string = ENGINE_ROOT): Map<string, string> {
  const filePath = cardBaseMapPath(root, engineRoot);
  if (!filePath || !fs.existsSync(filePath)) return new Map();
  const mtime = fs.statSync(filePath).mtimeMs;
  const cacheKey = `${engineRoot}\0${root}`;
  const cached = cache.get(cacheKey);
  if (cached && cached.mtime === mtime) return cached.map;
  const map = new Map<string, string>();
  try {
    const payload = JSON.parse(fs.readFileSync(filePath, "utf-8"));
    for (const [variantId, baseId] of Object.entries(payload?.baseCards ?? {})) {
      map.set(String(variantId).trim().toUpperCase(), String(baseId).trim());
    }
  } catch {
    // An unreadable map resolves nothing rather than breaking every ability lookup.
  }
  cache.set(cacheKey, { mtime, map });
  return map;
}

export function resolveBaseCardId(root: string, cardId: string, engineRoot: string = ENGINE_ROOT): CardBaseResolution {
  const requestedCardId = String(cardId).trim();
  const resolved = loadCardBaseMap(root, engineRoot).get(requestedCardId.toUpperCase()) ?? requestedCardId;
  return { cardId: resolved, requestedCardId, isVariant: resolved !== requestedCardId };
}
