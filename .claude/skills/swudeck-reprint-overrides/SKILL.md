---
name: swudeck-reprint-overrides
description: Find which cards in a SWU set are reprints of earlier-set cards and add the mappings to SWUDeck/Overrides.php. Use when a new set is released and its reprints need to be aliased back to their earliest printing for deck stats. Takes a set code (e.g. ASH, LAW, SEC).
---

# SWUDeck reprint overrides

`SWUDeck/Overrides.php` (`CardIDOverride`) aliases a reprinted card's CardID back to the
CardID of its **earliest** printing, so deck stats treat reprints as one card. This skill
finds a set's reprints and appends the `case` lines.

## What counts as a reprint
A card is a reprint of an earlier card when they share the **exact** same:
- Title
- Subtitle (treat `null` and `""` as equivalent — the data is inconsistent across sets)
- Cost
- Aspects (compare as a sorted set)

**Ignore tokens** (Shield, Experience, Battle Droid, Clone Trooper, TIE Fighter, X-Wing,
The Force, Spy, Advantage, etc.). Filter by `type.data.attributes.value == "Token"`.
Token IDs also diverge between systems (SWUSim `SOR_T02` vs SWUDeck `SOR_002`), so they
must not go in these overrides anyway.

## Data source
`SWUSim/GeneratedCode/cardArrayCache.json` → `cardArray` is the full multi-set card DB.
Each entry has: `id` (CardID like `ASH_122`), `title`, `subtitle`, `cost`,
`aspects.data[].attributes.name`, `expansion.data.attributes.code`, and
`type.data.attributes.value`. For non-token cards the `SET_NNN` id format is identical
to what SWUDeck's overrides use, so the `id` can be used directly.

## Set release order
Derived from each expansion's `publishedAt`. Current order (oldest → newest):
`SOR, SHD, TWI, JTL, LOF, IBH, SEC, LAW, TS26, ASH`. The script re-derives this from the
data so it stays correct as sets are added.

## Steps
1. Run the finder for the target set:
   ```
   python3 .claude/skills/swudeck-reprint-overrides/scripts/find_reprints.py ASH
   ```
   It prints ready-to-paste `case` lines and the full printing chain for each hit so you
   can confirm the target is genuinely the earliest.
2. Sanity-check each pair (the script prints title/subtitle/cost/aspects, and power/hp as
   an extra signal). A real reprint matches on all four key fields.
3. Append the `case` lines to `SWUDeck/Overrides.php`, just before `default: return $cardID;`,
   following the existing `case "XXX_NNN": return "YYY_NNN"; //Card Name` format.

## Notes
- If a card has 3+ printings, the script still picks the earliest; verify via the printed chain.
- Re-run is idempotent for research; only the Overrides.php edit is manual.
