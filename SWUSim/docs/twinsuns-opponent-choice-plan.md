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
| **66 inline `=== 1 ? 2 : 1` ternaries** (triage, don't bulk-replace). ⚠ Remaining `foreach([1,2])` loops in `SWUSim/Custom/GameLogic.php` (4) are **GrandArchive leftovers** (`myField`/`GetField`/CHAMPION/CURSE) from the original template — GA is 2-player, leave them. | ☐ |
| **create `SWUSim/docs/leader-gaps.md`** (cited by the card skill, does not exist) | ☐ |

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
| `AddGameLogEntry` cannot express 2-seat visibility (`GetNextTurn.php:250-253`) | ☐ |
| **`IsSeatLive` / dead-seat queues** — §5 checklist item 7 | ✅ **mutation-verified**. Two halves: (1) `_SWUEliminationCleanup` now DRAINS the eliminated seat's DecisionQueue **and TempZone** — it cleaned arenas and the base but left the queue, and nothing else ever drains it, so every "wait for everyone" gate (`AllQueuesEmpty`, TurnController `PENDING_DECISION`, ~10 `CustomInput` sites) blocked forever — **a SOFT-LOCK, not a lost trigger**; (2) new `SWUSeatAcceptsDecisions()` gates EVERY queue write from one place via `DecisionQueueController::AddDecision`, resolved by `function_exists` (not the registered-callback pattern, which only re-registers in the request that declares a winner). ⚠ Core file — verified inert for the other 9 sims (none defines the function; the guard evaluates false). Pin: `twinsuns/PendingDecisionOnASeatBeyondTwoBlocksActions.md::EliminatedSeatsPendingDecisionIsDrained_NoSoftLock`. |
| ⚠ **over-wide pools** (Pass 0's own `their<Zone>` widening) — **TRIAGED 2026-08-23: 6 cards, not 65.** Cross-referencing SCOPED card text ("that opponent"/"they control"/"the defending player") against a board-wide pool cuts 41+24 files to six. Clean (seat derived from the chosen object): `JTL_041`, `LAW_075`. **Real: `ASH_004` ✅ fixed+mutation-verified · `JTL_125` ☐ · `SEC_233` ☐ · `TS26_29` ☐** (Ziton Moj is an unlisted LOOP card — "for each player, deal 1 to a unit that player controls"). New **`'ofSeat' => int`** on `SWUOfferUnitTarget`/`_SWUCollectUnitTargets` is the scoping tool. ⚠ `SWUOfferUnitTarget`'s forward list is a WHITELIST — a new option not added there is silently dropped. Pin: `ash/GrandAdmiralThrawn_VictoryIsMine.md::TwinSuns_Deployed_ComparesAndTargetsONLYTheDefendingSeat`. |
| harness four-seat gaps | ✅ **ALL CLOSED 2026-08-24** — new `P#UNITACTIONS{EXACT,HAS,NOT}`; `PlayFromOpponentDiscard: P<seat>:<idx>`; `P{n}OnlyActions`; `WithP{n}Credits`; far-seat `…ArenaControlled` / arena upgrades / pilots / captives / base upgrades / Force; and far-seat DEPLOYED leaders now splice a real arena unit. See `_FINDINGS.md` §4. |

### Pass 2

| item | status |
|---|---|
| 70 "neither helper" + 16 monolith cards — re-scan first, helper fixes move them | ☐ |
| **+45 cards the text scan structurally missed** — the "defending player / that opponent / its controller" family (`_FINDINGS.md` §1b) | ☐ |
| re-check the "31 seat-aware (considered)" bucket **per clause, not per file** (`_FINDINGS.md` §1c) | ☐ |

### Known gaps (fixed code, unproven at 4 seats)

- `ASH_162` Rash Action and the `'OppDiscard'` modal branch have **no 4-seat section**. Correct by
  construction and green, but nothing pins them. ASH_162 needs a granted-keyword combat fixture;
  `'OppDiscard'` needs a modal-choose one (SHD_153 Poe Dameron).
- **Pass 0 (2026-08-23):** the `SWU_DMGDBASE` base-damage stamp has no 4-seat pin; the any-player
  unit-action offer is **UNPINNABLE** until the harness gains a unit-action offer-list assertion.
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
