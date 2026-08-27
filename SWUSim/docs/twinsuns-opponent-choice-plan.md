# Twin Suns — "an opponent" / "a player" choice sweep

**Opened 2026-08-21.** Multi-session. The resume point is the Progress table at the bottom; keep it
current *before* handing off, never after.

---

## 0. ▶ RESUME STATE (written 2026-08-24 — read this first)

**Suite: 9457 / 0** (baseline at sweep start: 9384). Every fix below is MUTATION-VERIFIED unless marked.

| block | state |
|---|---|
| **Pass 0 — 12 seams** | ✅ ALL CLOSED |
| **Harness four-seat gaps** | ✅ ALL CLOSED (§4 of `_FINDINGS.md`) |
| **DETERMINED (11)** | ✅ ALL CONVERTED, 10 pinned (`SEC_133` unpinnable — unreachable `?:` fallback) |
| **`TWI_199`** | ✅ |
| **PROMPT (40)** | ✅ **ALL 40 CONVERTED AND MUTATION-VERIFIED** (2026-08-24) |
| **Extras beyond the list** | ✅ `SEC_233` Beguile, `TS26_29` Ziton Moj, `SEC_010`-deployed, `LAW_156`/`SHD_256` offer path |

### PROMPT — COMPLETE
All 40 converted. The final 14 (`ASH_224` `HMW_205` `IC27_168` `JTL_014` `JTL_125` `JTL_208` `JTL_221`
`LAW_066` `LAW_080` `LAW_085` `SEC_186` `SEC_218` `SOR_185` `TWI_047`) landed 2026-08-24, each with a
four-seat pin and an independent mutation.

⚠ **`HMW_205` and `IC27_168` carry a FLAGGED PREVIEW ASSUMPTION.** They are absent from
`card-specific-rulings.md` (released sets only). Their reading is taken from the exact released analogue
`SHD_184` Bazine Netal — same clause word for word, and it does carry the "controlling player chooses"
ruling. **Re-check both when HMW / IC27 release and the rulings database is refreshed.**

### PASS 2 — §1b CLOSED, §1c CLOSED, per-clause worklist now known (2026-08-27)

**Suite 9831 / 0.** The old per-FILE classifier was replaced with a per-CLAUSE one (tokenizer, exact
handler bodies, comments stripped — methodology note #2 the hard way). **2718 CardID-keyed clauses**
indexed; **58 cards** carry a legacy `OtherPlayer`/`GetOpponent`/`SWUChooseOpponent` call in at least one
clause.

| bucket | count | state |
|---|---|---|
| **§1b — the 45 "defending player / that opponent" cards** | 45 | ✅ **CLOSED.** 33 already clean; of the 12 flagged, **5 were real bugs** (`SHD_114`, `SEC_242`, `LAW_044`, `LOF_222`, `SHD_144`), 4 were unreachable `??` fallbacks (tightened), 3 were scan false positives (`SEC_126`, `SEC_038`, `SHD_143` — all already seat-guarded). |
| **§1c — "considered" re-checked per clause** | 12 whitewashed | ✅ **CLOSED.** Only ONE live (`LAW_202`), and it was a DEAD seat param the handler never read. The other 11 are `??` fallbacks. |
| **Pass 2 body — plain LIVE clauses** | **~29** | ✅ **CLOSED 2026-08-27** — see "PASS 2 BODY — CLOSED" below: 25 live clauses converted, 3 deliberate leftovers annotated in code. |

⚠ **Two ENGINE helpers were found by card tests refusing to pass — both fixed, both far wider than any card:**
- **`SWUFindMzByUID` (316 call sites)** scanned `my`+`their`, and `their` EXCLUDES a teammate, so it
  returned null for any teammate's unit. Re-resolving by UID is the standard defence against index shift
  after a defeat, so every caller silently skipped teammate units. Now `SWUAllUnits()`.
- **`SWURevealResources` ignored the seat it was handed** — `$ownerPlayer` only chose `my` vs `their`, and
  `their` is the FAN-OUT, so it pooled every opponent's resources. Measured offer:
  `p4Resources-2 & p2Resources-1 & p2Resources-2`. Now filtered to the named seat.
- ★ The pattern: **the fan-out helpers are correct by default, so a caller that has DETERMINED a seat must
  narrow them back down.** `their<Zone>` being right for "each opponent" is what makes it wrong for
  "that opponent".

⚠ **Scan precision, for whoever runs the next one:** the §1b triage was 5/12. A windowed scan over a long
file attributes a NEIGHBOUR's code to your card — `SHD_143` was flagged by LAW_056's call 19 lines away,
and its own comment says "not merely OtherPlayer". Use exact handler bodies, never a line window.

**`SEC_150` Valiant Commando (first of the ~29).** "Deal 3 damage to THAT base" is the base it just
damaged — determined. It used `OtherPlayer()`, so above two seats a bystander's base took the 3. The seat
is now captured at TRIGGER time, not read back in the handler: the YESNO defers resolution past the end of
the attack, so `SWU_CURRENT_DEFENDING_SEAT` is gone by then.

### STILL OPEN (small, named)
- `SEC_133` — unpinnable by construction (its residual defect is an unreachable `?:` fallback).
- The `SWU_DMGDBASE` base-damage stamp has no four-seat pin.
- Pass 2: the ~70 "neither helper" + 16 monolith cards, plus the **45** cards the text scan structurally
  missed (the "defending player / that opponent / its controller" family, `_FINDINGS.md` §1b).

### THE WORKING RULES THIS SWEEP ESTABLISHED (do not re-derive)
1. **Check `.claude/SWUSim/refs/card-specific-rulings.md` FIRST** for any released card. 20 converted cards
   carry the sweep's premise verbatim: *"If there are multiple opponents, the controlling player chooses
   which one will be 'an opponent.'"*
2. **Eligibility = WHO ACTS**, three shapes: chosen player acts on their OWN board ⇒ FILTER precisely;
   something is done TO them ⇒ DO NOT filter ("can't be affected" may be the caster's best line); they act
   on a BOARD-WIDE pool ⇒ gate once globally, filter nobody.
3. **Auto-resolve the forced-optimal choice** (USER RULING): where the effect is strictly beneficial and
   the chosen opponent is never referenced again, resolve to the optimum instead of prompting.
4. **Assert the PROMPT, never just answer it** — a spare answer is silently absorbed, and the harness does
   not validate `OPTIONCHOOSE` candidates. Eligibility needs its own `P#OPTIONHAS/NOT` section.
5. **A menu assertion needs TWO eligible opponents**; at one the picker correctly auto-resolves invisibly.
6. **Pick fixture seats so the LEGACY answer ≠ the CORRECT answer**, then prove it by mutation. A 4-seat
   section is not automatically a discriminating one (SHD_172 and SHD_161 both passed under the bug).
7. **Grep free `#N` handler indices before naming a continuation** — a duplicate `$customDQHandlers` key
   silently overwrites, and the symptom looks like a queue-ordering bug.
8. Probe with `fwrite(STDERR, …)`; `AddGameLogEntry` is invisible in the CLI regression output.

---

## 1. The bug, and why it is everywhere

A card that says **"an opponent"** means *an opponent of your choice*. With one opponent that choice is
degenerate, so for the whole 2-player life of the engine `OtherPlayer()` — literally
`return $player === 1 ? 2 : 1;` — was a correct answer. Twin Suns is live, and it is now wrong.

Its wrapper `SWUChooseOpponent()` carried this comment until today:

> *"the call sites that need a real choice get an interactive prompt in **Phase 4** (when a 3–4 seat game
> is playable); **until then this resolves to the first live opponent**"*

Phase 4 shipped; the placeholder did not get revisited, and it was copied into new cards for months. That
comment is now a warning naming the bug — see [`stale workaround comments`] in project memory.

**Reported symptom (2026-08-21):** *"Cad Bane leader ping didn't ask which player to ping — it always
went to Player 1."* At four seats `OtherPlayer()` answers `2` for seat 1 and **`1` for everyone else**.

---

## 2. THE THREE INVARIANTS — every change in this sweep is judged against these

### I1 — Premier / 2-player must stay byte-identical
No new prompt, no extra click, no changed answer count in any 2-seat game. This is not a nice-to-have:
Premier is the format almost every game is played in, and a spurious prompt there is a worse regression
than the bug being fixed.

**How it is guaranteed:** `SWUQueueChooseOpponent()` emits a `PASSPARAMETER` (auto-resolves, invisible)
when there is exactly one eligible opponent. A converted call site therefore behaves exactly as before.
**If converting a card changes an existing 2-player test, stop — something else is wrong.** Cad Bane's
three pre-existing 2-player sections needed no edit; treat that as the standard, not luck.

### I2 — Auto-target whenever the choice is degenerate, INCLUDING in Twin Suns
A prompt is only correct when there is a **real** choice. Early Twin Suns games are mostly empty boards:
if only one opponent can actually be affected, resolve silently and do not ask. Asking "which opponent?"
when two of the three answers do nothing is a worse UX than the bug.

**How:** pass the `$eligible` seat list to `SWUQueueChooseOpponent($chooser, $handler, $tooltip,
$eligible)`. It filters the menu, auto-resolves at one, and queues **nothing at all** at zero — so the
caller MUST gate on a non-empty eligible list *before paying any cost*, or an accepted "you may" spends
the cost and fizzles. Eligibility is per-card: "an opponent chooses a unit they control" means opponents
**with at least one unit**, not all opponents.

### I3 — Anything that enumerates players must ask the GAME how many there are
No `[1, 2]`, no `1..2`, no `OtherPlayer()` as a stand-in for "the other side". Use:

| need | use |
|---|---|
| every live seat | `GetLiveSeatsArray()` |
| every live opponent | `OpponentsOf($seat)` |
| is this a multiplayer game | `SeatCountForGame() > 2` |
| is a seat still in | `IsSeatLive($seat)` |

⚠ **Live seats, not seat order** — an eliminated seat must not be offered, looped over, or damaged.
⚠ `OpponentsOf()` already filters to live seats; `GetSeatOrderArray()` does NOT.

---

## 2b. USER RULINGS (2026-08-23)

- **Bounty collection at four seats = THE PLAYER WHO DEFEATED THE UNIT.** Above two seats the defeated
  unit's owner, controller and killer are three different seats; the killer collects. Degenerate to
  today's behaviour at two seats, so Premier is untouched. ⚠ `SHD_161`'s existing four-seat bounty
  section **passes by accident** (`OtherPlayer(3) == 1 ==` the owner) — do not read that green as
  evidence, and mutation-verify the fix.
- **`TS26_15` C-3PO — "Only opponents may use this ability" means opponents of the CURRENT CONTROLLER**
  (the CR reading: "you" = the ability's controller). The seat that took control may **not** fire it;
  the original owner **may**. This inverts two existing sections in
  `ts26/C3p0_DieJediDogs.md` (`OpponentUsesActionDealsPower`, `TheDamageScalesWithHisCURRENTPower`) —
  the semantics are ruled, but **show the user the exact diff of those two sections before editing them**
  (project rule: always ask before updating confirmed unit tests).
- **Eligibility (I2) is decided PER CARD — no blanket rule.** The harm-vs-benefit split the research
  swarm observed is a useful *lens*, not a rule to codify: filtering is right where the effect harms the
  chosen seat and they dodge it by being empty, and wrong where "can't be affected" is the caster's best
  line (`ASH_224`, `TWI_222`, `TS26_43`, `TS26_68`, `SEC_218`, `SEC_193`). Each card records its own
  decision in its dossier; do not generalise one card's answer onto its look-alike.
  ⚠ `SHD_246` and `SHD_014` Cad Bane are one word apart and take **opposite** answers.

---

## 2c. ★ OFFICIAL RULINGS RETRO (2026-08-24) — every converted card re-audited

`.claude/SWUSim/refs/card-specific-rulings.md` (official card-database clarifications, 9 sets · 962 cards ·
1618 rulings) was added mid-sweep and **every card converted up to that point was re-checked against it**.

**The sweep's premise is stated officially**, on 20 of the converted cards verbatim:
> *"If there are multiple opponents, the controlling player chooses which one will be 'an opponent.'"*

That confirms the picker conversions outright — including `SEC_193`, whose contested eligibility call was
made on reasoning alone hours earlier.

### What the retro CAUGHT
- ⚠ **`SEC_010` Dedra Meero — a genuine MISS.** Its FRONT was converted; its **deployed** Raid-2 gate
  ("while you have more cards in hand than an opponent") lives in `KeywordEffects.php`, not the card file,
  and still asked one seat. **§5 checklist item 4 — a leader is not done until BOTH sides clear
  independently — exists for exactly this, and the first pass still missed it because the second side was
  in a different file.** Fixed + pinned + mutation-verified.
  ⚠ It is also the MIRROR quantifier of `LAW_083`: "MORE than an opponent" compares against the **minimum**
  hand; "FEWER than an opponent" compares against the **maximum**. Backwards silently inverts the card —
  pinned by its own mutation.

### Deliberate divergence, documented (NOT a bug)
`LAW_083`, `LAW_202`, `JTL_164`, `TWI_168`, `SEC_010`-deployed all carry the "controlling player chooses"
ruling, and we **auto-resolve instead of prompting**. That is correct and intentional: each effect is
strictly beneficial to the caster and **the chosen opponent is never referenced again**, so the optimal
answer is forced — invariant I2 says never raise a prompt whose answer cannot vary. Auto-resolving to the
max/min produces game-identical results with no click. ⚠ If a future card of this shape ever *references*
the chosen opponent afterwards, that reasoning collapses and it needs a real picker.

### Other rulings worth carrying forward
- `SHD_161`: *"Bounty is only triggered if you own the unit and collect its Bounty while it is controlled
  by an opponent"* — independently confirms the four-seat bounty section written for it.
- `TWI_199`: *"Abilities that refer to a card's 'name' do not include the subtitle"* — our `SWUObjectTitle`
  match is title-only. ✅ correct by luck; now correct on purpose.
- ⚠ `SHD_163` Migs Mayfeld **ERRATA**: the corrected text is *"When a player discards a card from **a**
  hand"*, but the dictionary still carries *"from **their** hand"*. Broader than implemented — a discard
  taken from ANOTHER player's hand should also trigger him. **Not seat-related; open item.**
- ⚠ `TS26_29` Ziton Moj: *"All damage dealt by Ziton's On Attack ability is dealt simultaneously."*
  Feeds the already-owed TS26_29 loop fix.
- ⚠ `LAW_215` Vermillion: *"The chosen player may choose not to play the revealed card."*
- `SEC_194`: the ruling confirms the existential reading, and adds that Overwhelm damage from an attack on
  a **unit** does NOT count as attacking your base.

## 3. Inventory (2026-08-21)

Built from **card text** (`$textData` + `$deployTextData`), then cross-referenced against handlers.

### Methodology — three corrections that changed the numbers, do not lose them
1. **Strip parenthesised reminder text before matching.** Overwhelm's *"(…deal excess damage to the
   opponent's base.)"* put 59 innocent cards in the "the opponent" bucket. Stripping parens took that
   category from 60 → **1**.
2. **Strip `//` and `/* */` comments before scanning handlers.** A fix's own comment explaining what
   `OtherPlayer()` used to do reads as a hit. That inflated the auto-pick list by 2.
3. **A card with no per-card file is not unimplemented.** `TWI_098` lives in `$playCostModifiers` in
   `GameLogic.php` — and is already correct. "No file" means *read the monoliths*, not "broken".

### Counts, after those corrections

| pattern (reminder text stripped) | cards |
|---|---|
| "an opponent" | 100 |
| "each opponent" / "each player" | 39 |
| "a player" | 22 |
| "choose a unit" | 16 |
| "that player" | 15 |
| "choose a non-leader unit" | 5 |
| "the opponent" | 1 — `TWI_098`, verified correct |
| **union** | **183 distinct cards** |

| handler verdict | cards |
|---|---|
| uses the correct picker | 7 |
| **AUTO-PICKS — confirmed broken** | **72** |
| uses neither helper — needs a read | 88 |
| no per-card file (monolith) | 16 |

⚠ The 88 are **not** clean. Their auto-pick often lives in a **shared helper**: every *"look at an
opponent's hand"* card routes through `SWULookAtOpponentHand()`, which calls `OtherPlayer()` internally.
`SOR_041 Power of the Dark Side` ("An opponent chooses a unit they control. Defeat that unit") is in that
bucket and is Cad Bane's exact shape.

### ⚠ RE-BASELINED 2026-08-21 (after Pass 0 + the `SWUDiscardCards` sweep) — READ THIS, not the list below

**A fourth scanning correction, and it made the sweep look like it had gone BACKWARDS.** The first
re-run reported 75 auto-picks, UP from 72, and flagged cards known to be correct (JTL_130, JTL_155,
SEC_126, HMW_152, HMW_223). Cause: the classifier treated any file containing `OtherPlayer(` as broken —
but a **2-player fast path beside a real picker is CORRECT**, and is the shape the sweep itself
recommends (`if (SeatCountForGame() > 2) { picker } else { inline }`).
**Rule: a file that uses ANY seat-aware helper has been CONSIDERED.** Only files using *nothing but*
`OtherPlayer` / `SWUChooseOpponent` / `GetOpponent` are suspects.

| bucket | cards |
|---|---|
| seat-aware (considered) | **31** |
| **ONLY legacy helpers — the Pass 1 worklist** | **66** |
| neither helper (needs a read) | 70 |
| no per-card file (monolith) | 16 |

**The 66, split by the shape their TEXT requires** — this is the batching order:

- **PROMPT (47)** — "an opponent" / "a player": `ASH_006, ASH_224, HMW_205, IC27_168, JTL_014, JTL_125,
  JTL_164, JTL_208, JTL_221, LAW_002, LAW_006, LAW_066, LAW_080, LAW_083, LAW_085, LAW_092, LAW_202,
  LAW_216, LAW_233, LOF_015, LOF_065, SEC_010, SEC_186, SEC_193, SEC_194, SEC_218, SEC_260, SHD_161,
  SHD_163, SHD_172, SHD_184, SHD_205, SHD_246, SOR_185, SOR_187, TS26_15, TS26_26, TS26_33, TS26_43,
  TS26_54, TS26_66, TS26_68, TWI_047, TWI_145, TWI_168, TWI_222, TWI_252`
- **LOOP (14)** — "each opponent/player": ✅ **COMPLETE**
  - ✅ 2026-08-21 (5, mechanical): `LAW_204, LAW_116, TS26_56, SHD_159, IC27_104` — all were the literal
    `[caster, OtherPlayer(caster)]`, now `SWUSeatsInPlayerOrder($caster)`.
  - ✅ 2026-08-21 (3 more): `SOR_190` Lothal Insurgent, `TS26_76` Wartime Profiteer, `SOR_129` Admiral
    Ozzel (TWO queue sites — the card file AND `OZZEL_PLAY` in `CardDQHandlers`; grep the handler name,
    not just the card file).
  - ✅ 2026-08-21 `LAW_099` Governor's Shuttle — the **N-seat interactive walk** pattern, reusable for
    any "each player chooses …": a QUEUED chain (not a loop, because each pick is interactive) where the
    remaining seats and the choices so far ride the continuation Param; seats with nothing to choose are
    skipped; and the effect applies only once every seat has answered, so the choices stay SIMULTANEOUS
    and no early removal re-indexes a later seat's pending pool.
  - ✅ 2026-08-21 `LAW_096` Rhydonium Detonation — the same walk, with one deliberate difference worth
    knowing: its bounces apply **IMMEDIATELY**, before the next seat is asked ("…may return a unit.
    THEN, defeat all"), so the pool is **recomputed per seat**. LAW_099's picks are held and applied
    together. **Read the card to decide which**: sequential-with-visible-board vs simultaneous.
  - ✅ 2026-08-21 `SOR_016` Thrawn (both halves, 25 sections) — and the card that shows **when NOT to
    convert a prompt**. Its "reveal the top card of ANY player's deck" was a YES/NO ("own deck or
    opponent?"). At two seats that says it BETTER than a two-name menu, and replacing it would have
    rewritten the prompt plus every existing 2-player section's answer for no gain. So the
    `$includeSelf` picker is gated behind `SeatCountForGame() > 2` and 2-player keeps its YES/NO —
    all 22 pre-existing sections passed untouched.
    **Generalise: I1 does not mean "make the picker invisible at two seats", it means DON'T CHANGE
    PREMIER AT ALL.** Where a card already has a good 2-player prompt, branch on seat count instead of
    replacing it.
  - ✅ 2026-08-21 the adjacency trio — **LOOP CATEGORY COMPLETE (14/14)**:
    `TS26_51` Lom Pyke (order only; its "for each player that does" rider is PER-SEAT — two acceptances
    earn two separate grants), `TS26_80` Reveal Intentions, `TWI_204` Impropriety Among Thieves.

  **USER RULING 2026-08-21 — table adjacency.** **RIGHT = the INCREMENT along SeatOrder, LEFT = the
  DECREMENT.** Seat 1's right neighbour is seat 2; seat 4's wraps to seat 1. Eliminated seats are
  skipped. Lives in `SWUSeatToTheRight()` / `SWUSeatToTheLeft()` beside `NextLiveSeat` — the ONE place
  the convention exists, so a future left/right card inherits it rather than re-deciding. Both return
  the other seat at two players, so a converted card is unchanged in Premier.

  ⚠⚠ **`TWI_204` is the sweep's sharpest structural finding: at two seats the card is a control SWAP, at
  four it is a ROTATION.** Same sentence, different shape — the old code had the swap hard-coded and only
  ever asked for TWO picks, so at four seats it was not incomplete, it was WRONG. **Ask of any "each
  player … the player to their right" card whether the two-seat behaviour is a degenerate case of the
  real one or a different effect entirely.**

  ⚠ Harness gap fixed on the way: `P#DISCARDUNIT` was hard-coded to `P[12]`, so a four-seat "who took
  from whom" section could not be WRITTEN. Widened to any seat. **Second two-seat limit found in the
  TEST INFRASTRUCTURE rather than the engine** — if the harness cannot express a four-seat assertion,
  the gap is invisible by construction.

  **New shared helper: `SWUSeatsInPlayerOrder(int $from)`** — every LIVE seat starting at `$from`, then
  clockwise, eliminated seats skipped. Returns `[$from, other]` at two seats, so it is byte-identical to
  the literal it replaces. THE list for any "each player …" effect.
- **DETERMINED (4)** — "that player" / "its controller", **must NOT prompt**: `JTL_227, SEC_017,
  SEC_133, SOR_188`
- **"choose a unit" (1)**: `TWI_199`

⚠ The **LOOP** group is the one to do first: no picker, no UI, no ruling — just "every live seat instead
of one", the shape already proven on SOR_174 / HMW_154 / SHD_156 / TWI_177 / HMW_188. Cheapest per card
and the least likely to need a decision.

### The 72 auto-picks (SUPERSEDED — kept for history)

```
ASH_006, ASH_148, ASH_224, HMW_188, HMW_205, IC27_104, IC27_168, JTL_014, JTL_125, JTL_164,
JTL_201, JTL_208, JTL_221, JTL_227, LAW_002, LAW_006, LAW_066, LAW_080, LAW_083, LAW_085, LAW_092,
LAW_096, LAW_099, LAW_116, LAW_202, LAW_204, LAW_215, LAW_216, LAW_233, LOF_015, LOF_065, LOF_177,
SEC_010, SEC_017, SEC_186, SEC_193, SEC_194, SEC_218, SEC_260, SHD_156, SHD_159, SHD_161, SHD_163,
SHD_184, SHD_205, SHD_246, SOR_016, SOR_129, SOR_145, SOR_185, SOR_187, SOR_190, TS26_15, TS26_26,
TS26_33, TS26_43, TS26_51, TS26_54, TS26_56, TS26_66, TS26_68, TS26_76, TS26_80, TWI_047, TWI_145,
TWI_168, TWI_177, TWI_185, TWI_199, TWI_204, TWI_222, TWI_252
```

---

## 4. Passes

### Pass 0 — the seams (partly done)
- [x] `SWUChooseOpponent()` — stale comment replaced with a warning naming the bug.
- [x] `SWUQueueChooseOpponent()` — optional `$eligible` filter (I2). Queues nothing at zero eligible.
- [x] **Helper audit done 2026-08-21.** ⚠ The headline finding: **"N card files call it" was the wrong
      metric.** It counted any function whose BODY mentions `OtherPlayer`, so `SWUDealDamageToBase` (48)
      and `DoCaptureUnit` (21) looked like the big wins and are not — both take their target seat as an
      explicit argument, so the helper is fine and the question moves to each caller. The real work is in
      `SWUDiscardCards`, whose optional `$target` DEFAULTS to `OtherPlayer()`.

  **`SWUDiscardCards` — 20 call sites: 8 correct, 12 broken, in FOUR distinct shapes.**

  | shape | cards | fix |
  |---|---|---|
  | "**each** opponent/player discards" | SHD_156, TWI_177, HMW_154, HMW_188 | LOOP `OpponentsOf()` / `GetLiveSeatsArray()`, one call per seat |
  | "**an** opponent discards" | JTL_201, ASH_148, LAW_193, SEC_153 | `SWUQueueChooseOpponent` + pass the picked seat |
  | target is **DETERMINED by the board**, not chosen | LAW_075 ("**its controller** discards" — the exhausted unit's), ASH_162 (`CombatLogic`, "**that opponent**" — the base it damaged) | pass THAT seat; no prompt |
  | shared engine branch | `CardDQHandlers` `case 'OppDiscard'` | needs a picked seat threaded in from the caller |

  ⚠⚠ **DO NOT over-apply the fizzle-only rule to a "you may pay N".** Gating LAW_193's payment offer on
  some opponent holding a card broke `PayOpponentEmptyHandStillPays`, which pins an established project
  ruling: **an action that fizzles STILL PAYS ITS COST** (ASH_004 Thrawn uses it as a soft pass). The
  fizzle-only rule is about an optional clause whose **TARGET POOL** is empty — a choice among nothing.
  A cost whose EFFECT may find nothing to do is different, and is offered. Pay first, then pick.

⚠ **`SHD_181` Pillage — "Choose a PLAYER. They discard 2 cards" — needs a picker that includes
  YOURSELF.** `SWUQueueChooseOpponent` cannot express it (opponents only). Either extend it with an
  `includeSelf` flag or build on `SWUPlayerPickerLabels`/`SWUDecodePlayerPick`, which already model
  "You&Opponent" / "You&P2&P3". **Decide this before starting the card pass** — several of the 22
  "a player" cards will want it.

  ⚠ **`HMW_154` Dooku's Solar Sailer is MY OWN bug from this session** (implemented 2026-08-21, same day):
  its text is "**each** opponent discards a card" and it calls `SWUDiscardCards($player, 1)` with no
  target, hitting one seat. Writing a card during this sweep is not protection against the family — check
  the discard/damage/draw target seat on every new card explicitly.

- [ ] `SWULookAtOpponentHand` / `SWUQueueShowOpponentHand` — CANNOT be fixed helper-side alone: choosing
      an opponent is INTERACTIVE, so the card must queue the picker and hand the seat in. Change the
      signature to take `?int $opp` and convert the 11 + 6 call sites in the card pass.

| card files calling it | helper | note |
|---|---|---|
| 48 | `SWUDealDamageToBase()` | dealer inference fixed 2026-08-21; the *target* seat still needs a look |
| 21 | `DoCaptureUnit()` | |
| 17 | `SWUDiscardCards()` | already takes `$target`; audit the CALLERS |
| 9 | `SWULookAtOpponentHand()` | also carries a stale "for now (2-player)" comment |
| 5 | `SWUQueueShowOpponentHand()` | |

- [x] **`DEAL_TARGET` + `HEAL_TARGET` base routing** (2026-08-21). Both universal handlers picked the base
      owner with `(strpos($mz,'my') === 0) ? $player : GetOpponent($player)`. A Twin Suns base mzID is
      **`p{n}Base-0`**, which matches NEITHER branch, so it fell through to `GetOpponent()`. Now
      `SWUMzOwner($mz, $player)`. Shared handlers, so this fixes every card that offers bases through
      them. Surfaced by HMW_188 Giant Gorax aiming its 3 at a seat-4 base.
- [x] **The 5 `GetOpponent` sites a 4-seat game ACTUALLY reaches** (2026-08-21), found by instrumenting
      the helper and running the suite rather than triaging cold: `SWUEnemySnokeCount` (83 calls),
      `SWUKeywordSuppressed`/ASH_068 Loth-Cat (40), `SWUCreditAbilitiesDisabled`/LAW_117 (27),
      `SWUCollectOpponentPlayReactions` (27), `CollectCombatStep1Triggers` defender fallback (7).
      All five were the same shape — "does **an** opponent control X?" must be "does **any**".
      ⚠ **THE TECHNIQUE IS THE REUSABLE PART:** instrument the suspect helper to log its caller, run the
      suite, and the log IS the live-site list. It turned an unbounded 46-site audit into 5 in one run.
      Use it on the 66 inline ternaries.
      Pin: `shd/SupremeLeaderSnoke_ShadowRuler.md::TwinSuns_ASnokeOnANYSeatShrinksYou` (a SEAT-3 Snoke —
      it must sit on a far seat; on seat 2 the old code was already right).
- [ ] ⚠⚠ **`GetOpponent()` — the remaining ~41 sites — is WORSE than `OtherPlayer()`.** It `return null` for any
      seat above 2, so a seat-3/4 caller does not get a wrong answer, it gets **NULL** — silently no
      damage, no discard, no target. Triage all 46 (11 `GameLogic`, 9 `CombatLogic`, 26 cards).
      **A third legacy helper, not in the original inventory** — the text-based scan could not see it
      because the bug is in the helper, not the card.
- [ ] **66 inline `=== 1 ? 2 : 1` ternaries.** Many are legitimate `Controller`/`Owner` fallbacks or
      telemetry. Triage, do not bulk-replace.
- [x] **32 hardcoded `foreach ([1, 2] as $p)` player loops → `GetLiveSeatsArray()`** (2026-08-21).
      13 files, incl. `SWUCollectLeavePlayReactions`, `SWUCheckShrinkDefeats`,
      `SWUCollectTrapFieldReactions`, `SWUSimulDefeatBegin` and 9 cards. Every one was a board-wide or
      per-player scan that silently stopped at seat 2.
      **Method that made it safe to do in one pass:** before touching anything, scan each loop BODY for
      one that uses BOTH `my*` and `their*` zone names — that is the shape which would DOUBLE-COUNT once
      widened. Exactly 1 of 37 flagged (`SHD_002` Qi'ra), and reading it showed a false positive (its
      `[1,2]` loop uses direct `GetGroundArena($p)` access; the my/their scan is a separate later loop).
      With that class ruled out the rest are mechanical.
      ⚠ A green suite proves only that PREMIER did not regress (I1) — `GetLiveSeatsArray()` returns
      `[1,2]` at two seats, so of course 9357 tests still pass. It proves NOTHING about the fix working.
      Pin at least one with a 4-seat section that cannot pass at 2:
      `shd/BokatanKryze_FightingForMandalore.md::TwinSuns_CountsEVERYSeatsBase` (3 damaged bases ⇒ draw
      3; reverting the loop gives 1).
- [x] **The picker UI shows PLAYER NAMES** (2026-08-21). `SWUQueueChooseOpponent` emits raw seat tokens
      (`P2`/`P3`/`P4`) because the server parses them back with `/^P(\d+)$/`; `Core/OptionChooseUI.js`
      now humanises the BUTTON TEXT only — username when the seat has an account, else "Player N", from
      `window.SWU_SEAT_USERNAMES`.
      ⚠ **Display and value stay separate.** The button submits the untouched token. Do NOT humanise
      server-side: a username is arbitrary user input and the decision Param is a delimited transport, so
      a name containing `&` or a space would corrupt the queue row.
      Pin: `Tests/Visual/ChooseOpponent_PickerShowsPlayerNames.md`.
- [ ] `SWUSim/docs/leader-gaps.md` **does not exist** although `swusim-implement-card` cites it as the
      register of unimplemented deployed sides. Create it; this sweep will keep finding entries.

### Pass 1 — the 72 confirmed auto-picks
One card per pass, reviewed. See the per-card checklist below.

### Pass 2 — the 88 "needs a read" + the 16 monolith cards
Mostly reached through helpers fixed in Pass 0; re-scan after Pass 0 and expect this list to shrink.

---

## 5. Per-card checklist

**Expect roughly three defects per card, not one.** All three were present on `SHD_014` Cad Bane:

1. **The target seat** — the reported symptom.
2. **The "is there anything to hit?" GATE.** Usually also `OtherPlayer()`-based, and *worse than a wrong
   target*: with seat 2 empty and seat 3 holding a unit it silently never offers the ability at all, so
   there is no prompt for a player to notice missing.
3. **A `?:` fallback that GUESSES a seat** when a param is absent (`intval($parts[0] ?? OtherPlayer(...))`).
   The caster should always ride the param; a missing one is a no-op, not a guess.

Then:

4. **Check the OTHER SIDE of a leader.** Cad Bane's deployed ability had never been implemented in any
   format and was in no gap doc. A leader is not done until both sides clear the bar independently.
5. **Where does the chosen player's decision get QUEUED?** "They choose a unit they control" must land on
   *their* queue, resolved in *their* frame. Carry UIDs across frames, never positional mzIDs.
6. **Cost timing.** Pay on *use*, never on being offered — otherwise declining burns the cost (a
   once-per-round budget makes this visible).
7. **`IsSeatLive` — is the seat still in the game?** (Added 2026-08-23.) `OpponentsOf()` /
   `GetLiveSeatsArray()` filter for free, so this only bites where a seat is read from somewhere else (a
   unit's `Controller`, a seat stored in a continuation Param, a snapshot). ⚠ Queueing a decision onto an
   eliminated seat is **not a lost trigger — it is a SOFT-LOCK**: nothing drains that queue and every
   "wait for everyone" gate blocks forever. `AddDecision` now refuses it centrally
   (`SWUSeatAcceptsDecisions`), but a card that *waits* on a dead seat's answer still hangs.

### Required sections per card
- 2-player positive **unchanged** (I1) — do not edit existing 2-player sections; if one breaks, stop.
- Twin Suns: the picker's pool — `P1OPTIONHAS:P2/P3/P4` + **`P1OPTIONNOT:P1`** (a menu built from
  `GetLiveSeatsArray()` instead of `OpponentsOf()` offers you your own seat).
- Twin Suns: pick a **far** seat (3 or 4) and assert only that seat is affected.
- **Degenerate choice auto-resolves (I2)** — 4 seats but only one eligible opponent ⇒ no prompt.
- Nothing eligible anywhere ⇒ no offer at all, and no cost spent.
- Request boundary if any state crosses a decision.

### ⚠ Fixture rules — these have caused two wrong bug reports already
- **`CommonSetup` builds seats 1 and 2 ONLY.** Far-seat units need `WithP{n}GroundArena`; far-seat
  **bases need `WithP3Base` / `WithP4Base`**. Without a base, `ZoneSearch('theirBase')` legitimately
  returns one base, the pool looks "truncated to two seats", and it reads exactly like a broken fan-out.
  *This is what produced the false HMW_011 report.*
- Twin Suns needs `WithSeatOrder`, `WithLiveSeats`, `WithGamePhase: ActionPhase`, `WithActivePlayer`.
- Before reporting a suspected fan-out bug, **instrument first**: log `SeatCountForGame()`,
  `GetLiveSeatsArray()`, `OpponentsOf()`, and the actual zone contents at the moment the pool is built.
- Mutation: revert the eligible set to `OtherPlayer()` and confirm the far-seat sections red while the
  2-player ones stay green. That asymmetry is the proof; a single green run is not.

---

## 6. Progress

**Rewritten 2026-08-21** — the previous table was append-ordered and had drifted out of date. Keep it
GROUPED and rewrite rows in place; do not append.

### Pass 0 — seams and engine-wide fixes

| item | status |
|---|---|
| `SWUChooseOpponent` stale "Phase 4" comment → a warning naming the bug | ✅ |
| `SWUQueueChooseOpponent` `$eligible` filter (invariant I2) | ✅ |
| `SWUQueueChooseOpponent` `$includeSelf` (for "a player") | ✅ |
| `SWUOpponentsWithCards()` — eligibility for the discard family | ✅ |
| `SWUSeatsInPlayerOrder()` — "each player" in player order | ✅ |
| `SWUSeatToTheRight()` / `SWUSeatToTheLeft()` — the adjacency ruling | ✅ |
| high-reach helper audit (5 helpers) | ✅ |
| 32 `foreach ([1,2])` player loops → `GetLiveSeatsArray()` | ✅ |
| `DEAL_TARGET` / `HEAL_TARGET` base routing → `SWUMzOwner` | ✅ |
| the 5 `GetOpponent()` sites a 4-seat game actually reaches | ✅ |
| picker UI shows usernames / "Player N" | ✅ |
| harness: `P#DISCARDUNIT` widened past `P[12]` | ✅ |
| both implement-card / implement-set-plan skills updated | ✅ |
| inventory re-baselined (66-card worklist, split by shape) | ✅ |
| **`GetOpponent()` — the remaining unreached sites** | ◐ 4 more killed via the foreign-mzID sweep (LAW_106, JTL_205, SOR_223, + Vermillion's deck path); the rest still to triage with the instrument-and-run technique |
| **66 inline `=== 1 ? 2 : 1` ternaries** | ✅ **TRIAGED 2026-08-27 — 31 remain, and here is every one of them.** **9 are GrandArchive leftovers** (`QueuePregameStartingChampionSetup`, `PREGAME_CHOOSE/RESOLVE_STARTING_CHAMPION*`, `DoAllyDestroyed`, `MaterialSelectionMetadata`, `BanishSelectionMetadata`) — GA is 2-player, leave them. **15 are provably safe**: `CombatLogic:504` sits in the `else` of `SeatCountForGame() > 2`; `CombatLogic:3267/3270/3271` are `$target->Controller ?? …` fallbacks that a unit in play can never reach; `KeywordEffects:181` IS `OtherPlayer`'s definition; the rest are comments or client JS. **7 ARE LIVE SWU CODE AND STILL OPEN — see the row below.** |
| **create `SWUSim/docs/leader-gaps.md`** (cited by the card skill) | ✅ **EXISTS** — it was deleted incidentally in `46b89e5e` and restored; two skills still cite it. |

### Pass 1 — cards

| group | status |
|---|---|
| **`SWUDiscardCards` — ALL 20 call sites** | ✅ complete |
| ↳ SHD_181 Pillage · HMW_154 · SHD_156 · TWI_177 · JTL_201 · ASH_148 · LAW_193 · SEC_153 · LAW_075 · ASH_162 · `'OppDiscard'` · SOR_174 | ✅ |
| **LOOP — "each opponent/player" (14)** | ✅ complete |
| ↳ LAW_204 · LAW_116 · TS26_56 · SHD_159 · IC27_104 · SOR_190 · TS26_76 · SOR_129 · LAW_099 · LAW_096 · SOR_016 · TS26_51 · TS26_80 · TWI_204 | ✅ |
| SHD_014 Cad Bane — both sides (the reported bug) | ✅ |
| HMW_188 Giant Gorax — each opponent chooses independently | ✅ |
| **RESEARCH SWARM — all 52 remaining Pass-1 cards dossiered** (2026-08-23) | ✅ see `twinsuns-dossiers/_FINDINGS.md` |
| **DETERMINED (11)** — the original 4 (all confirmed) + 7 re-filed from PROMPT | ✅ **ALL 11 CONVERTED AND 10 MUTATION-VERIFIED** (2026-08-23): `JTL_227`, `SEC_017`, `SOR_188`, `TWI_168` (×2 — existential AND anti-sum), `SHD_172` (×3 — one per cause), `LAW_083` (×2 — one per comparison), `JTL_164`, `LAW_202`, `SEC_010`, `SEC_194`. ⚠ **`SEC_133` is UNPINNABLE, deliberately**: its only remaining defect was a `?:` fallback (`$o->Controller ?? GetOpponent(...)`) that is unreachable — a unit in play always carries a Controller — so it is a defensive fix with no failing state to assert. Documented, not faked. |
| **"choose a unit" (1)** — `TWI_199` | ✅ **mutation-verified**. RULING APPLIED: "each ENEMY unit" means enemy of the ABILITY'S CONTROLLER (CR meaning), and does NOT shift with the chosen unit's owner — so picking an opponent's unit still returns every OTHER opponent's same-name units, and never the caster's own. Fix was to STAY in the caster's frame and search `their*` (ZoneSearch already fans out) instead of flipping `$playerID` to one opponent. Pin: `twi/ClearTheField.md::TwinSuns_EnemyMeansEVERYOpponentNotJustTheChosenOnesController`. |
| **PROMPT (40)** — the long tail, see §3 (was 47; 7 re-filed as DETERMINED) | ◐ **4 done, 36 to go.** `SHD_163` ✅ — ⚠ a LEAK IN AN ALREADY-SHIPPED FIX (Migs hand-rolled the my-prefix base ternary, so the central `SWUMzOwner` fix never reached it — **grep the SHAPE, not the helper's call sites**). `LAW_233` ✅ via the Galen seam. `TWI_222` + `TWI_252` ✅, `TS26_43` ✅, `LAW_216` ✅, `TS26_33` ✅, `TS26_66` ✅, `TS26_68` ✅, `TWI_145` ✅, `LOF_065` ✅, `LAW_002` ✅ (front; deployed side already fanned out via an Owner filter over `their*`), `TS26_54` ✅ (now pinned), `SHD_246` ✅, `SHD_205` ✅, `LAW_006` ✅ (3 sites, both sides). `SHD_184` ✅ (**first consumer of the Pass-0 `?int $opp` seam** — passing the seat controls BOTH the hand read AND the emitted mzID form, and the discard site reads the seat back off the chosen card so discard/log/draw-rider cannot disagree), `ASH_006` ✅. **25 done, 15 to go — all mutation-verified.** Latest: `SEC_193`, `LAW_092`, `SOR_187`, `LOF_015` (both sides, ready-only eligibility), `TS26_29` (rebuilt as a simultaneous one-per-player MZMULTICHOOSE per the official ruling), `SEC_010`-deployed (caught by the rulings retro).
⚠ **A caught omission worth remembering:** `ASH_006`'s first cut COMPUTED `$eligible` and never PASSED it to `SWUQueueChooseOpponent`. The code read correctly, the suite was green, and only the `P#OPTIONNOT` menu assertion caught it. A computed-but-unused eligibility list is invisible without a menu pin — one more reason every eligibility decision needs its own section.
⚠⚠ **`SHD_246` vs `SHD_014` Cad Bane is the sweep's sharpest near-miss:** the clauses differ by ONE WORD — Cad Bane "a unit they control" (NEEDS a has-a-unit filter) vs SHD_246 "a unit **or base** they control" (must have NO filter, since every live opponent always controls a base). Copying Cad Bane's gate across would delete legal picks. Pinned from both directions.
**⚠ THE ELIGIBILITY RULE, settled by two counterexample pairs — it is not the sentence shape, it is WHO ACTS:** if the chosen player is asked to DO something (`LAW_216` "an opponent CHOOSES a ground unit they control") they must be ABLE to act ⇒ FILTER, and filter precisely (ground-only: a space-only board is not eligible). If something is done TO them (`TS26_43` heal, `TWI_222` discard-or-droids) then "can't be affected" may be the caster's BEST line ⇒ DO NOT FILTER. **THREE SHAPES, now each with a worked example:**
1. *Chosen player ACTS on their own stuff* ⇒ FILTER precisely — `LAW_216` (ground-only: a space-only board is ineligible), `TS26_33` (`SWUOpponentsWithCards`).
2. *Something is done TO them* ⇒ DO NOT FILTER — `TS26_43` (heal a clean base = heal 0), `TWI_222` (hellbent ⇒ you get the Droids), `TS26_68` (empty deck ⇒ base damage, `DoDrawCard` always does something).
3. *Chosen player acts on a BOARD-WIDE pool identical for everyone* ⇒ gate ONCE globally, filter nobody — `TS26_54`, `TS26_66`.
⚠ `TS26_33` vs `TWI_222` is the sharpest pair: both are "an opponent … discards", and the filter flips purely on whether the card has an "if they DON'T" clause. Read what happens when they CAN'T. |
| **BLOCKED on a ruling** — `TS26_15` (full), `SEC_260` (reveal clause only) | ⚠ |

### Pass 0 — NEW seams found by the 2026-08-23 research swarm (detail: `twinsuns-dossiers/_FINDINGS.md` §2)

| item | status |
|---|---|
| **"the defending player" seam** — new `SWU_CURRENT_DEFENDING_SEAT` published at attack declaration (`CombatLogic.php`) + `SWUCurrentDefendingSeat()` (`GameLogic.php`). THE answer for any On-Attack ability naming the defender (~55 cards). | ✅ seam + first consumer `JTL_227` **mutation-verified** — pin `jtl/SuperheavyIonCannon.md::TwinSuns_OfferIsONLYTheDefendingSeatsUnits`. ⚠ remaining consumers `JTL_149, JTL_156, SEC_205, SEC_017` + the 45-card family still to convert |
| hidden-zone reveal 2-seat hardcoded ×2 blocks (`zzGameCodeGenerator.php`) — **blind pick above 2 seats** | ✅ **mutation-verified** — one `$_swuRevealSeats` scanner replaces both boolean flags; all 4 seats now carry a reveal term for Hand AND Resources. Probe: `SWUSim/DevTools/tests/twinsuns_hidden_zone_reveal_test.php` (30 assertions against the GENERATED text). ⚠ **`their<Zone>` is honoured only at ≤2 seats** — cards must be converted to `p{n}<Zone>`; the transport will not guess. ⚠ **REGEN ON THE SERVER POST-DEPLOY.** |
| `OTPF`/`OTPP`/`OTPN` permissions carry no seat | ✅ **both halves mutation-verified**. `SWUParseDiscardModifier` / `SWUBuildDiscardModifier` / `SWUDiscardModifierGrantsTo`; `SWUPlayFromOpponentDiscard(?int $ownerSeat)`; offer list loops `OpponentsOf()` and carries `owner`; client reads the seat off the rendered mzID; harness gained `PlayFromOpponentDiscard: P<seat>:<idx>`. ⚠ **Grantee is `@N`, NOT a bare digit — a trailing digit already means a COST DISCOUNT (`TPP2` = TWI_201).** Pins: `jtl/StolenAtHauler.md::TwinSuns_AFarSeatsDiscardPileIsReachableAtAll` + `::TwinSuns_TheGrantIsTaggedToONESeatAndDoesNotLeak`. ⚠ JTL_221's interactive picker is still owed (card work — its handler is synchronous; use the SOR_016 gate). |
| `SWULookAtOpponentHand` / `SWUQueueShowOpponentHand` / `SWUQueueShowOpponentDeck` / `SWULogResourceReveal` → `?int $opp` (20 call sites audited, none passed a seat ⇒ defaulted tail param is byte-identical). Helper now emits `theirHand-N` at ≤2 seats and **`p{n}Hand-N` above** (required by the transport reveal). `SWUOfferDiscard` takes `'opp'`; `DISCARD_FROM_OPP_HAND` derives the seat from the mzID. New `SWULogPrivateReveal()` scopes a private look to the two involved seats above 2 seats (`'ALL'` is byte-identical at 2). | ✅ seam + first consumer `SEC_017` Sabé (3 sites, both sides) **mutation-verified** — pin `sec/Sabe_QueensShadow.md::TwinSuns_Deployed_LooksAtTheDEFENDINGSeatsHand` |
| **`their<Zone>` resolver + every literal `their<Zone>-N` handoff** | ✅ **mutation-verified**. The RESOLVER is deliberately left 2-seat (with a loud contract comment in `zzGameCodeGenerator.php`): with 3 opponents "their" names nobody, and guessing in the hottest resolver would hide the error and allocate on every call. Instead new **`SWUForeignMzID($player,$ownerSeat,$zone,$idx)`** is THE way to build a foreign-zone mzID (`their<Zone>-N` at ≤2 seats, `p{n}<Zone>-N` above). **6 sites fixed**: `FOREIGN_PILOT_PLAY_CHOICE`, `SWUEnemyCreditTokenMzIDs` (LAW_106), `LAW_215` Vermillion, `JTL_205` Commence Patrol, `SOR_126` Resupply, `SOR_223` Restock — **4 of them also used `GetOpponent()`, which returns NULL above seat 2**, so the ability silently found nothing at all. Pin: `law/DefiantScrapper.md::TwinSuns_AnEnemyCreditOnANYSeatIsFindable`. |
| `SWUComputeActionsData` `$oppPlayer` — opponent-discard permissions dead for seats 3–4 | ✅ closed via the OTPF offer-list rewrite (both `OtherPlayer` uses in that function are now comments only) |
| `GameLogic.php:17008` "any player may use this ability" → loop `OpponentsOf()`, `p{n}` mzIDs above 2 seats | ✅ **NOW MUTATION-VERIFIED** (2026-08-24). The harness gained **`P#UNITACTIONS{EXACT,HAS,NOT}`**, which asserts `$data['unitActions']` from `SWUComputeActionsData` — the list the CLIENT uses. Reverting the loop reds **3** sections across `LAW_156`, `SHD_256`, `TS26_15`. |
| **Bounty collector at 4 seats** → new `SWUBountyCollector()`, per the 2026-08-23 ruling (**the killer collects**). Applied at **FOUR** sites, not three — deferred-WhenDefeated bag, Exploit bag, innate loop, granted-bounty loop. | ✅ **mutation-verified** — pin `shd/CloneDeserter.md::TwinSuns_TheKILLERCollectsNotSeat1`. ⚠ The pin is **seat 3 killing seat 4** on purpose: with P1 as killer the old rule is right by accident (`OtherPlayer(2)==OtherPlayer(3)==1==P1`), so the obvious 4-seat section passes under the bug — which is exactly what `SHD_161`'s existing one does. |
| `KeywordEffects.php:778`/`:1217` Galen aura → new `_SWUAnyOpponentControlsActive()` | ✅ **mutation-verified** — pin `law/GalenErso_DestroyingHisCreation.md::TwinSuns_AGalenOnANYSeatGrantsToEveryone` (revert ⇒ 1 failed, that one; all 4 two-player sections stay green) |
| `CombatLogic.php:2897` `SWU_DMGDBASE` stamp → `SWUMzOwner($targetMzID, $player)` | ✅ code; ⚠ **no 4-seat pin yet** |
| ⚠ **7 live two-seat ternaries in TRIGGER-ORDERING and SELECTION paths** | ☐ **NEW, found during the 2026-08-27 reconciliation.** `GameLogic.php` `FlushEntryTriggerBag:8575`, `FlushCombatTriggerBag:8701,8711`, `SWU_TRIGGER_RESUME:12031,12055`, `SWU_TRIGGER_ORDER_CHOICE:12079` all compute `$choosingPlayer` / `$resumeOwner` / `$other` as `$activePlayer === 1 ? 2 : 1` — that is WHO ORDERS simultaneous triggers, so it is rules-relevant, not cosmetic. `SelectionMetadata:22640` gates a card highlight on "both decision queues empty" and only ever checks one other seat, so at 3+ seats a card can light up while a far seat still owes an answer. None is scan-visible as a seat bug: they name no seat helper. |
| `AddGameLogEntry` cannot express 2-seat visibility | ✅ **CLOSED 2026-08-27.** VISIBILITY is now a COMMA-SEPARATED seat list (`ALL` / `P3` / `P1,P3`) instead of one seat, so a private look is ONE entry addressed to exactly the seats involved — `SWULogPrivateReveal` used to store the same line twice, and a team-scoped line was unexpressible. ⚠ The reader is GENERATED: the fix lives in `zzGameCodeGenerator.php`'s GameLog block, not in the gitignored `GetNextTurn.php`. Guarded by `DevTools/tests/gamelog_visibility_test.php` (reader shape + filter semantics, incl. the prefix case — `P1` must not match seat 11); mutation-verified by reverting the generator and regenerating. |
| **`IsSeatLive` / dead-seat queues** — §5 checklist item 7 | ✅ **mutation-verified**. Two halves: (1) `_SWUEliminationCleanup` now DRAINS the eliminated seat's DecisionQueue **and TempZone** — it cleaned arenas and the base but left the queue, and nothing else ever drains it, so every "wait for everyone" gate (`AllQueuesEmpty`, TurnController `PENDING_DECISION`, ~10 `CustomInput` sites) blocked forever — **a SOFT-LOCK, not a lost trigger**; (2) new `SWUSeatAcceptsDecisions()` gates EVERY queue write from one place via `DecisionQueueController::AddDecision`, resolved by `function_exists` (not the registered-callback pattern, which only re-registers in the request that declares a winner). ⚠ Core file — verified inert for the other 9 sims (none defines the function; the guard evaluates false). Pin: `twinsuns/PendingDecisionOnASeatBeyondTwoBlocksActions.md::EliminatedSeatsPendingDecisionIsDrained_NoSoftLock`. |
| ⚠ **over-wide pools** (Pass 0's own `their<Zone>` widening) — **TRIAGED 2026-08-23: 6 cards, not 65.** Cross-referencing SCOPED card text ("that opponent"/"they control"/"the defending player") against a board-wide pool cuts 41+24 files to six. Clean (seat derived from the chosen object): `JTL_041`, `LAW_075`. **Real: `ASH_004` ✅ · `JTL_125` ✅ · `SEC_233` ✅ (both scoped with `ofSeat`) · `TS26_29` ✅ (rewritten 2026-08-24 to one simultaneous MZMULTICHOOSE over every seat) — ALL FOUR NOW CLOSED, verified 2026-08-27** (Ziton Moj is an unlisted LOOP card — "for each player, deal 1 to a unit that player controls"). New **`'ofSeat' => int`** on `SWUOfferUnitTarget`/`_SWUCollectUnitTargets` is the scoping tool. ⚠ `SWUOfferUnitTarget`'s forward list is a WHITELIST — a new option not added there is silently dropped. Pin: `ash/GrandAdmiralThrawn_VictoryIsMine.md::TwinSuns_Deployed_ComparesAndTargetsONLYTheDefendingSeat`. |
| harness four-seat gaps | ✅ **ALL CLOSED 2026-08-24** — new `P#UNITACTIONS{EXACT,HAS,NOT}`; `PlayFromOpponentDiscard: P<seat>:<idx>`; `P{n}OnlyActions`; `WithP{n}Credits`; far-seat `…ArenaControlled` / arena upgrades / pilots / captives / base upgrades / Force; and far-seat DEPLOYED leaders now splice a real arena unit. See `_FINDINGS.md` §4. |

### Pass 2

| item | status |
|---|---|
| 70 "neither helper" + 16 monolith cards — re-scan first, helper fixes move them | ✅ **READ 2026-08-27** — the shape sweep was already clean, and reading the cohort with a wider net found a THIRD shape (hand-decoded initiative, `SOR_163` + `SHD_101`). See "Item 6" below. |
| **+45 cards the text scan structurally missed** — the "defending player / that opponent / its controller" family (`_FINDINGS.md` §1b) | ✅ **CLOSED** — see the §1b row at the top of this file (33 clean, 5 real bugs, 4 tightened fallbacks, 3 false positives). |
| re-check the "31 seat-aware (considered)" bucket **per clause, not per file** (`_FINDINGS.md` §1c) | ✅ **CLOSED** — see the §1c row at the top of this file (one live, `LAW_202`, and it was a dead seat param). |

### GetMzID() AUDIT + "NEITHER HELPER" SWEEP — BOTH CLOSED (2026-08-27). Suite 9851 / 0.

**`GetMzID()` audit — 29 call sites, all now safe.** `GetMzID()` builds "my…"/"their…" from the AMBIENT
`$playerID`, so it CANNOT name a far seat: a seat-3 unit comes back as `theirGroundArena-N`, which
resolves to SEAT 2. Verdicts: 20 are `$newCard->GetMzID()` in the acting player's own frame (safe);
3 pin the frame first (`_SWUOnPlayerDrew`/LAW_052, the HMW_060 Rampart loop, `SWUMillTopCard`); 3 are
GrandArchive leftovers; 2 were fixed earlier this session (`SHD_084`, `TWI_033`); and
**`SWUCollectOwnPlayReactions` is now pinned defensively** — it walks `GetUnitsInPlay($playingPlayer)` and
hands `$u->GetMzID()` to effects without ever setting the frame. Every current caller happens to arrive
correct, so that one is hardening, not a measured bug.

**⚠ THE "NEITHER HELPER" BUCKET IS WHERE THE SCAN-PROOF BUGS LIVE.** These cards call NO seat helper, so
`OtherPlayer`/`GetOpponent` scans walk straight past them. Two shapes, five real bugs, ALL found by
reading rather than grepping:

| shape | what it looks like | found |
|---|---|---|
| literal seat integers | `SWUDealDamageToBase(1, 1); SWUDealDamageToBase(1, 2);` | `SOR_014` front Action · `SHD_160` |
| hand-built relative mzID | `$targets[] = 'theirBase-0';` | `JTL_142` · `TWI_202` · `SOR_142` |

`'theirBase-0'` is a STRING. It names seat 2 and nothing else, and there is no helper call to grep for.
All five now use `GetLiveSeatsArray()` / `SWUAllBaseMzIDs(…, 'any')`.
⚠ For `TWI_202` Jar Jar this also skewed a RANDOM pick — a four-seat table drew from 2 bases instead of 4,
so the odds were wrong as well as the reach.

**Sweep is now clean.** The residual hits are all deliberate: GrandArchive leftovers, UI/CSS strings,
four documented two-player fallbacks (`if (empty($seats)) $seats = [1, 2];`), and the goldfish dev tool
in `CustomInput.php`, whose own comment states it is 2-player by construction.

⚠ **Re-run BOTH sweeps after any new card work** — the shapes are cheap to reintroduce and impossible to
notice, since nothing about `'theirBase-0'` or `SWUDealDamageToBase(1, 2)` looks wrong in review.

### PASS 2 BODY — CLOSED (2026-08-27). 25 live clauses → 3, all three deliberate.

Suite **9850 / 0**, DevTools 29/29. Every clause with a live legacy seat call was read against its printed
text (front AND `deployTextData`), classified, and converted.

**The 3 that remain are correct and annotated in code — do not "fix" them:**
- `YODA_DRAW` (SOR_045) and `SOR_016#0` (Thrawn) — their SOLE CALLER gates on `SeatCountForGame() <= 2`
  and takes a per-seat path above that, so `OtherPlayer()` is only reachable where it is correct by
  definition. ⚠ **The scan cannot see this: the guard lives in the CALLER, not the handler.** Expect any
  future clause scan to re-flag them.
- `SHD_172#0` (Krayt Dragon) — ~~a REACHABLE fallback~~ **RE-MEASURED 2026-08-27: no longer reachable.**
  That note predated the fix to the PRODUCER. There is exactly one `AddTrigger(…'SHD_172'…)` in the repo
  and it now appends `~{playingSeat}`; a STDERR probe on the missing-seat branch fires **zero** times
  across all 9877 sections, and replacing the branch with a bare `return` leaves the suite fully green.
  The branch is kept only for a payload SERIALISED INTO A LIVE GAME before the seat was threaded (triggers
  ride the EffectStack, which persists in `Gamestate.txt`) and it no longer GUESSES: two seats keeps
  `SWUChooseOpponent` (correct by definition there), 3+ seats drops the trigger rather than damaging the
  wrong player's board. Deliberately UNGUARDED — no reachable state to assert, same treatment as
  `SEC_133`. **Lesson: re-measure a "known reachable" note before building on it.**

**★ NEW HELPER: `SWUQueueChoosePlayer($chooser, $handler, $tooltip, $eligible)`** (GameLogic).
The player-scoped sibling of `SWUQueueChooseOpponent`. They are NOT interchangeable: `ChooseOpponent`
starts from `OpponentsOf()`, which **excludes teammates** — right for "an opponent", WRONG for
"a player" / "a different player" / "any player's deck", where a teammate is a legal pick. Three cards
needed it (`LAW_215`, `LAW_048`, `TS26_01`) and one was already silently wrong because of it (`SOR_016`
offered `includeSelf` over `OpponentsOf`, so Thrawn could not look at his own teammate's deck).

**Shapes converted**
| shape | cards |
|---|---|
| DETERMINED — read the object/mzID | `SHD_132` `LAW_170` (exchange control) · `SEC_180` · `SHD_088` · `TS26_26` · `SHD_213` · `HMW_114` (Overwhelm excess) |
| PROMPT — an opponent / a player | `LAW_233` · `SEC_235` · `LAW_215` ×4 · `LAW_048` · `TS26_01` (choose 2 players) |
| UNQUALIFIED — "a base" / "a leader" spans every seat | `HMW_004` (offer + handler) · `SEC_188` |
| EXISTENTIAL / comparison | `SOR_013` (⚠ per-base, never a sum) · `ASH_108` ("the most units" = vs EVERY other player) |
| FAN-OUT | `LOF_177` (EACH player picks — restructured into a per-seat chain) |
| WHOLE-TABLE search | `HMW_151` · `LAW_171` (the event can be in ANY discard pile) |

**⚠ Two traps this batch surfaced, both worth knowing before the next conversion:**
1. **Converting inline work into a continuation changes WHEN the game-over check runs.** `SOR_152` broke
   `LethalToOpponentBase_NoArrangePromptAfterTheWin` because the follow-up prompt got queued before the
   damage landed. Queue the follow-up from INSIDE the continuation.
2. **A lone CUSTOM queued onto an IDLE player never drains.** `LAW_215` reddened five two-seat sections
   because the credits were deferred onto the controller's queue while the OTHER player's queue was
   draining. Resolve inline when there is no genuine choice; only defer when a real pick exists.

### ✅ HARNESS GAP FIXED (2026-08-27) — `P{n}OnlyActions` now holds the turn at 3+ seats

**Was:** `P{n}OnlyActions` silences other seats by giving one of them a CLAIMED initiative, which
`SWUSwapTurnPlayer` reads through `_SWUSeatTookCounterThisRound` to auto-pass them. The initiative counter
can name exactly ONE claimant, so at three or four seats every other opponent kept acting: after any
action that swaps the turn (an ATTACK being the common one) the turn walked to seat 3 and stopped, and the
section's next `P{n}>…` line was silently rejected because it was no longer that seat's turn.

That made **"attack, then act again as the same player" unwriteable at four seats**, which is a whole
fixture SHAPE, not a quirk — and it is exactly what `ASH_039` and `SEC_144` needed.

**Fix:** at 3+ seats, `applyPostSetupDirectives` also lists every other live seat in **`SWU_COUNTER_TAKEN`**
— the PRODUCTION variable for "seats that have taken a counter this round", which
`_SWUSeatTookCounterThisRound` already consults. No test-only hook was needed: it states, in the engine's
own vocabulary, the thing the directive means. Untouched at two seats, so every existing
`P1OnlyActions` section is byte-identical.

⚠ **The diagnosis cost more than the fix.** Four wrong hypotheses first (resources, `handCardIds` vs
`WithP1Hand`, `SkipPreGame`, the CommonSetup form). What isolated it was noticing the card played fine
WITHOUT the attack — and then a two-seat control passing beside a four-seat twin that failed. When a
4-seat fixture misbehaves, **check the harness against a 2-seat control before suspecting the card.**

`ASH_039` and `SEC_144` are now both pinned and mutation-verified.

### ⚠ SECOND HARNESS GAP — no far-seat LEADER directive (found 2026-08-27, NOT fixed)

There is no `WithP3Leader` / `WithP4Leader`. `CommonSetup` covers seats 1-2 only (`myLeader`/`theirLeader`),
so a section cannot put a seat-3/4 leader into a chosen state.

**Blocks pinning `SEC_188` Darth Traya.** "Ready A NON-UNIT LEADER" is UNQUALIFIED, so every seat's leader
is a candidate; the old picker was a literal You/Opponent pair from `OtherPlayer($p)` — seat 2 only — and
the fix offers one `P{n}` option per seat holding an exhausted, undeployed leader. A DISCRIMINATING section
needs a FAR seat to hold that leader. A section answering `P1` passes under the bug too, so it would be a
guard that cannot fail; deliberately not written rather than banked as false coverage.

Unblock by adding a per-seat leader directive, then pin "P1 readies SEAT 4's leader".
⚠ Also note: appending an HTML comment AFTER the last section of a .md case file breaks that section —
the runner folds trailing text into it. Put notes like this here, not in the case file.

### Known gaps (fixed code, unproven at 4 seats)

- `ASH_162` Rash Action and the `'OppDiscard'` modal branch have **no 4-seat section**. Correct by
  construction and green, but nothing pins them. ASH_162 needs a granted-keyword combat fixture;
  `'OppDiscard'` needs a modal-choose one (SHD_153 Poe Dameron).
- ~~**Pass 0 (2026-08-23):** the `SWU_DMGDBASE` base-damage stamp has no 4-seat pin; the any-player
  unit-action offer is UNPINNABLE until the harness gains a unit-action offer-list assertion.~~
  ✅ **BOTH CLOSED — this note was stale on both counts (corrected 2026-08-27).** The any-player
  unit-action offer was pinned **2026-08-24** by `P#UNITACTIONS{EXACT,HAS,NOT}` (see the row at line ~556:
  reverting the `OpponentsOf()` loop reds 3 sections across `LAW_156`, `SHD_256`, `TS26_15`). The
  `SWU_DMGDBASE` pin was built 2026-08-27 — see "Item 3" below, which also found three real bugs while
  writing it. ⚠ Third stale "blocked/unpinnable" note found in one session; re-measure before believing one.
- **Pass 1 DETERMINED — CLOSED 2026-08-23.** All 11 converted; 10 mutation-verified. Only `SEC_133`
  remains unpinned, and deliberately: its residual defect is an unreachable `?:` fallback, so there is no
  failing state to assert.
  ⚠⚠ **THE FIXTURE LESSON, twice over.** `SHD_172`'s first pin put the Krayt on seat 3 and the PLAYING
  player on seat 1 — and TWO of its three mutations still passed, because for a seat-3 frame both
  `SWUChooseOpponent(3)` and `$playerID == 1 ? 2 : 1` answer seat 1, which WAS the playing player. The
  legacy code was right by accident, exactly as `SHD_161`'s bounty section is. Retargeting to
  "Krayt on seat 3, card played by seat 4" separated every cause.
  **Rule: choose the seats so the LEGACY answer and the CORRECT answer are different seats, then verify
  that by mutation — a 4-seat section is not automatically a discriminating one.**
- **Named follow-ups recorded in code (`STILL OWED` comments):** `JTL_221`'s interactive picker (its
  handler is synchronous — use the SOR_016 seat-count gate), `ASH_004`'s front-side Restore timing (the
  condition runs before `BeginSWUAttack`, so the defender is not yet declared), and `TS26_29` Ziton Moj
  (an unlisted LOOP card: "for each player, deal 1 to a unit that player controls").

### Standing rules for every card in this sweep

- **Run the FULL suite after every card** — a fixed card may be a FIXTURE elsewhere (Pillage went 10/10
  locally while breaking two `law/TransmissionJamming` sections).
- **Every card needs one section that CANNOT PASS AT TWO SEATS**, mutation-verified by reverting to the
  legacy helper. A green 2-player suite proves only that Premier did not regress.
- **Expect ~3 defects per card, not 1** — the target seat, the silent "is there anything to hit?" GATE,
  and a `?:` fallback that guesses a seat. Roughly one card in three also hides a second, unrelated bug
  (an unimplemented leader side, a rider reading the wrong pile).

---

## Guard pass, part 2 (2026-08-27) — the four "fiddly multi-step" conversions

All four are now pinned and mutation-verified, so the unpinned list from part 1 is down to the
unpinnable-by-construction cases.

| Card | Section | Mutation that reddens it |
|---|---|---|
| `SOR_152` For a Cause I Believe In | `sor/ForACauseIBelieveIn.md::FourSeats_ChoosesWHICHEnemyBaseTakesTheDamage` | `SWUPickedOpponent($lastDecision)` → `OtherPlayer($player)` |
| `TWI_017` Chancellor Palpatine | `twi/ChancellorPalpatine_PlayingBothSides.md::FourSeats_VillainyFaceHitsEVERYEnemyBase` | the `OpponentsOf()` fan-out → a single `OtherPlayer()` hit |
| `HMW_004` Grand Moff Tarkin | `hmw/GrandMoffTarkin_TyrantOfTheOuterRim.md::FourSeats_RegroupDefeatsTheCHOSENSeatsBase` | offer → `['myBase-0','theirBase-0']` (P4 not a candidate) **and** applier → the my/their string collapse (P2 dies instead of P4) |
| `LAW_215` Vermillion | `law/Vermillion_QirasAuctionHouse.md::FourSeats_CreditChooserIsVermillionsController` + `…::FourSeats_TeammatesDeckIsInThePoolAndMayTakeTheCredits` | chooser → `$D`; deck pool → `OpponentsOf($V)` |

⚠ **`SOR_019` Security Complex is a 25-HP base, not 30.** The first HMW_004 fixture seeded `SOR_019:25`
for P4, which had already eliminated that seat at setup — the section passed vacuously and the mutation's
"winners [1,3]" was the tell (killing P2 wiped a team whose other seat was already gone). Re-seeded at
`:20`. Check a base's printed HP before choosing a damage number.

### Three bugs found while writing these guards

1. **`LAW_215` credits chooser was the revealed DECK'S OWNER, not the Vermillion's controller.** `$D` was
   threaded down the whole `#1 → #2 → #2P/#3/#3P` chain and handed to `SWUQueueChoosePlayer`. Identical to
   the correct behaviour whenever you reveal your own deck; reveal an opponent's and the prompt landed on
   THEM — and since that seat is idle at that moment, its queue never drained in the request, so the
   Credits silently never appeared at all. `$V` is now threaded alongside `$D`.
2. **`LAW_215`'s deck pool excluded teammates.** "Reveal the top card of **a deck**" is unqualified, so it
   spans every live seat. It walked `OpponentsOf($V)`; with only a teammate's deck stocked the pool came
   back empty and the entire When-Attack-Ends trigger fizzled with no reveal, no play and no Credits.
3. **`SWUPlayerPickerLabels` / `SWUDeckPickerLabels` were opponent-scoped — a 12-call-site family.** Both
   enumerated `OpponentsOf($caster)`, deleting your partner from every "choose a player" / "a deck" offer.
   Every caller prints unqualified text (`SOR_171`, `SOR_156` Force Throw, `SEC_073`-adjacent
   `SEC` Pursue the Lead / Hired Slicer / Regulations Bureaucrat, `JTL` Profundity / Sabine's Masterpiece,
   `ASH` Reanimated Night Trooper, `LAW_018`, `LAW_125`, `LAW_215`), so both now walk
   `GetLiveSeatsArray()`. All nine player-picker handlers already decode to a bare seat and act on it, so
   no downstream change was needed. The two deck-picker cards' `OtherPlayer()` uses are gated behind
   `SeatCountForGame() <= 2` and stay correct.

### ⚠ OPEN — OPTIONCHOOSE answers are NOT pool-validated (blocks guarding this whole family)

`SWUValidateDecisionAnswer` falls through for `OPTIONCHOOSE`, so an answer naming a seat the offer never
contained still resolves — every decoder (`SWUDecodePlayerPick`, `SWUDecodeDeckPick`) happily reads any
`P{n}` token out of any string. **A picker's SCOPE is therefore structurally untestable**: the `SOR_171`
guard above pins the outcome but passes under the reverted (opponent-only) picker. This is the same hole
that was closed for `PASSPARAMETER` and `MZMULTICHOOSE`.

A 10-line patch (kept at `/tmp/optionchoose_validator.patch` during the 2026-08-27 session; trivially
re-derived: compare the answer against `explode('&', $head->Param)`) closes it, and immediately turns up:

- **1 live engine bug — `SOR_189` Leia Organa, Defiant Princess.** Its mandatory either/or is queued as
  `AddDecision(…, "OPTIONCHOOSE", "Ready a resource&Exhaust a unit", …)`. A DecisionQueue row is
  **space-delimited and `$param` is NOT sanitised** (`AddDecision` underscores the *tooltip* only, and says
  so in a comment). The row therefore stores `Param="Ready"` with `Tooltip="resource&Exhaust"`: the real
  client renders a single "Ready" button, the answer comes back as `"Ready"`, which fails the handler's
  `=== "Ready a resource"` test and falls to the ELSE branch — **so "ready a resource" is unreachable in
  play and Leia always exhausts a unit.** A `token_get_all()` scan of every `AddDecision()` call in
  `SWUSim/Custom/` for a literal `$param` containing a raw space returns **exactly this one hit**; worth
  landing as a DevTools lint next to `dontskiponpass_zero_min_test.php`.
- **~13 existing sections whose answers were never in the offer** and only resolved because of decoder
  leniency: `sec/PursueTheLead` (`Self` vs `[You&Opponent]`), `sec/DarthTraya_LordOfBetrayal` ×3
  (`You`/`Opponent` vs `[P1&Pass]` / `[P1&P2&Pass]` / `[P2&Pass]`), `law/JynErso_TakeTheNextChance` ×3
  (`Experience` vs `[GiveExperience&Exhaust]`), `law/Watchful` ×3 (`Yours`/`Theirs` vs
  `[@-&Your_deck&Opponent's_deck]`), `law/VultSkerrissDefender_SecretProject` and
  `twi/UnmaskingTheConspiracy` (an mzID answered to an `[@CARD&OK]` reveal prompt).
- **1 that needs investigating before its answer is "corrected":**
  `hmw/BactaTank::fortify_attaches_to_the_base_and_its_action_becomes_available` answers
  `myGroundArena-0` to an `[Heal0&Heal1&Heal2&Heal3]` prompt. An mzID answered to an amount prompt is the
  auto-resolve-artifact shape (memory: `auto-resolve-artifact-fake-engine-bug`) — the section may be
  answering the wrong prompt and asserting an outcome two rules share.

**LANDED 2026-08-27 (user go-ahead).** `SOR_189`'s labels are underscored and its handler compares the
underscored form; all 17 sections were corrected to the label the offer actually contains
(`Self`→`You`, `You`/`Opponent`→`P1`/`P2`, `Experience`→`GiveExperience`, `Yours`/`Theirs`→
`Your_deck`/`Opponent's_deck`, `Top`→`Leave`, the two reveal-prompt mzIDs→`OK`, and Bacta Tank's spare
`myGroundArena-0` deleted outright — its target auto-resolves, so the amount was always the first
question). Suite 9871/0. New lint `SWUSim/DevTools/tests/adddecision_param_space_test.php` scans every
`AddDecision()` call in `SWUSim/Custom/` (1570 files) for a literal `$param` holding a raw space, with
three self-tests; it reddens on the pre-fix Leia source.

**The validator immediately paid for itself twice more.** `sor/MissionBriefing::FourSeats_ChooseAPlayer
IncludesYourTeammate` now DISCRIMINATES — reverting `SWUPlayerPickerLabels` to `OpponentsOf()` fails it
with `OPTIONCHOOSE [You&P2&P4]`, the missing `P3` visible in the message. And `law/Watchful`'s three
sections turned out to be answering `Top` at a prompt whose labels are `[@card&Bottom&Leave]`.

---

## Item 3 (2026-08-27) — `SWU_DMGDBASE`: no code bug in the stamps, three elsewhere

All five stamp sites (`CombatLogic.php` 27 / 491 / 3021 / 4233, `GameLogic.php` 12961) and all three
readers (`CombatLogic.php` 1483 / 3471, `Retaliation.php`) are already owner-qualified with a real seat —
the open item was only the missing 4-seat pin. Writing that pin turned up three genuine bugs instead.

1. **`SWUAllBaseMzIDs($p, 'any')` never included a TEAMMATE's base.** It was `'my'` + `'their'`, and
   `'their'` is ZoneSearch's OPPONENT fan-out. `SWUAllUnits()` already carries a comment spelling this
   out — *"once 'their' excludes a teammate, 'my' + 'their' no longer covers the table, so an unqualified
   pool must start from 'team'"* — and it was fixed for units and left open for bases. **~35 unqualified
   "a base" offers** were silently missing the partner's base (`IBH_006`, `LAW_058`, `LAW_208`, `SOR_010`,
   `JTL_247`, `TS26_62`, `HMW_004`'s regroup defeat, the shared `DEAL_BASE_DAMAGE` / heal helpers, …).
   Fixed with an explicit `SWUTeammatesOf()` branch; `SWUTeammatesOf` returns `[]` outside a team game, so
   2-player and free-for-all Twin Suns are byte-identical.
2. **`_SWUSec012Protected` read "an opponent's base" as "anyone's base but mine".** In Team Suns those
   differ by exactly one seat — your partner. Pinging a teammate's base wrongly made the unit unattackable.
   Now `OpponentsOf($ctrl)`. ⚠ This was UNREACHABLE until (1) was fixed: the two bugs masked each other.
3. **The harness's `declareAttack` injected the attack target WITHOUT validating it** — unlike
   `answerDecision`, which has mirrored production's check since 2026-08-14. A section could therefore
   attack a unit the legality pool had deliberately excluded (SEC_012, Sentinel, Hidden, `LOF_211`,
   `SOR_142`/`TWI_195` Sabine, `ASH_035`). At two seats it was mostly invisible — with one target left the
   attack resolves INLINE with no picker, so a bogus mzID was simply ignored and the right thing happened
   anyway — but at 3+ seats the extra bases mean a picker DOES appear and the injection went through.
   Measured: a SEC_012-protected unit was attacked and killed.

   Closing it reddened **13 Twin Suns sections** that address the defender as `theirGroundArena-0` /
   `theirBase-0` / a bare index — forms the 3+-seat pool (and the real client) never produces. All 13
   were rewritten to the seat-specific `P{n}G{i}` / `P{n}S{i}` / `P{n}B` syntax the runner already
   supports: `SHD_143` Bo-Katan, `SHD_?` Wanted, `SEC_?` Arihnda Pryce, `LAW_?` Rodian Bondsman,
   `JTL_?` CR90 Relief Runner, `SOR_?` Sabine Explosives Artist, `HMW_?` Giant Gorax ×2, `HMW_?` The
   Chieftain, `TS26_?` Wartime Refugee ×2 / Mercenaries / Profiteer.

**Guards:** `ibh/RebellionYWing.md::TeamSuns_OfferPoolIncludesTheTEAMMATESBase` (pool) and
`sec/CassianAndor_Climb.md::TeamSuns_ATeammatesBaseIsNotANOPPONENTSBase` (scope). The second is
mutation-verified against BOTH fixes and fails at a different step for each — at the ANSWER when the pool
is narrowed, at the ATTACK when the opponent test is widened.

---

## Item 2 (2026-08-27) — the `-` decline sweep, measured rather than assumed

The backlog entry said "~510 assertions still testing a decline the client can't produce". That was only
partly right, and the cheapest way to find out was to run the experiment: copy the whole case tree,
rewrite every `- P{n}>AnswerDecision:-` to the client's token, and diff the pass set.

**Census of the 617 executed `-` answers, by the type of the decision actually pending:**

| Type | n | Client's real token | Verdict |
|---|---|---|---|
| `MZMAYCHOOSE` | 400 | `PASS` (three `SubmitInput(… cardID=PASS)` sites) | **3 live bugs**, below; the rest equivalent |
| `MZMULTICHOOSE` | 114 | **`-`** — `Core/MZMultiChooseUI.js:600` `serializeResult()` returns `'-'` for an empty selection, and the cancel/parse-fail paths submit `'-'` too | ✅ already correct, nothing to do |
| `TOPDECKSEARCH` | 42 | `''` (empty join) | equivalent — 0 divergences |
| `YESNO` | 38 | `NO` | equivalent — 0 divergences |
| `MZSPLITASSIGN` | 7 | `PASS` | equivalent — 0 divergences |
| *(empty queue)* | 13 | — | **dead WHEN lines**, listed below |
| `SCRY` / `OPTIONCHOOSE` / `CUSTOM` | 1 each | — | noise |

So the premise "`-` is never a client token" is FALSE for `MZMULTICHOOSE`, which is the single biggest
reason not to have mass-rewritten these. The 400 `MZMAYCHOOSE` lines do use a token the client cannot
send, but they are behaviourally identical under `PASS` — the `dontskiponpass_zero_min_test.php` lint
already forces a real `PASS` twin for the shapes where it matters, so rewriting them buys nothing.

### The three bugs

1. **`SEC_193` Grand Admiral Thrawn.** `MZMAYCHOOSE` + unflagged `CUSTOM`. The opponent declining is a
   real answer — "no unit is captured, so Thrawn readies" — but a sticky `PASS` skipped `SEC_193#0`
   entirely and Thrawn stayed exhausted. The handler's own `$lastDecision !== 'PASS'` branch was
   unreachable.
2. **`JTL_253` Coordinated Front.** Two INDEPENDENT optional grants; the trailing `CUSTOM` that runs the
   SPACE half was unflagged, so declining the GROUND half with `PASS` swallowed the space half too — the
   event did half of what it says.
3. **`SWUQueueChooseTarget(…, may: true)` — the second funnel into the whole hazard.** Its sibling
   `SWUQueueMayChooseTarget` defaults `dontSkipOnPass` to 1; this one queued the continuation unflagged,
   for **9 callers**. Measured on `SOR_129` Admiral Ozzel: declining the play half skipped `OZZEL_PLAY`,
   so "each opponent may ready a unit" never happened AND the action never closed. Fixed centrally —
   a mandatory `MZCHOOSE` cannot be passed at all (the validator refuses it), so non-`$may` callers are
   byte-identical.

### CLOSED — 9 `-` answers landed on an EMPTY queue (dead WHEN lines), all removed

They resolved nothing: `PopDecision` on an empty queue, then a no-op. Harmless as they stood, but this is
the auto-resolve-artifact shape — a spare answer that silently absorbs a real prompt the moment one
appears, which is exactly how a phantom "Mina When-Defeated" bug was once logged. Removed 2026-08-27;
suite 9879/0 and a re-instrumented sweep now reports **zero** remaining.

| section | removed action |
|---|---|
| `ash/ElzarMann_HauntedByAVision::EntersReady_WithForceLeader` | #2 |
| `ash/ElzarMann_HauntedByAVision::EntersExhausted_NoForceLeader` | #2 |
| `ash/ElzarMann_HauntedByAVision::ZeroTokens_NoOpponentDraw` | #2 |
| `ash/JodNaNawood_KeepingSecrets::WhenPlayed_DeclinePay` | #2 |
| `law/VultSkerrissDefender_SecretProject::ADeckMillAlsoSatisfiesTheGate` | #2 |
| `lof/CuriousFlock::ResourceCapped_PayMax` | #4 |
| `lof/KyloRen_WereNotDoneYet::Deployed_PlayTwoUpgradesFromDiscard` | #4 |
| `shd/Headhunting::MultiAttack_BountyHunterBonus` | #4 |
| `twi/OsiSobeck_WardenOfTheCitadel::PaidSix_CapturesCostSixOrLess` | #2 |

⚠ **The first census of this set was WRONG, and the way it was wrong is worth remembering.** It wrote the
marker to STDERR and attributed each one to the next `PASS:` line on STDOUT — but the two streams buffer
independently, so the correlation drifted: it named `law/EnfysNestsHelmet`, `law/NothingLeftToFear` and
`lof/LuminousBeings` (none of which have a dead line) and MISSED `lof/KyloRen_WereNotDoneYet` and
`ash/ElzarMann::EntersExhausted_NoForceLeader`. The reliable form collects the marker into a global that
the RUNNER prints per section on the same stream, tagged with the action index from `$action['raw']`.
**Never correlate a STDERR probe against STDOUT test output by interleaving.**

---

## Item 4 (2026-08-27) — `GetMzID()` fixed in the GENERATOR, not at 29 call sites

`ZoneClasses.php`'s `GetMzID()` built its prefix from the ambient `$playerID`
(`$prefix = $playerID == $this->PlayerID ? "my" : "their"`). `"their<Zone>"` names no seat, so above two
seats a FOREIGN object's mzID resolved to whichever opponent the READER's frame happened to pick — seat 2
for a seat-1 reader, and nothing usable for a reader at seats 3-4. Every other producer of an unqualified
pool had already been converted to real `p{n}` mzIDs (ZoneSearch's opponent fan-out, `SWUAllUnits`,
`SWUAllBaseMzIDs`); `GetMzID()` was the last one still speaking the two-seat dialect, and its ~29 call
sites hand the result straight to damage / token / trigger APIs.

**Fixed once, in `zzGameCodeGenerator.php`, gated on `$rootName == "SWUSim"`** — `ZoneClasses.php` is
generated AND gitignored, so a hand-edit there has no git trace and dies at the next regen. Above two
seats a foreign object now returns `p{owner}<Location>-{idx}`; two-player is byte-identical, which is what
keeps the entire existing engine, client and 9877-section corpus working unchanged. Regenerate with
`php zzGameCodeGenerator.php rootName=SWUSim`.

Guarded by `SWUSim/DevTools/tests/getmzid_seat_aware_test.php` (own seat, all three foreign seats read
from seat 1, seat 1 read from SEAT 4, and both seats at a two-player table). Mutation-verified by
reverting the generator and regenerating: `"seat 2's unit reads as p2GroundArena-0 … got:
theirGroundArena-0"`. ⚠ If that test fails after a pull, the fix is to REGENERATE, not to edit.

---

## Item 6 (2026-08-27) — a THIRD "neither helper" shape: hand-decoded initiative

The shape sweep had found two seat-proof shapes (literal seat integers, hand-built relative mzIDs) and
both were clean. Re-reading the cohort with a wider net turned up a third, which no previous scan looked
for because it contains no seat helper, no mzID and no seat literal in an obviously-seat position:

```php
$holder = strpos((string)GetInitiativeCounter(), 'P1') === 0 ? 1 : 2;   // "not P1" => seat 2
if ($holder === intval($player)) …
```

**Two cards decoded the initiative counter by hand, and both are wrong twice over above two seats:** a
seat-3/4 initiative holder never satisfies the gate, AND seat 2 satisfies it on someone else's
initiative. `PlayerHasIniative($seat)` has always existed and matches `P{seat}_CLAIMED` /
`P{seat}_UNCLAIMED` for any seat (the engine's `Iniative` typo is load-bearing).

| card | clause | guard |
|---|---|---|
| `SOR_163` Star Wing Scout | "When Defeated: **If you have the initiative**, draw 2 cards." | `sor/StarWingScout.md::FourSeats_AFARSeatsInitiativeCounts` |
| `SHD_101` Adelphi Patrol Wing | "…**If you have the initiative**, it gets +2/+0 for this attack." | `shd/AdelphiPatrolWing.md::FourSeats_AFARSeatsInitiativeGrantsTheBuff` |

Both mutation-verified by restoring the string decode (P3 draws 0 instead of 2; P2's base takes 2
instead of 4). `jtl/FaceOff` also reads `GetInitiativeCounter()` but only tests `CLAIMED`/`UNCLAIMED`,
which is seat-independent — correct as written.

⚠ **Scan shapes are a floor, not a ceiling.** Both of these were invisible to the `OtherPlayer` /
`GetOpponent` / `theirBase-0` / literal-seat scans; what found them was widening the net to "any
expression that produces a SEAT NUMBER from something that is not a seat helper". The generalised rule
for future sweeps: grep for the *outputs* (a variable named `$holder`/`$seat`/`$target` assigned from a
ternary or a `strpos`), not only for the known-bad *inputs*.
