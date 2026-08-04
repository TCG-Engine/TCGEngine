# IC27 — Card Implementation Plan

**⚠ PREVIEW SET.** All 15 cards are mock entries in `SWUSim/Custom/CardMocks.php` (imported via
`zzPreviewTool.php`), not official-API data. Icons 2027 Edition releases **2026-11-20**. The card
list GROWS as previews land — re-run `swusim-generate-set-implement-doc IC27` after each import;
the `### Already Done` line survives regeneration.

15 cards total: 2 Leaders, 12 Units (10 Ground, 2 Space), 1 Event.
**15 needs-work, 0 auto-wired** — no vanilla and no keyword-only cards. Every card is a named
character with a real ability, which is what an "Icons" marquee set looks like.

### Already Done
IC27_067, IC27_071, IC27_104, IC27_187, IC27_146, IC27_158, IC27_079, IC27_167, IC27_022, IC27_026, IC27_024, IC27_168, IC27_078, IC27_008, IC27_001

## Foundations already built — do not re-do

The Stage 0.5 survey found **no unbuilt core mechanic**. Two seams looked new and each has an exact
precedent — check these before designing anything:

| Needed by | Already built | Precedent to copy |
|---|---|---|
| IC27_071 dynamic value keyword ("Raid 1 **for each** …") | yes | **SEC_171 Punishing One** — `KeywordEffects.php:1049`, same shape |
| IC27_008 / IC27_001 "put a card from your hand on the **top or bottom** of your deck" | yes | **TWI_004 Yoda (Sensing Darkness)** — identical clause; also `DoScry` |
| IC27_026 "when you heal damage from your base" | yes | `OnHealBase()` — `CombatLogic.php:715` |
| IC27_078 static ability active **from the discard pile** | yes | LAW_212 Malakili (owner-aware out-of-play) |
| IC27_078 aspect-penalty waiver scoped to a card **name** | yes | HMW_004 Tarkin / SOR_008 Hera at the `SWUAspectPenalty` chokepoint; Qi'ra SHD_202 for TITLE matching (subtitle excluded) |
| IC27_067 keyword **aura** over other friendly units | yes | JTL_047 Yularen + the `KeywordEffects.php` friendly-grant loops |
| IC27_167 return a resource to its **owner's** hand | yes | `SWUReturnResourceToHand` (respects Owner — LAW_029 Arquitens) |
| Experience token / Sentinel-this-phase / Restore / Ambush / Raid | yes | `DoGiveExperienceToken`, `GRANT_PHASE_KEYWORD`, `GeneratedKeywordCode.php` |

**Reprints already mapped, not yet imported:** `AppCore/SWU/Overrides.php:122-128` folds IC27_097,
_015, _108, _136, _154, _188, _194 into earlier printings. None of the 15 imported cards is a
reprint (verified: every title+subtitle is new against every printing in the dictionaries). Reprints
that land later classify as auto-wired — fold them into the earliest printing's file, don't re-implement.

**⚠ Mock-text typo to fix while you're in there:** IC27_158 reads "If it's returned to your hand**.**
you may play it for free." (period, should be a comma). Cosmetic only — nothing matches on that
phrase — unlike HMW_127's "Attach **of**", which attach logic *did* match.

## Phase 1 — Keyword modification (autonomous)

Both are **no-stub passives** (the stub generator doesn't detect them) living in `KeywordEffects.php`.
Ordered first because IC27_078 (Phase 6) needs a card named "Darth Vader" in the pool to test against.

- [x] **Batch 1.1 — IC27_067, IC27_071** — done, 18 sections, suite 6100/0. Zero new plumbing: both
  riders are one `case` each in the existing shared switches (`HasConditionalKeyword_Ambush`'s
  granter loop, which already self-excludes by UniqueID; `GetConditionalKeyword_Raid_Value`, whose
  contribution the generated `GetKeyword_Raid_Value` adds on top of the printed value). The per-card
  files carry only a pointer comment.
  - IC27_067 Darth Vader (Useless to Resist): Ambush (auto-wired) + aura "each **other** friendly
    unit gains Ambush". Self-exclusion is the load-bearing negative; the aura must also cover units
    that enter play *after* him.
  - IC27_071 Avar Kriss (For Light and Life): Raid 1 (auto-wired) + "gains Raid 1 for each **other**
    friendly unit" — copy SEC_171. The two sources SUM (printed 1 + N), and the value must be
    recomputed live as the board changes, not snapshotted at entry.

## Phase 2 — Combat-window triggers (autonomous)

- [x] **Batch 2.1 — IC27_104, IC27_187** — done, 11 sections, suite 6111/0. IC27_104 routes its
  per-player discards through an intermediate CUSTOM (`IC27_104#0`) because a mandatory relative-mzID
  pick queued straight from an On Attack closure is silently skipped; IC27_187 is a direct closure
  (`SWUMillTopCard` + `SWUAddAttackPowerBonus`, no decisions).
  - IC27_104 The Inquisitor's TIE (Would Rather Win) — **Space**. On Attack: "**each** player with 4
    or more cards in their hand discards a card." Symmetric — it discards from YOUR hand too, and the
    4-card threshold is evaluated per player. Exactly-4 vs exactly-3 is the boundary pair.
  - IC27_187 Jar Jar Binks (Bumbling Representative): On Attack: discard a card from your **deck**
    (mill, not hand); if it costs 6 or more, +4/+0 for this attack. Boundary at cost 5/6; an empty
    deck must not fatal; the buff lasts the attack only.
- [x] **Batch 2.2 — IC27_146** — done, 7 sections, suite 6118/0. Structural twin of LAW_252 Fett's
  Firespray: the AddTrigger sits ABOVE the attacker-survival early-return in
  `SWUCollectCombatHitTriggers`, so it fires when Boba trades (the card's own subtitle). The "may"
  is skipped entirely when no resource is exhausted (no pointless prompt).
  - IC27_146 Boba Fett (Compensated If He Dies): When Attack Ends: if the **defending unit** was
    defeated, you may ready 2 resources. Negatives that prove the gate: defender survived; the
    attack hit a BASE (no defender at all); Boba himself died in the trade (the trigger must still
    fire — see the ASH attacker-death family). `READY_RESOURCE` currently has a single call site.
- [x] **Batch 2.3 — IC27_158** — done, 8 sections, suite 6126/0. Pay-gate mirrors JTL_096 Blue Leader
  (`SWUTotalPaymentCapacity` -> YESNO -> `SWUOfferAltPayment` -> a new `IC27_158_PAY` case in the
  alt-pay switch), the bounce+replay mirrors SHD_207 (reusing `LOF_185#2`) but scopes the free play to
  the FALCON'S CONTROLLER rather than the owner. No offer when there is no legal target. Guarded with
  a SimulateRequestBoundary section. Mock typo ("hand. you") fixed in CardMocks.php.
  - IC27_158 Millennium Falcon (YA-HOO!) — **Space**. When Attack Ends: may pay [1 resource]; if you
    do, return a friendly unit costing 3 or less to its **owner's** hand; if it returned to **your**
    hand, you may play it for free. ⚠ Own batch — three chained decisions across a request boundary
    (the session-100 bug class: anything written before an `AddDecision` and read behind it MUST be
    serialized). The owner/controller divergence is the headline negative: controlling an
    enemy-**owned** unit means it returns to the opponent's hand and you get NO free play. Also test
    decline-at-each-step, cost-4 unit excluded (boundary 3/4), and no ready resource to pay with.

## Phase 3 — Entry triggers & resource swings (autonomous)

- [x] **Batch 3.1 — IC27_079, IC27_167** — done, 9 sections, suite 6135/0. IC27_079 is a registered
  STAT_BUFF token (`'IC27_079' => ['kind'=>'STAT_BUFF']`) so phase expiry is central. IC27_167 is two
  MZMULTICHOOSEs (`3|3` mandatory return -> `0|3` optional resource), both processed DESCENDING by
  index. ⚠ "Fewer than 3 resources" is UNREACHABLE — Lando costs 3 and paying exhausts rather than
  removes, so >=3 always remain when the ability resolves.
  - IC27_079 Qui-Gon Jinn (Unwavering Belief): Sentinel (auto-wired) + When Played: give **another**
    friendly unit +2/+2 for this phase. Self-exclusion; sole-unit case (no legal target); the buff
    must EXPIRE at end of phase.
  - IC27_167 Lando Calrissian (Check This Out): When Played: return 3 friendly resources to their
    **owners'** hands, then you may resource up to 3 cards from your hand. Fewer than 3 resources =
    return as many as you can; "up to 3" includes declining to 0; an opponent-owned resource
    (SHD_122 Arquitens) goes back to THEIR hand.

## Phase 4 — Passive conditions & the base-heal reaction (autonomous)

- [x] **Batch 4.1 — IC27_022, IC27_026** — done, 10 sections, suite 6145/0. ⚠ **IC27_022's closure had
  to go in GameLogic beside SHD_182, NOT in its per-card file**: `$playCostModifiers = []` is
  initialized AFTER `cards/_loader.php` runs, so a per-card registration is silently wiped (this is
  why LAW_179 / TS26_71 also live in the monolith with only a pointer comment in their card file).
  IC27_026 hooks `OnHealBase` and passes the ACTUALLY-healed amount, so a nearly-full base deals less
  than the printed Restore 3.
  - IC27_022 Moff Gideon (Cold Calling): passive self-cost reduction — costs [2 resources] less if a
    friendly unit **was defeated this phase**. No stub; sits at the cost-modifier chokepoint. Test
    both directions of the gate, that it reads FRIENDLY (an enemy defeat must not enable it), that
    it resets between phases, and that the discount can't drive cost below 0.
  - IC27_026 Darth Sidious (Move Against the Jedi): Restore 3 (auto-wired) + "When you heal damage
    from your base: deal **that much** damage to an enemy unit." Hooks `OnHealBase`. The amount is
    the damage ACTUALLY healed, so a base at full/near-full HP heals less than 3 and deals less —
    that clamp is the key test. Only YOUR base counts; a heal of 0 fires nothing; no enemy unit =
    fizzle. His own Restore is the natural trigger, so it self-combos with IC27_001's heal too.

## Phase 5 — Multi-trigger and multi-clause cards (autonomous)

- [x] **Batch 5.1 — IC27_024, IC27_168** — IC27_168: 7 sections, suite 6159/0 (three clauses chained
  through continuations; every fizzle branch falls through to the next clause, and only the DRAW is
  gated on the discard). IC27_024: IC27_024: 7 sections, suite 6152/0.
  ⚠⚠ **GENERATOR BUG FOUND AND FIXED**: the stub detector matched the TIGHT slash form
  (`"When Played/"`) but not the SPACED header this card uses (`"When Played / On Attack / When
  Defeated:"`), so only the LAST window was detected and the other two dispatched to nothing —
  a silent in-game no-op. `zzCardCodeGenerator.php` now matches `/When Played\s*\//i` and
  `/On Attack\s*\//i`; the gitignored local `GeneratedAbilityStubs.php` was hand-patched to match
  until the next regen. **Any other card with a spaced multi-trigger header had the same bug.**
  Also: the When-Defeated collection runs BEFORE cleanup, so the dying source is still in the arena
  and had to be explicitly excluded from its own "give a friendly unit a token" target list.
  - IC27_024 Grand Admiral Thrawn (Listen to Me Carefully): ONE ability on **three** triggers (When
    Played / On Attack / When Defeated): may give an Experience token to a friendly unit; it gains
    Sentinel for this phase. Each trigger is its own dispatch path and needs its own coverage — plus
    "may" declined, no friendly unit at all, and (on When Defeated) Thrawn no longer being a legal
    target himself. Sentinel must expire with the phase; Experience does not.
  - IC27_168 Cunning Ploy (Event): **three INDEPENDENT clauses** — look at an opponent's hand, you
    may discard a card from it (if you do, that player draws); exhaust an enemy unit; you may attack
    with a unit, which gets +3/+0 for that attack. ⚠ Per the LOF_223 Force Illusion family, a
    fizzling earlier clause must NOT skip later ones — empty opponent hand, no enemy unit, and no
    ready attacker each need a section proving the other two still resolve.

## Phase 6 — Out-of-play static + name-scoped waiver (autonomous)

- [x] **Batch 6.1 — IC27_078** — done, 6 sections, suite 6165/0. Waiver hooked at the
  `SWUAspectPenalty` chokepoint (SOR_008 Hera shape) reading a discard-only predicate
  `_SWUAnakinIC27078InDiscard`; matched by `CardTitle` so any Darth Vader printing qualifies. The
  search is `DoTopDeckSearch` over the full deck size.
  - IC27_078 Anakin Skywalker (Destined For Darkness): When Defeated: search your deck for a card
    **named** "Darth Vader", reveal it, and draw it. Plus a static that is live **while he is in your
    discard pile**: ignore the aspect penalties on cards you play named Darth Vader. Own batch —
    two independent seams on one card. Match by TITLE, subtitle excluded (Qi'ra SHD_202), so both
    IC27_067 *and* IC27_001 qualify. Critical negatives: the waiver is OFF while he is in play / in
    hand / in the deck and ON only from the discard pile; **deploying the IC27_001 leader is NOT
    "playing a card"** (settled — `SWUDeployLeader` deliberately does not route through
    `ActivateCard`), so the waiver must not apply to it; a no-match search returns the peeked cards
    rather than milling them (`dontSkipOnPass`, ASH_224 Elzar Mann).

## Phase 7 — Leaders (pair-programmed)

Both sides of each leader, plus the Epic deploy threshold. Left for last so Phases 1–6 run unattended.

- [x] **Batch 7.1 — IC27_008** — done, 6 sections, suite 6171/0. Both sides run the SAME clause
  (verbatim TWI_004 Yoda), differing only in who owns the After Action — threaded as a trailing
  `close` flag on the continuation param rather than duplicating the flow. Epic deploy needed no
  wiring (printed cost 6 IS the threshold).
  - IC27_008 Princess Leia (On a Diplomatic Mission). Front `Action [1 resource, Exhaust]`: draw a
    card, then put a card from your hand on the top **or** bottom of your deck — copy TWI_004 Yoda
    verbatim. `Epic Action`: deploy at **6 or more** resources. Deployed On Attack: the same draw +
    top-or-bottom, with no cost. Test the resource threshold at exactly 5 and 6, that the Epic is
    once-per-game, and that the top-vs-bottom choice actually lands in the chosen position.
- [x] **Batch 7.2 — IC27_001** — done, 8 sections, suite 6179/0. **USER RULING (2026-08-04): the two
  sides land on OPPOSITE sides of the cost-vs-effect line, exactly like SOR_006 Palpatine.** FRONT: the
  defeat is inside the brackets, so it is a cost REQUIREMENT — gated in `SWULeaderActionAffordable`
  beside SOR_006, no decline, leader does not exhaust without a sacrifice. DEPLOYED: "you may … If you
  do" is an EFFECT — never gated, freely declinable. Epic deploy generic (printed cost 7).
  - IC27_001 Darth Vader (No One to Stop Us). Front `Action [1 resource, Exhaust, defeat a friendly
    unit]`: draw a card and heal 2 damage from your base. `Epic Action`: deploy at **7 or more**
    resources. Deployed On Attack: "you **may** defeat another friendly unit. If you do, draw a card
    and heal 2 damage from your base."
  - ⚠ **The cost-vs-effect distinction is the whole card, and it decides an engine interaction.** On
    the FRONT side the defeat is a **cost**, so it is a availability requirement (no friendly unit ⇒
    the Action is unavailable — the session-92 sweep KEEPS cost-requirement gates) and, per the
    HMW_060 Rampart ruling, a cost-defeat passes `$skipReplacement = true`. On the DEPLOYED side it
    is an **effect** behind "you may … If you do", so the Action stays available, declining is legal,
    and the defeat IS replaceable. Getting these backwards is the likely failure and is why this
    batch is pair-programmed.
  - Also: "another friendly unit" excludes the deployed leader itself; the heal is capped by damage
    actually on your base (which chains into IC27_026).

## Status

**✅ ALL 15 CURRENTLY-PREVIEWED IC27 CARDS ARE IMPLEMENTED** (2026-08-04). Suite 6082 -> **6179/0** (+97 sections). Zero deferrals.
