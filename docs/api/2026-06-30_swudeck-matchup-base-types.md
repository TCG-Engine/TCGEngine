# SWUDeck Matchup Stats — Base Type Changes

**Audience:** integrators that submit game results to the SWUStats API.
**Status:** Active · **Last updated:** 2026-06-30

> This is a consumer-facing summary. It is intentionally non-exhaustive — the live, canonical
> API reference is at `/TCGEngine/Stats/APIs.php`.

## TL;DR

Opponent bases in matchup stats are no longer collapsed into a single color. They are now split
by **common base type** (Standard / Force / Splash), and **Rare/Special bases are tracked
individually by card name** (e.g. "Data Vault"). **Everything is backward compatible** — payloads
that work today keep working, and existing stored stats are preserved.

---

## Submitting results

Endpoints: `POST /TCGEngine/APIs/SubmitGameResult.php` and `POST /TCGEngine/APIs/SubmitManualGameResult.php`.

### New optional field: `opposingBase`

Inside the per-player JSON (next to `opposingHero`), you may now send the opponent's **base card ID**
(FFG UID):

```jsonc
"player1": {
  "leader": "<leaderID>",
  "base": "<your baseID>",
  "opposingHero": "<opponent leaderID>",
  "opposingBase": "<opponent baseID>",   // NEW — preferred
  "opposingBaseColor": "Red"             // still accepted (fallback)
}
```

### Resolution rules

| You send | Recorded as |
|----------|-------------|
| `opposingBase` = a **common** base | Bucketed by color × type: **Standard** (30HP), **Force** (28HP), or **Splash** (27HP) |
| `opposingBase` = a **Rare/Special** base | Tracked individually by base identity, shown by **card name** |
| only `opposingBaseColor` (or an unrecognized base) | **"{Color} Legacy"** — color known, base type unknown (this is the pre-change behavior) |

**Recommendation:** start sending `opposingBase`. It is optional and additive; no other changes are
required. If you cannot determine the opponent's base card, keep sending `opposingBaseColor` as before.

### How a base is categorized

The base you send is recorded into one of these categories:

| Category | Meaning |
|----------|---------|
| `Standard` | 30HP common base. |
| `Force` | 28HP common base (LOF mechanic). |
| `Splash` | 27HP common base (LAW mechanic). |
| `Named` | Rare/Special base — tracked individually and shown by its card name. |
| `Legacy` | Color-only data; base type unknown (pre-change data, or color-only submissions). |

---

## Behavior & compatibility notes

- **No migration, no data loss.** All previously stored matchup data automatically reads as
  **"{Color} Legacy"**. You do not need to resubmit anything.
- **Reprints consolidate.** Different printings of the same common base count into the same bucket.
- **New sets.** Base classification is curated per set. When a new set releases, its bases are
  added to our lists; until then, an unrecognized base safely defaults to **Standard** rather than
  failing. A Rare/Special base from a not-yet-onboarded set may briefly appear as Standard until the
  list is updated.
- **Display** (the Stats widget): Standard shows as the plain color; Legacy / Force / Splash show as
  "Color · Type"; Rare/Special show the card name.

## Questions / requesting a base reclassification

If you see a base bucketed incorrectly (e.g. a new-set Rare base showing as Standard), report the
base's set code / card ID so it can be added to the classification list.
