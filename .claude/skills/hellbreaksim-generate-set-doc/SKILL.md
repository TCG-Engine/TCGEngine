---
name: hellbreaksim-generate-set-doc
description: Use when starting a Hellbreak set to produce its consolidated HellbreakSim/docs/<set>-implement.md plan, which hellbreaksim-implement-set-plan then drives. Scans the generated card dictionaries, classifies every base card, surveys for unbuilt mechanics, then batches the remaining work. Generate-only; one set abbreviation as input.
---

# HellbreakSim — Generate Set Implement Doc

Produce the single runnable `HellbreakSim/docs/<set>-implement.md` plan for one Hellbreak set.
This skill writes **no card logic** — `hellbreaksim-implement-set-plan` drives the plan card by card
via `hellbreaksim-implement-card`.

## Invocation

Argument: one set abbreviation. **`DOT` is the only set that exists** (Dawn of Terror). Anything
else: stop and confirm with the user.

First invoke **`hellbreaksim-session-start`** to load project state, then proceed.

## Flow

```
Stage 1  inventory + classify  +  mechanic survey
         -> .claude/tmp/<set>-inventory.md  -> STOP for human review
Gate     unbuilt shared mechanic?  YES -> report blockers, STOP (no plan)
                                   NO  -> continue
Stage 2  batch by shared mechanic + autonomy tag -> HellbreakSim/docs/<set>-implement.md
```

## Stage 1 — Inventory

**Regenerate first** — the IMPL bucket is read from `GeneratedMacroCode.php`, which is built from the
local `card_abilities` DB rows and is gitignored, so a stale copy reports cards as done that nobody
has written:

```bash
docker exec -w /var/www/html/TCGEngine otmtcge-hellbreaksim-web-server-1 \
  php zzGameCodeGenerator.php rootName=HellbreakSim
```

Then run the scan on the **host** (no docker needed). One TSV row per **base** card —
`id · type · bucket · name · text`:

```bash
.claude/skills/hellbreaksim-generate-set-doc/inventory.sh DOT > .claude/tmp/dot-base.tsv
cut -f3 .claude/tmp/dot-base.tsv | sort | uniq -c        # the bucket split
```

Cards with a non-empty `baseCard` are **variants** (borderless, poster, alt art) and are dropped —
they play as their base card and never carry their own abilities. As of 2026-09-15 DOT has 310
dictionary entries and **176 base cards**.

### Two integrity checks the scan cannot make for you

**1. Abilities keyed on a non-base ID.** Every implemented ID should appear in the base-card list:

```bash
grep -oE '"DOT_[0-9]+:[0-9]+"' HellbreakSim/GeneratedCode/GeneratedMacroCode.php \
  | grep -oE 'DOT_[0-9]+' | sort -u > /tmp/impl.txt
cut -f1 .claude/tmp/dot-base.tsv | sort | comm -23 /tmp/impl.txt -
```

Anything it prints is an ability the engine can never reach — a variant resolves to its base *before*
the ability lookup, so the row is dead. Report it; do not count it as implemented. (DOT_245, DOT_436
and DOT_437 were exactly this in 2026-09-15.)

**2. Unlinked printings inflating the base count.** The importer links variants by name suffix, which
silently fails when the name has no suffix or a damaged one. Two cheap greps find them:

```bash
# duplicate names among "base" cards — a repeat is usually an unlinked printing
cut -f4 .claude/tmp/dot-base.tsv | sort | uniq -d
# any base card numbered >= 225 (the borderless/poster range) is suspect
cut -f1 .claude/tmp/dot-base.tsv | grep -E 'DOT_(22[5-9]|2[3-9][0-9]|[34][0-9][0-9])'
```

A base card at N whose N−200 is also a base card with the same name is an unlinked **borderless**
printing, and that one is a **live gameplay bug**, not just a miscount: a deck containing it resolves
to nothing and the card plays with no abilities. Fix belongs in the `baseCards` map at the top of
`HellbreakSim/CardData/ReviewedCardFaces.json`. ⚠ Not every repeated name is a variant — DOT has
same-name runs at distinct consecutive numbers (Scuba Diver 081–087) and six documented
"uncertain number" pairs (114/115, 134/135, …). Check the collector numbers before calling it a bug.

### ⚠ A Monster's `textData` is only its LURKING face

`textData` carries the front face only. A monster's **unleashed** text — often a whole extra Action
ability — lives only in `facesData`, as JSON with `lurking` / `unleashed` keys. Classifying a monster
from `textData` understates it every time, the same way a SWU leader's deployed side does. For every
`Monster`, read both faces:

```bash
awk '/\$facesData = array \(/{f=1;next} f&&/^  \$[a-zA-Z]+Data = array/{exit} f' $DICT \
  | grep "^  'DOT_001'"
```

Locations and everything else are single-faced.

### Buckets

Assign each base card exactly one:

| Bucket | Rule | Disposition |
|---|---|---|
| **IMPL** | already has a compiled macro key | verify only |
| **VANILLA** | blank text | nothing to build |
| **KEYWORD-ONLY** | text is only implemented keywords (+ their values/reminder) | nothing to build |
| **NEEDS-WORK** | anything else — triggered, Action, passive, scheme, Jumpscare | Stage 2 |

Implemented keywords, all parsed straight from rules text by regex in `HellbreakSim/Custom/`:
**Bloodlust, Fearsome, Fierce, First Strike, Guardian, Malicious, Overkill, Stealth, Terrify**.
There is no keyword registry to consult — `HellbreakKeywordValue()` regexes the card's text — so a
keyword-only card genuinely needs no macro.

**Judgment note:** the keyword strip is a heuristic. READ every card whose text survives the strip
before calling it NEEDS-WORK, and read every KEYWORD-ONLY card's text to confirm nothing rides along
after the keyword. The human review below backstops this boundary.

Baseline for DOT at 2026-09-15: **44 IMPL · 35 VANILLA · 6 KEYWORD-ONLY · 91 NEEDS-WORK**. A wildly
different split means the scan or the regeneration is wrong, not that the set changed.

## Stage 1b — Mechanic survey

Find mechanics the ENGINE does not implement that would block whole groups of cards.

1. **Collect candidate terms** from the NEEDS-WORK texts: capitalized mechanic words, `Action —`,
   `Scheme —`, `Take Control —`, `Flipped —`, `Jumpscare`, and any verb you cannot map to an
   existing macro.
2. **Check the macro vocabulary** in `Schemas/HellbreakSim/GameSchema.txt`. A card needs a
   *dispatch point*; if no macro fires at the moment its text describes, that is a real blocker.
   The 21 event macros and 10 value modifiers are listed there — treat that list as the surface.
3. **Check the helpers** in `HellbreakSim/Custom/CardLogic.php`, `CombatLogic.php`, `GameLogic.php`
   for the operation the text needs (targeting, damage, kill, draw, discard, malice payment).
   A hit may be a real implementation or just a name — judge, and when unsure, list it as a
   candidate and let the user decide at the gate.
4. **Confirm against the rulebook** (`.claude/HellbreakSim/refs/rules-of-play.md`). Only something
   the rules define as a mechanic is a real blocker; a one-off card effect is not.
5. **Count dependents** — how many NEEDS-WORK cards reference it.

Report each confirmed-unbuilt mechanic as **name + rulebook page + dependent-card count**.

## Gate

- **Unbuilt shared mechanic confirmed → HARD STOP.** Report the blockers, write **no** plan, and
  tell the user those foundations come first. Re-invoking re-surveys, so nothing needs hand-maintaining.
- **None → Stage 2.**

## Stage 1 output — scratch inventory, then STOP

Write `.claude/tmp/<set>-inventory.md` (gitignored scratch, deletable after Stage 2):

```markdown
# DOT — Stage 1 Inventory (scratch)

## ⚠ Unbuilt mechanics — build first
<name — rulebook p.N — N dependent cards, or "None detected.">

## Base cards (176)
| ID | Name | Type | Bucket | Note (needs-work text, one line) |
|----|------|------|--------|----------------------------------|
| DOT_001 | Dracula, Transylvanian Terror | Monster | NEEDS-WORK | lurking initiative-win malice; unleashed pay-4 ready Action |
```

Every base card appears in exactly one row, and a Monster's row mentions **both** faces.

**Then STOP and ask the user to review** — both the mechanic section (it drives the gate) and the
bucket assignments. Do not start Stage 2 until they confirm.

## Stage 2 — Batch & tag

Operate on the NEEDS-WORK cards only.

**Autonomy tag** (per card, lifted to the batch):
- `pair-programmed` if it needs a new macro/dispatch point, a new shared helper, a new decision
  shape, or a ruling the rulebook does not settle;
- `autonomous` otherwise.

**Grouping:**
- All **autonomous** phases first, then the **pair-programmed** ones (longest unattended run).
- Each phase is a **shared-mechanic group** — one macro or one helper family — ordered so a
  foundational mechanic precedes the cards depending on it.
- **2–4 cards per batch.** Each card needs its own fixture, and an interactive card needs several.
  A batch that looks small is correct.
- **A Monster is one card but two faces**, and each face is its own ability set. Never put more than
  one Monster in a batch.

## Output — `HellbreakSim/docs/<set>-implement.md`

```markdown
# DOT — Card Implementation Plan

176 base cards (310 dictionary entries incl. 134 variants): <breakdown by type>.
<M> needs-work, <K> already implemented, <V> vanilla/keyword-only.

### Already Done
<comma-separated IMPL + VANILLA + KEYWORD-ONLY ids. hellbreaksim-implement-set-plan appends to this line.>

## Phase 1 — <mechanic> (autonomous)
- [ ] **Batch 1.1 — DOT_012, DOT_045**
  - DOT_012 <name>: RefreshReady — if 2+ allied Human minions, ...
- [ ] **Batch 1.2 — …**

## Phase N — <new dispatch point> (pair-programmed)
```

The loop reads three things: `## Phase X` headers, `- [ ] **Batch X.Y …**` lines carrying CardIDs,
and the `### Already Done` line. The per-card one-liners are for reading at a glance —
`hellbreaksim-implement-card` looks up full text itself.

After writing it, tell the user where the plan is, that the scratch inventory can be deleted, and
that `hellbreaksim-implement-set-plan` can drive it. **Never commit** — the user commits manually.

## Common mistakes

| Mistake | Fix |
|---|---|
| Classifying a Monster from `textData` | That is the lurking face only. Read `facesData` — the unleashed side usually carries a whole Action ability. |
| Counting variants as cards | Drop every row with a non-empty `baseCard`; 134 of DOT's 310 entries are variants. |
| Trusting a stale `GeneratedMacroCode.php` | Regenerate first — it is built from local DB rows and is gitignored. |
| Treating a card-specific effect as an unbuilt "mechanic" | The gate is for a missing *dispatch point or shared helper*, not for a card that needs new code. Check `GameSchema.txt` before escalating. |
| Writing card logic here | This skill is generate-only. |
