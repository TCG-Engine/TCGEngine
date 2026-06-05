# SOR (Spark of Rebellion) — Card Implementation Reference

254 cards total: 18 Leaders, 12 Bases, 148 Units, 59 Events, 15 Upgrades, 2 Token Upgrades.

**Progress: ~229/252 carded entries done (implemented + auto-wired vanilla/pure-keyword). 23 remain — sequenced in [sor-complex-plan.md](sor-complex-plan.md).** Breakdown of the 23 (Phases A–C of the complex plan landed: SOR_097, SOR_158, SOR_156, SOR_075, SOR_055, SOR_168, SOR_150, SOR_071, SOR_072, SOR_118, SOR_160, SOR_102; regression 552 passing):
- **3 unbuilt leaders:** SOR_003 Chewbacca, SOR_008 Hera Syndulla, SOR_013 Cassian Andor.
- **~6 Simple/Medium-tier cards that were tier-classified but never batched:** SOR_143, SOR_146, SOR_174, SOR_182, SOR_196, SOR_219.
- **~14 genuinely-Complex:** SOR_040, SOR_041, SOR_042, SOR_043, SOR_058, SOR_089, SOR_107, SOR_153, SOR_155, SOR_187, SOR_203, SOR_212, SOR_223, SOR_233.

> ⚠ **The old "~46 remain: 13 Simple / 9 Medium / 23 Complex" line was stale and misleading.** The Simple plan (`sor-simple-plan.md`) and Medium plan (`sor-medium-plan.md`) are both *batch-complete*, but a band of cards that were tier-classified in this file never made it into either plan's batches. They have **generated trigger stubs** (so the generator thinks they have a WhenPlayed/OnAttack/etc.) but **no Custom handler was ever written** — the trigger fires and dispatches to nothing, so the ability silently no-ops in-game. Pure-keyword/vanilla cards (e.g. SOR_180 Shielded, SOR_195 Ambush) are NOT in the 35 — the dictionaries auto-wire them.

**Engine inventory before starting SOR work:**
- Keywords done: Ambush, Overwhelm, Raid N, Restore N, Sentinel, Shielded, Saboteur, Bounty, Grit
- Done: DoDrawCard, DoGiveShieldToken, DoGiveExperienceToken, OnExhaustCard, OnReadyCard
- Done: WhenPlayed / OnAttack / WhenDefeated trigger dispatch, MZCHOOSE targeting, MZMove to hand
- Done: Krennic passive (+1/+0 to damaged units), Sabine leader, SecurityComplex base, EnergyConversionLab base, Wedge Antilles unit

**Already implemented (skip):** SOR_001, SOR_014, SOR_019, SOR_022, SOR_100

---

## Tier Definitions

- **Simple** — uses only engine mechanics already in place. An agent can implement this by following existing card patterns with no new infrastructure.
- **Medium** — needs one new shared helper or a new mechanic not yet wired up, but the pattern is clear from adjacent code. Usually one new function + straightforward use.
- **Complex** — needs significant new infrastructure, multi-step decision chains, novel phase-state tracking, or unusual rules interactions with high bug risk.

---

## Tier Summary

### Already Done
SOR_001, SOR_002, SOR_005, SOR_006, SOR_010, SOR_014, SOR_016, SOR_017, SOR_019, SOR_022, SOR_031, SOR_084, SOR_087, SOR_100, SOR_122, SOR_125, SOR_137, SOR_148, SOR_162, SOR_169, SOR_170, SOR_189, SOR_193, SOR_199, SOR_215, SOR_222, SOR_224, SOR_226, SOR_236, SOR_244, SOR_251, SOR_054, SOR_076, SOR_216, SOR_124, SOR_106, SOR_116, SOR_032, SOR_165, SOR_044, SOR_063, SOR_066, SOR_098, SOR_229, SOR_250, SOR_064, SOR_207, SOR_205, SOR_239, SOR_213, SOR_232, SOR_164, SOR_141, SOR_157, SOR_117, SOR_194, SOR_057, SOR_070, SOR_166, SOR_101, SOR_248, SOR_081, SOR_113, SOR_161, SOR_230, SOR_242, SOR_082, SOR_079, SOR_211, SOR_159, SOR_144, SOR_130, SOR_034, SOR_080, SOR_172, SOR_077, SOR_078, SOR_173, SOR_127, SOR_025, SOR_111, SOR_073, SOR_154, SOR_028, SOR_074, SOR_140, SOR_220, SOR_234, SOR_252, SOR_033, SOR_038, SOR_090, SOR_132, SOR_134, SOR_176, SOR_121, SOR_151, SOR_037, SOR_049, SOR_108, SOR_231, SOR_241, SOR_094, SOR_093, SOR_177, SOR_110, SOR_050, SOR_053, SOR_059, SOR_060, SOR_068, SOR_039, SOR_086, SOR_099, SOR_178, SOR_202, SOR_208, SOR_209, SOR_214, SOR_221, SOR_206, SOR_218, SOR_227, SOR_240, SOR_083, SOR_126, SOR_136, SOR_147, SOR_163, SOR_171, SOR_096, SOR_123, SOR_104, SOR_007, SOR_012, SOR_135, SOR_092, SOR_052, SOR_119, SOR_192, SOR_152, SOR_238, SOR_246, SOR_228, SOR_200, SOR_201, SOR_185, SOR_062, SOR_051, SOR_190, SOR_191, SOR_245, SOR_139, SOR_181, SOR_056, SOR_235, SOR_061, SOR_138, SOR_167, SHD_072, SOR_004, SOR_018, SOR_011, SOR_009, SOR_012, SOR_103, SOR_036, SOR_105, SOR_015, SOR_085, SOR_149, SOR_133, SOR_088, SOR_198, SOR_183, SOR_197, SOR_047, SOR_204, SOR_188, SOR_091, SOR_175, SOR_115, SOR_179, SOR_186, SOR_184, SOR_129, SOR_142, SOR_045, SOR_097, SOR_158, SOR_156, SOR_075, SOR_055, SOR_168, SOR_150, SOR_071, SOR_072, SOR_118, SOR_160, SOR_102, SOR_219, SOR_003, SOR_013, SOR_182, SOR_143, SOR_196, SOR_146, SOR_040, SOR_041, SOR_233, SOR_187, SOR_145, SOR_174, SOR_043, SOR_042, SOR_212, SOR_223, SOR_153, SOR_089, SOR_058, SOR_107, SOR_155, SOR_203, SOR_008, SOR_109, SOR_048, SOR_112, SOR_114, SOR_131, SOR_217, SOR_249 (+ cross-set TWI_120, SHD_028)

> **SOR Simple plan COMPLETE (all 6 phases / 15 batches).** The unit activated-ability foundation (SWUUnitAction) + the discount/exhaust/bounce/shield-heal/draw-resource/deck-search/leader batches all landed; regression at 329 passing. Plan tracker: docs/sor-simple-plan.md. Also fixed a latent "for this attack" buff bug (SOR_220) and added infra: HASKEYWORD test assertion, OPTIONCHOOSE decision type, phase keyword-grant clear, DoTopDeckPlay count cap, unit-action UI Attack/Ability chooser.

### Simple (~107 cards)
Pure-keyword units, vanilla units, and single-effect cards using existing primitives.

**Vanilla (no text):**
SOR_046, SOR_095, SOR_120 (upgrade, no text), SOR_069 (upgrade, no text), SOR_128, SOR_210, SOR_225, SOR_237, SOR_247

**Pure keyword(s) already implemented:**
SOR_032 (Grit), SOR_044 (Restore 1), SOR_057 (Protector — gives Sentinel), SOR_063 (Sentinel), SOR_064 (Shielded), SOR_066 (Sentinel), SOR_070 (gives Restore 2), SOR_098 (Sentinel), SOR_101 (Ambush, WhenPlayed return ≤2-cost unit from discard — need bounce from discard but same infra as SWUPlayFromDiscard path), SOR_117 (Ambush + Overwhelm), SOR_141 (Raid 2), SOR_157 (Raid 2), SOR_164 (Overwhelm), SOR_165 (Grit), SOR_166 (Saboteur upgrade), SOR_194 (Saboteur + Raid 2), SOR_205 (Saboteur), SOR_207 (Shielded), SOR_213 (Ambush), SOR_229 (Sentinel), SOR_232 (Overwhelm), SOR_239 (Saboteur), SOR_248 (Raid 1 + cost reduction if Trooper controlled), SOR_250 (Sentinel)

**Single-effect events/units using existing primitives:**
SOR_025 (base epic — deal 3 to damaged unit), SOR_028 (base epic — give -4/-0), SOR_073 (give Shield token), SOR_074 (heal 3 from unit or base), SOR_077 (defeat unit with ≤5 HP), SOR_078 (defeat non-leader unit), SOR_106 (give 3 units +3/+3 +2/+2 +1/+1), SOR_111 (WhenPlayed draw), SOR_124 (give +2/+2), SOR_127 (unit deals damage equal to power to enemy unit), SOR_140 (WhenPlayed: unit loses Sentinel this phase), SOR_154 (each friendly unit gains Raid 2 this phase), SOR_172 (deal 4 damage), SOR_173 (deal 3 to each unit in chosen arena), SOR_216 (give enemy -4/-0), SOR_220 (attack +3/+0), SOR_234 (two Imperial units deal power damage to same target), SOR_244 (Ambush + OnAttack exhaust enemy Vehicle ground unit), SOR_251 (defeat an upgrade), SOR_252 (put cards from discard to bottom of deck)

**Conditional passive buffs (already have the pattern from Krennic/Wedge):**
SOR_034 (Restore 1 + events opponents play cost 1 more), SOR_081 (while ≥6 resources, +2/+0), SOR_113 (while ≥6 resources, gains Sentinel), SOR_130 (while attacking damaged unit +2/+0 + Overwhelm), SOR_144 (Raid 1 + each other Heroism unit gains Raid 1), SOR_159 (while another Aggression unit, +Raid 2), SOR_161 (while you have initiative +2/+0), SOR_211 (while another Cunning unit, gains Sentinel), SOR_230 (other friendly Imperial units +1/+1), SOR_242 (other friendly Rebel units +1/+1)

**Simple WhenPlayed/OnAttack/WhenDefeated already patterned:**
SOR_033 (WhenPlayed: deal 2 to a friendly ground unit and 2 to an enemy ground unit — two MZCHOOSE + DealDamage; was C7), SOR_037 (WhenPlayed: give Experience to each damaged friendly unit — DoGiveExperienceToken), SOR_038 (Shielded + WhenPlayed: defeat unit with ≤4 remaining HP — ZoneSearch filter by remaining HP + SWUDefeatUnit; was C7), SOR_039 (WhenPlayed: exhaust all ground units — loop + OnExhaustCard, no targeting), SOR_044 (Restore 1), SOR_049 (Sentinel + WhenDefeated: give 2 Experience to another friendly unit, if Force draw — DoGiveExperienceToken×2 + HasTrait Force check + DoDrawCard), SOR_050 (Shielded + WhenPlayed/OnAttack: give Shield to another Spectre unit — YESNO + ZoneSearch Spectre trait + DoGiveShieldToken; was C7→Medium), SOR_053 (upgrade +2/+1 non-Vehicle, WhenPlayedAsUpgrade: if attached is Luke Skywalker, HealUnit + DoGiveShieldToken — CardID check pattern from Traitorous; was C7→Medium), SOR_059 (OnAttack: heal 2 from another unit), SOR_060 (WhenDefeated: give Shield to Vigilance unit), SOR_068 (Shielded + WhenPlayed: if you control another Vigilance unit, heal 4 from base — ZoneSearch aspect filter + HealBase), SOR_083 (WhenDefeated: put self into play as resource — novel but small), SOR_086 (WhenPlayed: give a unit Sentinel for this phase — AddTurnEffect("SENTINEL"); was C7), SOR_090 (Sentinel + Overwhelm + WhenPlayed: deal damage = resource count — ZoneSearch count + MZCHOOSE + DealDamage; was C7), SOR_094 (Action[exhaust]: give Experience to another friendly unit), SOR_096 (WhenPlayed: search top 5 for a Rebel card — DoTopDeckSearch with Rebel filter, pick 1; was M8), SOR_099 (Sentinel + WhenPlayed: may return friendly non-leader ground unit to hand, draw a card — BOUNCE_UNIT + DoDrawCard; was M3), SOR_104 (Event: search top 10 for up to 3 units combined cost ≤7, play free — DoTopDeckPlay; was C7), SOR_108 (WhenDefeated: give Experience to a unit — MZCHOOSE + DoGiveExperienceToken; was M6), SOR_123 (Event: search top 5 for a unit — DoTopDeckSearch unit filter, pick 1; was M8), SOR_126 (put event into play as resource — like Resupply), SOR_132 (WhenPlayed: deal 3 to a space unit — MZCHOOSE + SWUDealDamageToUnit; was C7), SOR_121 (upgrade OnAttack: if not attacking a base, deal 2 to a unit in defender's arena — reads SWU_CURRENT_DEFENDER + DEAL_UNIT_DAMAGE; was C7), SOR_134 (WhenPlayed/WhenDefeated: deal 2 to enemy base + 2 to enemy unit — SWUDealDamageToBase + MZCHOOSE + SWUDealDamageToUnit; was C7), SOR_136 (upgrade +3/+1 non-Vehicle, WhenPlayedAsUpgrade: if Darth Vader deal 4 to ground unit — upgrade system + conditional CardID check; was C7), SOR_147 (WhenPlayed/WhenDefeated: discard entire hand, draw 3 — YESNO + loop SWUAddToDiscard all hand cards + DoDrawCard(3); no new infrastructure; was C7→Medium), SOR_151 (Event: deal damage = friendly unit's damage + 1 to enemy unit — two MZCHOOSE + DealDamage; was C7), SOR_162 (WhenPlayed: defeat an upgrade), SOR_163 (WhenDefeated: if you have initiative, draw 2 — has_initiative check + DoDrawCard; was M5), SOR_171 (Event: choose a player, they draw 2 — DoDrawCard; was M5), SOR_176 (WhenPlayed: reveal event from hand → deal 1 damage), SOR_178 (WhenPlayed: if another Cunning unit, MZCHOOSE enemy ≤4-cost + EXHAUST_UNIT), SOR_202 (WhenPlayed: return non-leader unit — BOUNCE_UNIT; was M3), SOR_206 (OnAttack: pay 2 resources, draw a card — YESNO + SWUExhaustResources(2) + DoDrawCard(1)), SOR_208 (OnAttack: if leader unit, MZCHOOSE non-leader + EXHAUST_UNIT), SOR_209 (Raid 1 + WhenPlayed: return friendly non-leader unit — BOUNCE_UNIT; was M3), SOR_214 (OnAttack: READY_RESOURCE — upgrade that grants this to attached Vehicle), SOR_218 (Event: exhaust enemy unit + give Shield to friendly ≤3-cost — EXHAUST_UNIT + DoGiveShieldToken; was M1), SOR_221 (choose arena, exhaust all units in it — arena YESNO + EXHAUST_UNIT loop), SOR_226 (WhenDefeated: ready a Villainy unit), SOR_227 (WhenPlayed: attack with unit; Imperial gets +2/+0), SOR_231 (WhenPlayed: give 2 Experience to another Imperial unit), SOR_240 (WhenPlayed: attack with unit; Rebel gets +2/+0), SOR_241 (WhenPlayed: give 2 Experience to another Rebel unit)

**Conditional passive buffs — newly added (same pattern as Krennic/Wedge):**
SOR_079 (each friendly non-leader unit that costs 6+ gains Ambush — like Wedge passive; was M6), SOR_080 (WhenPlayed: give Exp to each of up to 3 Trooper units — DoGiveExperienceToken + ZoneSearch Troopers; was M6), SOR_082 (while Official unit: Sentinel; while Palpatine in play: +0/+1 — dual conditional passive; was M6)

**Leaders now Simple (leader side + deployed OnAttack; infra proven session 30):**
SOR_007 leader side (Action[1R, exhaust]: give Experience to Imperial — DoGiveExperienceToken + MZCHOOSE Imperial trait filter), SOR_007 deployed OnAttack (give Experience to another Imperial — same), SOR_010 deployed OnAttack (deal 2 to a unit — MZCHOOSE + DEAL_UNIT_DAMAGE), SOR_012 IG-88 deployed (each other friendly gains Raid 1 — same passive pattern as SOR_144 Red Three)

---

### Medium (~83 cards)

These need one or more new primitives. Group them by the new mechanic they introduce. Implement each mechanic once, then all cards in that group become simple.

#### M0: Phase-counter GlobalEffect pattern — ✅ established session 30
`SWU_ENEMY_DEFEATED`, `SWU_PLAYED_VILLAINY`, `SWU_PLAYED_UNIT_{uid}`, `SWU_ATTACKED_MANDALORIAN_{uid}` all implemented. Pattern: set in the relevant game function, check in ability handler, clear at RegroupPhaseStart.

New counters needed (same 2-line pattern each):
- `SWU_FRIENDLY_DEFEATED` — unblocks SOR_051 Luke unit (WhenPlayed conditional debuff) → **Medium**
- `SWU_CARDS_PLAYED_COUNT` — unblocks SOR_191 Vanguard Ace, SOR_190 Lothal Insurgent (cards played this phase count)
- `SWU_ATTACKED_THIS_PHASE` set — unblocks SOR_245 Medal Ceremony (Rebel units that attacked this phase)

Also newly Medium from C4: SOR_011 Grand Inquisitor deployed OnAttack (deal 1 to ≤3-power friendly + ready it — SWUDealDamageToUnit + power filter + READY_UNIT, all primitives exist)

#### M1: Exhaust a unit (targeted effect) — ✅ EXHAUST_UNIT custom DQ handler now exists
`OnExhaustCard` exists. `EXHAUST_UNIT` custom DQ handler added (GameLogic.php) — callers queue PASSPARAMETER/MZCHOOSE + CUSTOM EXHAUST_UNIT. SOR_039/SOR_178/SOR_221 moved to Simple.

Cards remaining: SOR_129 (play Imperial, enters ready, each opponent readies a unit — moved to Medium; ActivateAbility + enter-ready flag + opponent READY_UNIT), SOR_186 (exhaust unit; it can't ready this round — needs "can't ready" phase tracking, still Medium). SOR_218 moved to Simple (both EXHAUST_UNIT and DoGiveShieldToken exist).

#### M2: Ready a unit (targeted effect) — ✅ READY_UNIT custom DQ handler now exists
`OnReadyCard` exists. `READY_UNIT` custom DQ handler added (GameLogic.php) — callers queue PASSPARAMETER/MZCHOOSE + CUSTOM READY_UNIT. SOR_169 is Done.

Remaining blockers are NOT the ready-unit primitive: SOR_011 deployed (deal-damage-to-friendly step + ready combo), SOR_110 (defeat-self as cost + attack-exhausted-unit), SOR_149 (needs "WhenDefeats" trigger type — when THIS unit kills another), SOR_184 (WhenPlayed self-ready ✅ Simple via SOR_148 pattern; but Action[2 resources]: exhaust non-unique needs resource-cost unit-action), SOR_196 (needs "WhenAttacked" trigger type)

**Newly Medium from C7 (BeginSWUAttack established):** SOR_055 The Force Is With Me (MZCHOOSE friendly unit → DoGiveExperienceToken×2 → HasTrait Force check → DoGiveShieldToken → YESNO → BeginSWUAttack with chosen unit — all primitives exist; multi-step chain with cross-handler mzID encoding; was C7 unmarked)

#### M3: Return unit to owner's hand — ✅ BOUNCE_UNIT custom DQ handler now exists
`MZMove($player, mzID, "myHand")` exists. `BOUNCE_UNIT` custom DQ handler added (GameLogic.php) — same queue pattern.

Done: SOR_199, SOR_222, SOR_224. Moved to Simple: SOR_099, SOR_202, SOR_209 (all use BOUNCE_UNIT directly). Remaining Medium: SOR_183 (bounce event from discard — discard zone search + MZMove to hand, not arena unit), SOR_197 (return up to 2 friendly resources to hand — different zone, not BOUNCE_UNIT path).

#### M4: Look at opponent's hand (spy)
Display/log only — no game effect beyond viewing. Low mechanical complexity, mostly UI.

Cards: SOR_016 (look at top of each deck), SOR_185 (OnAttack: name card, opponent reveals + discards named), SOR_200 (look at hand + discard from it), SOR_201 (WhenPlayed: look at opponent's hand, discard non-unit), SOR_228 (WhenPlayed: look at opponent's hand)

#### M5: Draw a card (general) — `DoDrawCard` already exists
Many cards use this. It's already in the engine as `DoDrawCard($player, 1)`. Cards that draw are therefore Simple if that's their only new mechanic. Listing here because test infrastructure may need the DSL assertion `P1DECKCOUNT` (already done).

Moved to Simple: SOR_163 (WhenDefeated if initiative: draw 2), SOR_171 (choose player draw 2). Already Simple: SOR_067, SOR_111. Remaining Medium (needs new trigger patterns or complex choice): SOR_045 (WhenDefeated: choose any number of players to draw — multi-player choice), SOR_103 (attack with 2 Rebel units — needs two sequential BeginSWUAttack calls), SOR_105 (each other friendly gains "WhenDefeated: draw" — reactive on-enemy-defeat passive grant), SOR_115 (draw when unique defeated, once per round — per-round tracking), SOR_119 (Scry 1 with draw-or-discard+heal choice), SOR_145 (WhenDefeated: per-opponent choice deal 3 to base OR discard).

#### M6: Experience token abilities already in engine (DoGiveExperienceToken done)
But need the MZCHOOSE pattern wired for "give Experience to a unit" targeting + `$whenPlayedAbilities` / `OnAttack` triggers for the many Experience-granters.

Moved to Simple: SOR_079 (passive Ambush grant to ≥6-cost), SOR_080 (give Exp to up to 3 Troopers), SOR_082 (conditional passive Sentinel/+0/+1), SOR_108 (WhenDefeated give Exp). Already Simple: SOR_037, SOR_094. Remaining Medium (needs new trigger or complex pattern): SOR_007 leader (leader side OnAttack trigger), SOR_035 (reveal from hand mechanic), SOR_036 (reactive trigger on enemy unit defeated), SOR_085 Rukh (WhenDealsComCombatDamage trigger type not yet implemented), SOR_105 (each other friendly gains reactive WhenDefeated — passive grant).

#### M7: Deck look/scry (look at top N, put some on bottom/top)
Needs `DoScryN` infrastructure: reveal top N cards, present choices, move back.

Cards: SOR_016 (look at top of each deck), SOR_031 (WhenPlayed/WhenDefeated: look at top 2, reorder), SOR_119 (WhenPlayed/OnAttack: peek top, draw or discard), SOR_192 (WhenCompleteAttack: look at top, play/discard/leave), SOR_236 (WhenPlayed/OnAttack: look at top, put on bottom or leave)

#### M8: Deck search (search top N for matching card, reveal + draw, put rest on bottom)
Needs `DoSearchDeckTop($player, $N, $filter, $count)` infrastructure: peek top N, offer matching cards, draw chosen, put rest on bottom.

Done: SOR_084, SOR_087, SOR_125. Moved to Simple: SOR_096 (search top 5 Rebel), SOR_104 (search top 10 units ≤7 cost play free — DoTopDeckPlay), SOR_123 (search top 5 any unit). Remaining Medium: SOR_042 (search ENTIRE deck — full deck search, not top N), SOR_181 (Jabba: search top 8 Trick + each Trick costs 1 less — cost-reduction side is M9).

#### M9: Conditional cost reduction (pay less if condition met) — ✅ foundation established (SOR_248)
**Foundation built session-34:** `SWUComputePlayCost` in `GameLogic.php` is the single source of truth for play cost (base + aspect penalty + modifiers, floored at 0), called by both `CanAffordActivationReserve` (UI affordability/glow) and `ActivateCard` (payment) so they can't drift. Per-card reducers register a closure in the **`$playCostModifiers`** registry: `$playCostModifiers["CARD_ID"] = fn($player, $subjectObj): int` (negative = cheaper). SHD_182 Bravado was migrated off its inline hack into this registry; SOR_248 added. **Adding a "costs N less" card is now just one closure.** (Known gap: the play-from-discard TPP/OTPP cost sites still compute cost inline — revisit if a cost-reduced card must be playable from discard.)

Done: SOR_248 (costs 1 less if you control Trooper). Now Simple via the registry: SOR_139 (if you control Force unit, costs 1 less — same shape as SOR_248, swap the trait check for an aspect check). Still Medium (need a phase/round counter or extra mechanic alongside the reducer): SOR_056 (OnAttack: next non-Heroism/Villainy card costs 2 less this phase — needs "next card this phase" tracking), SOR_061 (first upgrade on this unit each round costs 1 less — per-round counter), SOR_167 (discard from hand, deal damage equal to cost), SOR_235 (play non-Heroism unit free, deal cost to own base).

#### M10: "Name a card" effect
Needs text input or choice from named list. Unusual mechanic.

Cards: SOR_062 (WhenPlayed: name a card, opponents can't play it while in play), SOR_185 (OnAttack: name card, opponent reveals + discards)

#### M11: Discard from opponent's deck
Cards: SOR_047 (OnAttack: discard 1 per Spectre, heal for different aspects), SOR_058 (Vigilance event: discard 6 from opponent's deck), SOR_204 (WhenDefeated: discard from own deck; if non-unit, deal 2)

#### M12: Damage redistribution / split effects
Cards: SOR_051 (WhenPlayed: give enemy -3/-3; if friendly defeated this phase, give -6/-6 instead), SOR_052 (WhenPlayed: heal up to 8 from any units/bases, deal that much to self), SOR_092 (give friendly +2/+2, it deals power damage split among any units), SOR_097 (WhenPlayed: deal damage equal to unit count in target arena), SOR_131 (Raid 1 per damage on self + OnAttack: deal 1 to self and 1 to other ground), SOR_135 (WhenPlayed: deal 6 divided among enemy units)

---

### Complex (~55 cards)

These need major new systems, multi-step decisions, unusual phase-state tracking, or rules interactions with significant bug risk.

#### C1: Take control of unit — ✅ ALL DONE (`SWUTakeControlOfUnit` implemented)
`SWUTakeControlOfUnit($newController, $mzID)` in GameLogic.php. `TEMPORARY_STEAL` TurnEffect for end-of-turn returns.

Cards: SOR_006 ✅, SOR_122 ✅, SOR_224 ✅

#### C2: Phase-state tracking ("if you've done X this phase/round")
Needs new phase-scoped counters: cards played this phase, base damage dealt this phase, times drawn this round, units attacked this phase.

Cards: SOR_013 leader (draw if dealt ≥3 base damage this phase), SOR_013 deployed (draw when deal base damage, once per round), SOR_115 Agent Kallus (draw when unique defeated, once per round), SOR_143 (OnPlay another Aggression card: deal 1 to base), SOR_148 (WhenPlayed: if a base has ≥15 damage, ready self), SOR_150 (draw + attack with "when deals damage: defeat it" temp condition), SOR_152 (reveal top 4, 1 damage per Heroism card, choose keep/discard), SOR_160 Wolffe (WhenPlayed/OnAttack: bases can't be healed this phase), SOR_175 (draw 2; opponents who took base damage this phase discard 2), SOR_191 (return all units defeated this phase from discard to hand), SOR_245 (give Experience to up to 3 Rebel units that attacked this phase)

#### C3: Leader side abilities (non-deployed) — varies by leader
Leader side abilities fire as an Action that exhausts the leader. The exhausted leader + cost check infra exists for Krennic and Sabine. Each new leader is medium to complex depending on its effect.

- **Now Simple** (leader side + deployed OnAttack both fully patterned): SOR_007 (DoGiveExperienceToken + MZCHOOSE Imperial filter; proven by SOR_080 + Bo-Katan deployed OnAttack), SOR_010 deployed OnAttack (MZCHOOSE + DEAL_UNIT_DAMAGE; Bo-Katan proved deployed OnAttack), SOR_012 deployed text (each other friendly gains Raid 1 — same pattern as SOR_144 Red Three in Simple tier)
- **Medium leaders** (ability is simple once leader exhaustion works): SOR_002 ✅, SOR_004 (give +0/+2 to unit — needs stat-buff TurnEffect + ObjectCurrentHP extension), SOR_009 (attack with Rebel, maybe another Rebel), SOR_011 (deal 2 to own ≤3-power unit, ready it — SWUDealDamageToUnit + power filter + READY_UNIT chain, all primitives exist), SOR_012 leader side (attack + if more units +1/+0 — BeginSWUAttack + conditional TurnEffect buff), SOR_018 (attack, defender -1/-0)
- **Complex leaders** (effect needs new infrastructure): SOR_003 (play ≤3-cost unit from hand with Sentinel — needs cost-gated play from hand), SOR_005 (give Shield to Heroism unit played this phase — needs "played this phase" tracking), SOR_006 (pay resource + defeat friendly: deal 1 + draw), SOR_008 Hera (ignore Spectre aspect penalty — needs aspect-check hook), SOR_010 (if you played Villainy card this phase: deal 1 to unit + 1 to base), SOR_013 (if dealt ≥3 damage to base this phase: draw), SOR_015 Boba (when enemy leaves play: ready resource — reactive trigger), SOR_016 Thrawn (look at top of decks, exhaust unit based on cost), SOR_017 Han Solo (play card from hand as resource, ready it; then next APS defeat a resource), SOR_019 Security Complex (already done)

#### C4: "Deployed" leaders with complex WhenDeployed / ongoing effects
Most deployed leader abilities use mechanics from C3 or M-tier. But the "WhenDeployed" fire path needs to exist first (Krennic's `Restore 2` is already implemented as deploy text).

**Now Simple** (deployed OnAttack proven by Bo-Katan): SOR_007 deployed (OnAttack: give Experience to another Imperial unit — MZCHOOSE Imperial + DoGiveExperienceToken), SOR_010 deployed (OnAttack: deal 2 to a unit — MZCHOOSE + DEAL_UNIT_DAMAGE), SOR_012 deployed (each other friendly gains Raid 1 — same passive pattern as SOR_144)

**Now Medium** (all primitives exist, just needs chain): SOR_011 deployed (OnAttack: deal 1 to another friendly ≤3-power unit and ready it — SWUDealDamageToUnit + power filter + READY_UNIT)

Complex deploy abilities: SOR_004 deployed (during action phase: not defeated by 0 HP; in regroup phase, defeat if 0 HP), SOR_006 deployed ✅, SOR_008 deployed (ignore Spectre aspect penalty — C3), SOR_015 deployed (when completes attack: if enemy left play this phase, ready up to 2 resources), SOR_016 deployed (APS: look at top cards; OnAttack: reveal + exhaust by cost), SOR_017 deployed (OnAttack: put top deck card as resource, defeat at next APS)

#### C5: "Each opponent" + "divided as you choose" effects
Needs iteration over opponents (trivial in 2P, matters for Twin Suns) and split-damage target selection.

Cards: SOR_040 Avenger (opponent chooses a unit they control, defeat it), SOR_041 (opponent chooses unit they control, defeat it), SOR_043 Superlaser Blast (defeat all units), SOR_058 Vigilance event (choose 2 of 4 options), SOR_107 Command event (choose 2 of 4 options), SOR_135 Palpatine unit (deal 6 divided among enemy units), SOR_145 K-2SO (for each opponent: deal 3 to base OR they discard), SOR_155 Aggression event (choose 2 of 4 options), SOR_203 Cunning event (choose 2 of 4 options)

#### C6: "Choose 2" modal events
Multi-option selection from a list. Needs a new "choose N options from list" DQ pattern. 4 events use the exact same structure.

Cards: SOR_058 Vigilance, SOR_107 Command, SOR_155 Aggression, SOR_203 Cunning

#### C7: Complicated conditional/interaction chains
*(→ Simple/Medium = lowered tier; ✅ = Done)*

- SOR_033 → **Simple** (two MZCHOOSE + DealDamage, both primitives exist)
- SOR_038 → **Simple** (Shielded keyword + ZoneSearch filter remaining HP + SWUDefeatUnit)
- SOR_045 Yoda (WhenDefeated: choose any number of players, they each draw — multi-player choice)
- SOR_047 Kanan Jarrus (OnAttack: discard 1 per Spectre from deck; heal 1 per different aspect — deck-mill + aspect counting)
- SOR_050 → **Simple** (DoGiveShieldToken + ZoneSearch Spectre trait exist; YESNO + MZCHOOSE pattern; was Medium)
- SOR_051 Luke unit → **Medium** (WhenPlayed: -3/-3 or -6/-6 if friendly defeated this phase — needs one new `SWU_FRIENDLY_DEFEATED` GlobalEffect set in `SWUDefeatUnit` when `$obj->Owner == $activePlayer`; same pattern as existing `SWU_ENEMY_DEFEATED`)
- SOR_052 Redemption (heal up to 8 total from any units/bases, deal to self — multi-target heal)
- SOR_053 → **Simple** (WhenPlayedAsUpgrade + host CardID == Luke check + HealUnit(all) + DoGiveShieldToken — established pattern from Traitorous/SOR_215; was Medium)
- SOR_054 → **Medium** (upgrade OnAttack: "give defender -2/-2" requires knowing the combat target mzID — `OnAttackFromUpgradeTrigger` receives only `$unitMzID`, not `$targetMzID`. Fix: add `SWU_CURRENT_COMBAT_TARGET` to `ExecuteSWUAttack` before flushing triggers → drops to Simple)
- SOR_055 → **Medium** (give 2 Exp + if Force: Shield, then attack — BeginSWUAttack established via SOR_215; all primitives exist; multi-step chain with cross-handler mzID encoding)
- SOR_085 Rukh (WhenDeals combat damage to non-leader while attacking: defeat that unit — WhenDealsCombatDamage trigger not yet implemented)
- SOR_086 → **Simple** (AddTurnEffect("SENTINEL") on chosen unit; pattern established)
- SOR_087 ✅ Done
- SOR_088 (when attacks and defeats: deal excess damage to another ground unit — post-combat excess damage routing)
- SOR_089 Relentless (first event per opponent per round loses all abilities — passive event-blanking + per-round counter)
- SOR_090 → **Simple** (count ZoneSearch resources, MZCHOOSE target, DealDamage)
- SOR_091 Emperor's Legion (return all units defeated this phase — phase-state defeated-set tracking needed)
- SOR_092 (give +2/+2, unit deals power divided among any units — MZSplitAssign pattern)
- SOR_102 Home One (Restore 2 + each other gets Restore 1 + WhenPlayed: play Heroism from discard at -3 cost)
- SOR_104 → **Simple** (DoTopDeckPlay done; same pattern as SOR_087)
- SOR_110 Frontline Shuttle (Action[defeat this]: attack with exhausted unit, can't attack bases — exhaust-override attack path)
- SOR_121 → **Simple** (upgrade OnAttack: "if not attacking a base, deal 2 to unit in defender's arena" — the combat target mzID is now exposed as `SWU_CURRENT_DEFENDER` by `ExecuteSWUAttack` (session 33); read it to distinguish base vs unit target, then `DEAL_UNIT_DAMAGE`. Same primitives as SOR_054, now Done)
- SOR_122 ✅ Done
- SOR_129 → **Medium** (ActivateAbility + enter-play-ready flag + opponent READY_UNIT)
- SOR_131 → **Medium** (Raid-per-damage: ObjectCurrentPower extension; OnAttack: SWUDealDamageToUnit self + MZCHOOSE ground)
- SOR_133 Seventh Sister (Saboteur + when deals base damage: deal 3 to ground — WhenDealsCombatDamageToBase trigger not yet implemented)
- SOR_136 → **Simple** (upgrade WhenPlayedAsUpgrade: if host CardID == Darth Vader, MZCHOOSE + SWUDealDamageToUnit 4 — established pattern from Traitorous)
- SOR_137 ✅ Done
- SOR_138 Force Lightning (unit loses all abilities + if Force: pay X deal 2X — HasNoAbilities suppression + variable cost payment)
- SOR_142 Sabine unit (while ≥3 aspects among friendlies: can't be attacked unless Sentinel + OnAttack: 1 to defender or base)
- SOR_147 → **Simple** (discard entire hand: loop SWUAddToDiscard all hand cards + DoDrawCard(3) — no new infrastructure; was Medium)
- SOR_149 Mace Windu (Ambush + WhenDefeats: ready himself — WhenDefeats trigger type not yet implemented)
- SOR_150 Heroic Sacrifice (draw + attack + +2/+0 + temp "when deals combat damage: defeat it")
- SOR_151 → **Simple** (two MZCHOOSE: friendly unit + enemy unit; DealDamage = friendly.Damage + 1)
- SOR_153 Saw Gerrera (as additional cost for opponents to play event: deal 2 to own base — passive event-cost surcharge hook)
- SOR_177 Bib Fortuna (Action[exhaust]: play event for 1 less — ActivateAbility + event cost reduction hook)
- SOR_179 Boba Fett unit (OnAttack: if attacking exhausted unit that didn't enter play this round, deal 3 — per-round enter-play tracking)
- SOR_183 → **Medium** (BOUNCE_UNIT bounces arena units; this bounces an event from any discard to hand — discard ZoneSearch + MZMove)
- SOR_184 → **Medium** (READY_UNIT done; WhenPlayed self-ready if Boba/Jango simple; Action[2]: EXHAUST_UNIT + ActivateAbility pattern)
- SOR_185 Chimaera (OnAttack: name card, opponent reveals hand + discards named — name-a-card mechanic)
- SOR_186 → **Medium** (EXHAUST_UNIT done; still needs "can't ready this round" flag)
- SOR_187 I Had No Choice (choose up to 2 units; opponent chooses 1 to return, other to bottom — opponent targeting choice)
- SOR_188 Chopper (while Spectre: Raid 1; OnAttack: discard from deck; if event, exhaust resource — deck-mill + conditional)
- SOR_190 Lothal Insurgent (WhenPlayed: if played another card this phase — phase-state tracking)
- SOR_191 Emperor's Legion (return all units defeated this phase — phase-state defeated-set tracking)
- SOR_192 Ezra Bridger (WhenCompleteAttack: look at top, play/discard/leave — OnAttackEnd trigger + Scry-1 with play option)
- SOR_193 Millennium Falcon (enters ready; at regroup readying: pay 1 or return — regroup-phase trigger hook)
- SOR_198 Han Solo unit (Ambush + deals combat damage before defender — first-strike combat modifier)
- SOR_223 Don't Get Cocky (reveal up to 7 from deck until stop or 7 — iterative reveal + player-stops mechanic)
- SOR_233 I Am Your Father (deal 7 unless opponent says "no"; if no, draw 3 — opponent YESNO during your action)
- SOR_235 Galactic Ambition (play unit free, deal its cost to own base — free-play with self-damage = cost)
- SOR_238 C-3PO (WhenPlayed/OnAttack: choose number, look at top; if matches reveal + draw — NUMBER_CHOOSE + top-card peek)
- SOR_246 You're My Only Hope (look at top, play for 5 less / free if base ≤5 HP — Scry-1 + conditional cost reduction)

---

## Full Card Data

### SOR_001 — Director Krennic *(Unique)* ✅ DONE
**Type:** Leader  **Arena:** Ground  **Traits:** Imperial,Official  **Aspects:** Vigilance,Villainy

Each friendly damaged unit gets +1/+0.
Epic Action: If you control 5 or more resources, deploy this leader.

*Deploy text:* Restore 2

### SOR_002 — Iden Versio *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Imperial,Trooper  **Aspects:** Vigilance,Villainy

Action [Exhaust]: If an enemy unit was defeated this phase, heal 1 damage from your base.
Epic Action: If you control 6 or more resources, deploy this leader.

*Deploy text:* Shielded. When an enemy unit is defeated: Heal 1 damage from your base.

### SOR_003 — Chewbacca *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Underworld,Wookiee  **Aspects:** Vigilance,Heroism

Action [exhaust]: Play a unit that costs 3 or less from your hand (paying its cost). It gains Sentinel for this phase.
Epic Action: If you control 7 or more resources, deploy this leader.

*Deploy text:* Sentinel. Grit.

### SOR_004 — Chirrut Îmwe *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Force,Rebel  **Aspects:** Vigilance,Heroism

Action [Exhaust]: Give a unit +0/+2 for this phase.
Epic Action: If you control 5 or more resources, deploy this leader.

*Deploy text:* During the action phase, this unit isn't defeated by having no remaining HP. (During the regroup phase, if he has no remaining HP, defeat him.)

### SOR_005 — Luke Skywalker *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Force,Rebel  **Aspects:** Vigilance,Heroism

Action [1 resource, exhaust]: Give a Shield token to a [Heroism] unit you played this phase.
Epic Action: If you control 6 or more resources, deploy this leader.

*Deploy text:* On Attack: You may give another unit a Shield token.

### SOR_006 — Emperor Palpatine *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Force,Imperial,Sith,Official  **Aspects:** Command,Villainy

Action [1 resource, exhaust, defeat a friendly unit]: Deal 1 damage to a unit and draw a card.
Epic Action: If you control 8 or more resources, deploy this leader.

*Deploy text:* When Deployed: Take control of a damaged non-leader unit. On Attack: You may defeat another friendly unit. If you do, deal 1 damage to a unit and draw a card.

### SOR_007 — Grand Moff Tarkin *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Imperial,Official  **Aspects:** Command,Villainy

Action [1 resource, exhaust]: Give an Experience token to an Imperial unit.
Epic Action: If you control 5 or more resources, deploy this leader.

*Deploy text:* On Attack: You may give an Experience token to another Imperial unit.

### SOR_008 — Hera Syndulla *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Rebel,Twi'lek,Spectre  **Aspects:** Command,Heroism

Ignore the aspect penalty on SPECTRE cards you play.
Epic Action: If you control 6 or more resources, deploy this leader.

*Deploy text:* Ignore the aspect penalty on SPECTRE cards you play. On Attack: You may give an Experience token to another unique unit.

### SOR_009 — Leia Organa *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Rebel,Official  **Aspects:** Command,Heroism

Action [exhaust]: Attack with a Rebel unit. Then, you may attack with another Rebel unit.
Epic Action: If you control 5 or more resources, deploy this leader.

*Deploy text:* Raid 1. When this unit completes an attack: You may attack with another Rebel unit.

### SOR_010 — Darth Vader *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Force,Imperial,Sith  **Aspects:** Aggression,Villainy

Action [1 resource, exhaust]: If you played a [Villainy] card this phase, deal 1 damage to a unit and 1 damage to a base.
Epic Action: If you control 7 or more resources, deploy this leader.

*Deploy text:* On Attack: You may deal 2 damage to a unit.

### SOR_011 — Grand Inquisitor *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Force,Imperial,Inquisitor  **Aspects:** Aggression,Villainy

Action [exhaust]: Deal 2 damage to a friendly unit with 3 or less power and ready it.
Epic Action: If you control 6 or more resources, deploy this leader.

*Deploy text:* On Attack: You may deal 1 damage to another friendly unit with 3 or less power and ready it.

### SOR_012 — IG-88 *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Underworld,Droid,Bounty Hunter  **Aspects:** Aggression,Villainy

Action [Exhaust]: Attack with a unit. If you control more units than the defending player, the attacker gets +1/+0 for this attack.
Epic Action: If you control 5 or more resources, deploy this leader.

*Deploy text:* Each other friendly unit gains Raid 1.

### SOR_013 — Cassian Andor *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Rebel  **Aspects:** Aggression,Heroism

Action [1 resource, Exhaust]: If you've dealt 3 or more damage to an enemy base this phase, draw a card.
Epic Action: If you control 6 or more resources, deploy this leader.

*Deploy text:* Saboteur. When you deal damage to an enemy base: You may draw a card. Use this ability only once each round.

### SOR_014 — Sabine Wren *(Unique)* ✅ DONE
**Type:** Leader  **Arena:** Ground  **Traits:** Mandalorian,Rebel,Spectre  **Aspects:** Aggression,Heroism

Action [exhaust]: Deal 1 damage to each base.
Epic Action: If you control 4 or more resources, deploy this leader.

*Deploy text:* On Attack: Deal 1 damage to each enemy base.

### SOR_015 — Boba Fett *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Underworld,Bounty Hunter  **Aspects:** Cunning,Villainy

When an enemy unit leaves play: You may exhaust this leader. If you do, ready a resource.
Epic Action: If you control 5 or more resources, deploy this leader.

*Deploy text:* When this unit completes an attack: If an enemy unit left play this phase, ready up to 2 resources.

### SOR_016 — Grand Admiral Thrawn *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Imperial,Official  **Aspects:** Cunning,Villainy

When the action phase starts: Look at the top card of each player's deck.
Action [1 resource, exhaust]: Reveal the top card of any player's deck. Exhaust a unit that costs the same as or less than the revealed card.
Epic Action: If you control 6 or more resources, deploy this leader.

*Deploy text:* When the action phase starts: Look at the top card of each player's deck. On Attack: You may reveal the top card of any player's deck. Exhaust a unit that costs the same as or less than the revealed card.

### SOR_017 — Han Solo *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Underworld  **Aspects:** Cunning,Heroism

Action [exhaust]: Put a card from your hand into play as a resource and ready it. At the start of the next action phase, defeat a resource you control.
Epic Action: If you control 6 or more resources, deploy this leader.

*Deploy text:* On Attack: Put the top card of your deck into play as a resource and ready it. At the start of the next action phase, defeat a resource you control.

### SOR_018 — Jyn Erso *(Unique)*
**Type:** Leader  **Arena:** Ground  **Traits:** Rebel  **Aspects:** Cunning,Heroism

Action [Exhaust]: Attack with a unit. The defender gets -1/-0 for this attack.
Epic Action: If you control 6 or more resources, deploy this leader.

*Deploy text:* While a friendly unit is attacking, the defender gets -1/-0.

### SOR_019 — Security Complex ✅ DONE
**Type:** Base  **Aspects:** Vigilance

Epic Action: Give a Shield token to a non-leader unit.

### SOR_020 — Capital City
**Type:** Base  **Aspects:** Vigilance

### SOR_021 — Dagobah Swamp
**Type:** Base  **Aspects:** Vigilance

### SOR_022 — Energy Conversion Lab ✅ DONE
**Type:** Base  **Aspects:** Command

Epic Action: Play a unit that costs 6 resources or less from your hand. Give it AMBUSH for this phase.

### SOR_023 — Command Center
**Type:** Base  **Aspects:** Command

### SOR_024 — Echo Base
**Type:** Base  **Aspects:** Command

### SOR_025 — Tarkintown
**Type:** Base  **Aspects:** Aggression

Epic Action: Deal 3 damage to a damaged non-leader unit.

### SOR_026 — Catacombs of Cadera
**Type:** Base  **Aspects:** Aggression

### SOR_027 — Kestro City
**Type:** Base  **Aspects:** Aggression

### SOR_028 — Jedha City
**Type:** Base  **Aspects:** Cunning

Epic Action: Give a non-leader unit -4/-0 for this phase.

### SOR_029 — Administrator's Tower
**Type:** Base  **Aspects:** Cunning

### SOR_030 — Chopper Base
**Type:** Base  **Aspects:** Cunning

### SOR_031 — Inferno Four *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 2  **3/3**  **Traits:** Imperial,Vehicle,Fighter  **Aspects:** Vigilance,Villainy

When Played/When Defeated: Look at the top 2 cards of your deck. Put any number of them on the bottom of your deck and the rest on top in any order.

### SOR_032 — Scout Bike Pursuer
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/2**  **Traits:** Imperial,Trooper  **Aspects:** Vigilance,Villainy

Grit

### SOR_033 — Death Trooper
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/3**  **Traits:** Imperial,Trooper  **Aspects:** Vigilance,Villainy

When Played: Deal 2 damage to a friendly ground unit and 2 damage to an enemy ground unit.

### SOR_034 — Del Meeko *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/3**  **Traits:** Imperial,Trooper  **Aspects:** Vigilance,Villainy

Restore 1. Each event an opponent plays costs 1 more.

### SOR_035 — Lieutenant Childsen *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **2/4**  **Traits:** Imperial,Official  **Aspects:** Vigilance,Villainy

Sentinel. When Played: Reveal up to 4 [Vigilance] cards from your hand. For each card revealed this way, give an Experience token to this unit.

### SOR_036 — Gideon Hask *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/5**  **Traits:** Imperial,Trooper  **Aspects:** Vigilance,Villainy

When an enemy unit is defeated: Give an Experience token to a friendly unit.

### SOR_037 — Academy Defense Walker
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **4/6**  **Traits:** Imperial,Vehicle,Walker  **Aspects:** Vigilance,Villainy

Sentinel. When Played: Give an Experience token to each friendly damaged unit.

### SOR_038 — Count Dooku *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **5/5**  **Traits:** Force,Sith,Separatist  **Aspects:** Vigilance,Villainy

Shielded. When Played: You may defeat a unit with 4 or less remaining HP.

### SOR_039 — AT-AT Suppressor
**Type:** Unit  **Arena:** Ground  **Cost:** 7  **6/9**  **Traits:** Imperial,Vehicle,Walker  **Aspects:** Vigilance,Villainy

When Played: Exhaust all ground units.

### SOR_040 — Avenger *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 7  **6/7**  **Traits:** Imperial,Vehicle,Capital Ship  **Aspects:** Vigilance,Villainy

When Played/On Attack: An opponent chooses a non-leader unit they control. Defeat that unit.

### SOR_041 — Power of the Dark Side
**Type:** Event  **Cost:** 5  **Traits:** Innate  **Aspects:** Vigilance,Villainy

An opponent chooses a unit they control. Defeat that unit.

### SOR_042 — Search Your Feelings
**Type:** Event  **Cost:** 4  **Traits:** Innate  **Aspects:** Vigilance,Villainy

Search your deck for a card and draw it. (Then, shuffle your deck.)

### SOR_043 — Superlaser Blast
**Type:** Event  **Cost:** 8  **Traits:** Tactic,Disaster  **Aspects:** Vigilance,Villainy

Defeat all units.

### SOR_044 — Restored ARC-170
**Type:** Unit  **Arena:** Space  **Cost:** 2  **2/2**  **Traits:** Rebel,Vehicle,Fighter  **Aspects:** Vigilance,Heroism

Restore 1

### SOR_045 — Yoda *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/2**  **Traits:** Force,Jedi  **Aspects:** Vigilance,Heroism

Restore 2. When Defeated: Choose any number of players. They each draw a card.

### SOR_046 — Consular Security Force
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Rebel,Trooper  **Aspects:** Vigilance,Heroism

*(No text — vanilla)*

### SOR_047 — Kanan Jarrus *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/5**  **Traits:** Force,Jedi,Rebel,Spectre  **Aspects:** Vigilance,Heroism

On Attack: You may discard 1 card from the defending player's deck for each friendly SPECTRE unit. Heal 1 damage from your base for each different aspect among the discarded cards.

### SOR_048 — Vigilant Honor Guards
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/5**  **Traits:** Rebel  **Aspects:** Vigilance,Heroism

While this unit is undamaged, it gains Sentinel.

### SOR_049 — Obi-Wan Kenobi *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **3/6**  **Traits:** Force,Jedi  **Aspects:** Vigilance,Heroism

Sentinel. When Defeated: Give 2 Experience tokens to another friendly unit. If it's a Force unit, draw a card.

### SOR_050 — The Ghost *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 5  **4/6**  **Traits:** Rebel,Vehicle,Transport,Spectre  **Aspects:** Vigilance,Heroism

Shielded. When Played/On Attack: You may give a Shield token to another SPECTRE unit.

### SOR_051 — Luke Skywalker *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 7  **5/6**  **Traits:** Force,Jedi,Rebel  **Aspects:** Vigilance,Heroism

Restore 3. When Played: Give an enemy unit -3/-3 for this phase. If a friendly unit was defeated this phase, give that enemy unit -6/-6 for this phase instead.

### SOR_052 — Redemption *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 7  **4/8**  **Traits:** Rebel,Vehicle,Capital Ship  **Aspects:** Vigilance,Heroism

Sentinel. When Played: Heal up to 8 total damage from any number of units and/or bases. Deal that much damage to this unit.

### SOR_053 — Luke's Lightsaber *(Unique)*
**Type:** Upgrade  **Cost:** 2  **+2/+1**  **Traits:** Item,Weapon,Lightsaber  **Aspects:** Vigilance,Heroism

Attach to a non-Vehicle unit. When Played: If attached unit is Luke Skywalker, heal all damage from him and give a Shield token to him.

### SOR_054 — Jedi Lightsaber
**Type:** Upgrade  **Cost:** 2  **+2/+1**  **Traits:** Item,Weapon,Lightsaber  **Aspects:** Vigilance,Heroism

Attach to a non-VEHICLE unit. If attached unit is a FORCE unit, it gains: "On Attack: Give the defender -2/-2 for this phase."

### SOR_055 — The Force Is With Me
**Type:** Event  **Cost:** 3  **Traits:** Force  **Aspects:** Vigilance,Heroism

Choose a friendly unit and give 2 Experience tokens to it. If you control a FORCE unit, also give a Shield token to the chosen unit. You may attack with the chosen unit.

### SOR_056 — Bendu *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **3/8**  **Traits:** Creature,Force  **Aspects:** Vigilance,Vigilance

Sentinel. On Attack: The next non-[Heroism], non-[Villainy] card you play this phase costs 2 less.

### SOR_057 — Protector
**Type:** Upgrade  **Cost:** 1  **+0/+2**  **Traits:** Innate  **Aspects:** Vigilance,Vigilance

Attached unit gains Sentinel.

### SOR_058 — Vigilance
**Type:** Event  **Cost:** 5  **Traits:** Innate  **Aspects:** Vigilance,Vigilance

Choose two, in any order:
- Discard 6 cards from an opponent's deck.
- Heal 5 damage from a base.
- Defeat a unit with 3 or less remaining HP.
- Give a Shield token to a unit.

### SOR_059 — 2-1B Surgical Droid
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **1/3**  **Traits:** Droid  **Aspects:** Vigilance

On Attack: You may heal 2 damage from another unit.

### SOR_060 — Distant Patroller
**Type:** Unit  **Arena:** Space  **Cost:** 2  **2/2**  **Traits:** Fringe,Vehicle,Fighter  **Aspects:** Vigilance

When Defeated: You may give a Shield token to a [Vigilance] unit.

### SOR_061 — Guardian of the Whills
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Force,Fringe  **Aspects:** Vigilance

The first upgrade you play on this unit each round costs 1 less.

### SOR_062 — Regional Governor *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **2/6**  **Traits:** Imperial,Official  **Aspects:** Vigilance

When Played: Name a card. While this unit is in play, opponents can't play the named card.

### SOR_063 — Cloud City Wing Guard
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/3**  **Traits:** Fringe,Trooper  **Aspects:** Vigilance

Sentinel

### SOR_064 — Wilderness Fighter
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Trooper  **Aspects:** Vigilance

Shielded

### SOR_065 — Baze Malbus *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **5/5**  **Traits:** Fringe  **Aspects:** Vigilance

Grit. While you have the initiative, this unit gains Sentinel.

### SOR_066 — System Patrol Craft
**Type:** Unit  **Arena:** Space  **Cost:** 5  **4/6**  **Traits:** Vehicle,Fighter  **Aspects:** Vigilance

Sentinel

### SOR_067 — Rugged Survivors
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/6**  **Traits:** Fringe  **Aspects:** Vigilance

Grit. On Attack: If you control a leader unit, you may draw a card.

### SOR_068 — Cargo Juggernaut
**Type:** Unit  **Arena:** Ground  **Cost:** 6  **4/7**  **Traits:** Vehicle,Tank  **Aspects:** Vigilance

Shielded. When Played: If you control another [Vigilance] unit, heal 4 damage from your base.

### SOR_069 — Resilient
**Type:** Upgrade  **Cost:** 1  **+1/+2**  **Traits:** Innate  **Aspects:** Vigilance

*(No text — stat-only upgrade)*

### SOR_070 — Devotion
**Type:** Upgrade  **Cost:** 2  **+0/+2**  **Traits:** Innate  **Aspects:** Vigilance

Attached unit gains Restore 2.

### SOR_071 — Electrostaff
**Type:** Upgrade  **Cost:** 2  **+2/+1**  **Traits:** Item,Weapon  **Aspects:** Vigilance

Attach to a non-VEHICLE unit. While attached unit is defending, the attacker gets -1/-0.

### SOR_072 — Entrenched
**Type:** Upgrade  **Cost:** 1  **+1/+2**  **Traits:** Condition  **Aspects:** Vigilance

Attached unit can't attack bases.

### SOR_073 — Moment of Peace
**Type:** Event  **Cost:** 1  **Traits:** Innate  **Aspects:** Vigilance

Give a Shield token to a unit.

### SOR_074 — Repair
**Type:** Event  **Cost:** 2  **Traits:** Supply  **Aspects:** Vigilance

Heal 3 damage from a unit or base.

### SOR_075 — It Binds All Things
**Type:** Event  **Cost:** 2  **Traits:** Force  **Aspects:** Vigilance

Heal up to 3 damage from a unit. If you control a FORCE unit, you may deal that much damage to another unit.

### SOR_076 — Make an Opening
**Type:** Event  **Cost:** 2  **Traits:** Tactic  **Aspects:** Vigilance

Give a unit -2/-2 for this phase. Heal 2 damage from your base.

### SOR_077 — Takedown
**Type:** Event  **Cost:** 3  **Traits:** Tactic  **Aspects:** Vigilance

Defeat a unit with 5 or less remaining HP.

### SOR_078 — Vanquish
**Type:** Event  **Cost:** 5  **Traits:** Tactic  **Aspects:** Vigilance

Defeat a non-leader unit.

### SOR_079 — Admiral Piett *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **2/5**  **Traits:** Imperial,Official  **Aspects:** Command,Villainy

Each friendly non-leader unit that costs 6 or more gains Ambush.

### SOR_080 — General Tagge *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **2/5**  **Traits:** Imperial,Official  **Aspects:** Command,Villainy

When Played: Give an Experience token to each of up to 3 TROOPER units.

### SOR_081 — Seasoned Shoretrooper
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Imperial,Trooper  **Aspects:** Command,Villainy

While you control 6 or more resources, this unit gets +2/+0.

### SOR_082 — Emperor's Royal Guard
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/5**  **Traits:** Imperial  **Aspects:** Command,Villainy

While you control an OFFICIAL unit, this unit gains Sentinel. While you control Emperor Palpatine (as a leader or unit), this unit gets +0/+1.

### SOR_083 — Superlaser Technician *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/3**  **Traits:** Imperial  **Aspects:** Command,Villainy

When Defeated: You may put this unit into play as a resource and ready it.

### SOR_084 — Grand Moff Tarkin *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **3/6**  **Traits:** Imperial,Official  **Aspects:** Command,Villainy

When Played: Search the top 5 cards of your deck for up to 2 Imperial cards, reveal them, and draw them. (Put the other cards on the bottom of your deck in a random order.)

### SOR_085 — Rukh *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **1/2**  **Traits:** Imperial  **Aspects:** Command,Villainy

Shielded. When this unit deals combat damage to a non-leader unit while attacking: Defeat that unit.

### SOR_086 — Gladiator Star Destroyer *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 5  **5/5**  **Traits:** Imperial,Vehicle,Capital Ship  **Aspects:** Command,Villainy

When Played: Give a unit Sentinel for this phase.

### SOR_087 — Darth Vader *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 7  **7/8**  **Traits:** Force,Imperial,Sith  **Aspects:** Command,Villainy

Ambush. When Played: Search the top 10 cards of your deck for any number of [Villainy] units with combined cost 3 or less and play each of them for free.

### SOR_088 — Blizzard Assault AT-AT *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 8  **9/10**  **Traits:** Imperial,Vehicle,Walker  **Aspects:** Command,Villainy

When this unit attacks and defeats a unit: You may deal the excess damage from this attack to an enemy ground unit.

### SOR_089 — Relentless *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 7  **6/7**  **Traits:** Imperial,Vehicle,Capital Ship  **Aspects:** Command,Villainy

The first event played by each opponent each round loses all abilities.

### SOR_090 — Devastator *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 8  **8/8**  **Traits:** Imperial,Vehicle,Capital Ship  **Aspects:** Command,Villainy

Sentinel. Overwhelm. When Played: You may deal damage to a unit equal to the number of resources you control.

### SOR_091 — The Emperor's Legion
**Type:** Event  **Cost:** 4  **Traits:** Imperial,Supply  **Aspects:** Command,Villainy

Return each unit in your discard pile that was defeated this phase to your hand.

### SOR_092 — Overwhelming Barrage
**Type:** Event  **Cost:** 5  **Traits:** Tactic  **Aspects:** Command,Villainy

Give a friendly unit +2/+2 for this phase. Then, it deals damage equal to its power divided as you choose among any number of other units.

### SOR_093 — Alliance Dispatcher *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **2/4**  **Traits:** Rebel  **Aspects:** Command,Heroism

Action [exhaust]: Play a unit from your hand. It costs 1 less.

### SOR_094 — Bail Organa *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **2/4**  **Traits:** Rebel,Official  **Aspects:** Command,Heroism

Action [Exhaust]: Give an Experience token to another friendly unit.

### SOR_095 — Battlefield Marine
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/3**  **Traits:** Rebel,Trooper  **Aspects:** Command,Heroism

*(No text — vanilla)*

### SOR_096 — Mon Mothma *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **2/4**  **Traits:** Rebel,Official  **Aspects:** Command,Heroism

When Played: Search the top 5 cards of your deck for a REBEL card, reveal it, and draw it.

### SOR_097 — Admiral Ackbar *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **2/5**  **Traits:** Rebel,Official  **Aspects:** Command,Heroism

Restore 1. When Played: You may deal damage to a unit equal to the number of units you control in its arena.

### SOR_098 — Echo Base Defender
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **2/4**  **Traits:** Rebel,Trooper  **Aspects:** Command,Heroism

Sentinel

### SOR_099 — Bright Hope *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 4  **2/5**  **Traits:** Rebel,Vehicle,Transport  **Aspects:** Command,Heroism

Sentinel. When Played: You may return a friendly non-leader ground unit to its owner's hand. If you do, draw a card.

### SOR_100 — Wedge Antilles *(Unique)* ✅ DONE
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/3**  **Traits:** Rebel  **Aspects:** Command,Heroism

Each friendly VEHICLE unit gets +1/+1 and gains Ambush.

### SOR_101 — Rogue Squadron Skirmisher
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/3**  **Traits:** Rebel,Vehicle,Speeder  **Aspects:** Command,Heroism

Ambush. When Played: Return a unit that costs 2 or less from your discard pile to your hand.

### SOR_102 — Home One *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 8  **6/7**  **Traits:** Rebel,Vehicle,Capital Ship  **Aspects:** Command,Heroism

Restore 2. Each other friendly unit gains Restore 1. When Played: Play a [Heroism] unit from your discard pile. It costs 3 less.

### SOR_103 — Rebel Assault
**Type:** Event  **Cost:** 3  **Traits:** Rebel,Tactic  **Aspects:** Command,Heroism

Attack with a REBEL unit. It gets +1/+0 for this attack. Then, attack with another REBEL unit. It gets +1/+0 for this attack.

### SOR_104 — U-Wing Reinforcement
**Type:** Event  **Cost:** 7  **Traits:** Supply  **Aspects:** Command,Heroism

Search the top 10 cards of your deck for up to 3 units with combined cost 7 or less and play each of them for free.

### SOR_105 — General Krell *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 6  **5/6**  **Traits:** Force,Jedi,Republic  **Aspects:** Command,Command

Each other friendly unit gains: "When Defeated: You may draw a card."

### SOR_106 — Attack Pattern Delta
**Type:** Event  **Cost:** 5  **Traits:** Tactic  **Aspects:** Command,Command

Give a friendly unit +3/+3 for this phase. Give another friendly unit +2/+2 for this phase. Give a third friendly unit +1/+1 for this phase.

### SOR_107 — Command
**Type:** Event  **Cost:** 4  **Traits:** Innate  **Aspects:** Command,Command

Choose two, in any order:
- Give 2 Experience tokens to a unit.
- A friendly unit deals damage equal to its power to a non-unique enemy unit.
- Put this event into play as a resource.
- Return a unit from your discard pile to your hand.

### SOR_108 — Vanguard Infantry
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/2**  **Traits:** Trooper  **Aspects:** Command

When Defeated: You may give an Experience token to a unit.

### SOR_109 — Colonel Yularen *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **2/5**  **Traits:** Imperial,Official  **Aspects:** Command

When you play a [Command] unit (including this one): Heal 1 damage from your base.

### SOR_110 — Frontline Shuttle
**Type:** Unit  **Arena:** Space  **Cost:** 2  **2/3**  **Traits:** Vehicle,Transport  **Aspects:** Command

Action [defeat this unit]: Attack with a unit, even if it's exhausted. It can't attack bases for this attack.

### SOR_111 — Patrolling V-Wing
**Type:** Unit  **Arena:** Space  **Cost:** 3  **3/3**  **Traits:** Vehicle,Fighter  **Aspects:** Command

When Played: Draw a card.

### SOR_112 — Consortium StarViper
**Type:** Unit  **Arena:** Space  **Cost:** 3  **3/4**  **Traits:** Fringe,Vehicle,Fighter  **Aspects:** Command

While you have the initiative, this unit gains Restore 2.

### SOR_113 — Homestead Militia
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **2/4**  **Traits:** Fringe,Trooper  **Aspects:** Command

While you control 6 or more resources, this unit gains Sentinel.

### SOR_114 — Escort Skiff
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/3**  **Traits:** Underworld,Vehicle,Speeder  **Aspects:** Command

While you control another [Command] unit, this unit gains Ambush.

### SOR_115 — Agent Kallus *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Imperial,Trooper  **Aspects:** Command

Ambush. When another unique unit is defeated: You may draw a card. Use this ability only once each round.

### SOR_116 — Steadfast Battalion
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/5**  **Traits:** Trooper  **Aspects:** Command

Overwhelm. On Attack: If you control a leader unit, give a friendly unit +2/+2 for this phase.

### SOR_117 — Mercenary Company
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **4/4**  **Traits:** Underworld,Trooper  **Aspects:** Command

Ambush. Overwhelm.

### SOR_118 — 97th Legion
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **1/1**  **Traits:** Imperial,Trooper  **Aspects:** Command

This unit gets +1/+1 for each resource you control.

### SOR_119 — Reinforcement Walker
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **4/5**  **Traits:** Vehicle,Walker  **Aspects:** Command

When Played/On Attack: Look at the top card of your deck. Either draw that card or discard it and heal 3 damage from your base.

### SOR_120 — Academy Training
**Type:** Upgrade  **Cost:** 1  **+1/+1**  **Traits:** Learned  **Aspects:** Command

*(No text — stat-only upgrade)*

### SOR_121 — Hardpoint Heavy Blaster
**Type:** Upgrade  **Cost:** 2  **+1/+0**  **Traits:** Modification,Weapon  **Aspects:** Command

Attach to a VEHICLE unit. Attached unit gains: "On Attack: If this unit isn't attacking a base, you may deal 2 damage to a unit in the defender's arena."

### SOR_122 — Traitorous
**Type:** Upgrade  **Cost:** 3  **+0/+0**  **Traits:** Innate  **Aspects:** Command

When this upgrade becomes attached to a non-leader unit that costs 3 or less: Take control of that unit. When this upgrade becomes unattached from a unit: That unit's owner takes control of it.

### SOR_123 — Recruit
**Type:** Event  **Cost:** 2  **Traits:** Supply  **Aspects:** Command

Search the top 5 cards of your deck for a unit, reveal it, and draw it.

### SOR_124 — Tactical Advantage
**Type:** Event  **Cost:** 2  **Traits:** Tactic  **Aspects:** Command

Give a unit +2/+2 for this phase.

### SOR_125 — Prepare for Takeoff
**Type:** Event  **Cost:** 4  **Traits:** Plan  **Aspects:** Command

Search the top 8 cards of your deck for up to 2 Vehicle units, reveal them, and draw them.

### SOR_126 — Resupply
**Type:** Event  **Cost:** 1  **Traits:** Supply  **Aspects:** Command

Put this event into play as a resource.

### SOR_127 — Strike True
**Type:** Event  **Cost:** 2  **Traits:** Tactic  **Aspects:** Command

A friendly unit deals damage equal to its power to an enemy unit.

### SOR_128 — Death Star Stormtrooper
**Type:** Unit  **Arena:** Ground  **Cost:** 1  **1/1**  **Traits:** Imperial,Trooper  **Aspects:** Aggression,Villainy

*(No text — vanilla)*

### SOR_129 — Admiral Ozzel *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **1/4**  **Traits:** Imperial,Official  **Aspects:** Aggression,Villainy

Action [exhaust]: Play an Imperial unit from your hand (paying its cost). It enters play ready. Each opponent may ready a unit.

### SOR_130 — First Legion Snowtrooper
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/3**  **Traits:** Imperial,Trooper  **Aspects:** Aggression,Villainy

While attacking a damaged unit, this unit gets +2/+0 and gains Overwhelm.

### SOR_131 — Fifth Brother *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/5**  **Traits:** Force,Imperial,Inquisitor  **Aspects:** Aggression,Villainy

This unit gains Raid 1 for each damage on him. On Attack: You may deal 1 damage to this unit and 1 damage to another ground unit.

### SOR_132 — Imperial Interceptor
**Type:** Unit  **Arena:** Space  **Cost:** 2  **3/2**  **Traits:** Imperial,Vehicle,Fighter  **Aspects:** Aggression,Villainy

When Played: You may deal 3 damage to a space unit.

### SOR_133 — Seventh Sister *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **4/4**  **Traits:** Force,Imperial,Inquisitor  **Aspects:** Aggression,Villainy

Saboteur. When this unit deals combat damage to an opponent's base: You may deal 3 damage to a ground unit that opponent controls.

### SOR_134 — Ruthless Raider
**Type:** Unit  **Arena:** Space  **Cost:** 4  **4/3**  **Traits:** Imperial,Vehicle,Capital Ship  **Aspects:** Aggression,Villainy

When Played/When Defeated: Deal 2 damage to an enemy base and 2 damage to an enemy unit.

### SOR_135 — Emperor Palpatine *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 8  **7/7**  **Traits:** Force,Imperial,Sith,Official  **Aspects:** Aggression,Villainy

Overwhelm. When Played: Deal 6 damage divided as you choose among enemy units.

### SOR_136 — Vader's Lightsaber *(Unique)*
**Type:** Upgrade  **Cost:** 2  **+3/+1**  **Traits:** Item,Weapon,Lightsaber  **Aspects:** Aggression,Villainy

Attach to a non-Vehicle unit. When Played: If attached unit is Darth Vader, you may deal 4 damage to a ground unit.

### SOR_137 — Fallen Lightsaber
**Type:** Upgrade  **Cost:** 2  **+2/+1**  **Traits:** Item,Weapon,Lightsaber  **Aspects:** Aggression,Villainy

Attach to a non-Vehicle unit. If attached unit is a Force unit, it gains: "On Attack: Deal 1 damage to each ground unit the defending player controls."

### SOR_138 — Force Lightning
**Type:** Event  **Cost:** 3  **Traits:** Force  **Aspects:** Aggression,Villainy

Choose a unit. It loses all abilities for this phase. Then, if you control a FORCE unit, pay any number of resources and deal 2 damage to the chosen unit for each resource paid this way.

### SOR_139 — Force Choke
**Type:** Event  **Cost:** 4  **Traits:** Force  **Aspects:** Aggression,Villainy

If you control a FORCE unit, this event costs 1 less to play. Deal 5 damage to a non-VEHICLE unit. That unit's controller draws a card.

### SOR_140 — SpecForce Soldier
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/3**  **Traits:** Rebel,Trooper  **Aspects:** Aggression,Heroism

When Played: A unit loses Sentinel for this phase.

### SOR_141 — Green Squadron A-Wing
**Type:** Unit  **Arena:** Space  **Cost:** 2  **2/2**  **Traits:** Rebel,Vehicle,Fighter  **Aspects:** Aggression,Heroism

Raid 2

### SOR_142 — Sabine Wren *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/2**  **Traits:** Mandalorian,Rebel,Spectre  **Aspects:** Aggression,Heroism

While there are at least 3 aspects among other friendly units, this unit can't be attacked (unless she gains Sentinel). On Attack: You may deal 1 damage to the defender or to a base.

### SOR_143 — Fighters for Freedom
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Rebel,Trooper  **Aspects:** Aggression,Heroism

Saboteur. When you play another [Aggression] card: You may deal 1 damage to a base.

### SOR_144 — Red Three *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 3  **3/3**  **Traits:** Rebel,Vehicle,Fighter  **Aspects:** Aggression,Heroism

Raid 1. Each other friendly [Heroism] unit gains Raid 1.

### SOR_145 — K-2SO *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/5**  **Traits:** Rebel,Droid  **Aspects:** Aggression,Heroism

Overwhelm. When Defeated: For each opponent, choose one: either deal 3 damage to that player's base, or that player discards a card from their hand.

### SOR_146 — Zeb Orrelios *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **4/4**  **Traits:** Rebel,Spectre  **Aspects:** Aggression,Heroism

When this unit completes an attack: If the defender was defeated, you may deal 4 damage to a ground unit.

### SOR_147 — Black One *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 4  **4/3**  **Traits:** Resistance,Vehicle,Fighter  **Aspects:** Aggression,Heroism

When Played/When Defeated: You may discard your hand. If you do, draw 3 cards.

### SOR_148 — Guerilla Attack Pod
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/6**  **Traits:** Rebel,Vehicle,Walker  **Aspects:** Aggression,Heroism

Grit. When Played: If a base has 15 or more damage on it, ready this unit.

### SOR_149 — Mace Windu *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 6  **5/7**  **Traits:** Force,Jedi,Republic  **Aspects:** Aggression,Heroism

Ambush. When this unit attacks and defeats a unit: Ready him.

### SOR_150 — Heroic Sacrifice
**Type:** Event  **Cost:** 3  **Traits:** Tactic  **Aspects:** Aggression,Heroism

Draw a card, then attack with a unit. For this attack, it gets +2/+0 and gains: "When this unit deals combat damage: Defeat it."

### SOR_151 — Karabast
**Type:** Event  **Cost:** 1  **Traits:** Spectre  **Aspects:** Aggression,Heroism

A friendly unit deals damage to an enemy unit equal to the amount of damage on the friendly unit plus 1.

### SOR_152 — For a Cause I Believe In
**Type:** Event  **Cost:** 3  **Traits:** Innate  **Aspects:** Aggression,Heroism

Reveal the top 4 cards of your deck. For each [Heroism] card revealed this way, deal 1 damage to an enemy base. You may discard any of the revealed cards and put the rest back on top of your deck in any order.

### SOR_153 — Saw Gerrera *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **5/5**  **Traits:** Rebel  **Aspects:** Aggression,Aggression

As an additional cost for each opponent to play an event, they must deal 2 damage to their base.

### SOR_154 — Rallying Cry
**Type:** Event  **Cost:** 4  **Traits:** Tactic  **Aspects:** Aggression,Aggression

Each friendly unit gains Raid 2 this phase.

### SOR_155 — Aggression
**Type:** Event  **Cost:** 4  **Traits:** Innate  **Aspects:** Aggression,Aggression

Choose two, in any order:
- Draw a card.
- Defeat up to 2 upgrades.
- Ready a unit with 3 or less power.
- Deal 4 damage to a unit.

### SOR_156 — Benthic "Two Tubes" *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Rebel,Trooper  **Aspects:** Aggression

On Attack: Another friendly [Aggression] unit gains Raid 2 for this phase.

### SOR_157 — Cantina Braggart
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **3/1**  **Traits:** Underworld  **Aspects:** Aggression

Raid 2

### SOR_158 — Jedha Agitator
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **2/4**  **Traits:** Rebel  **Aspects:** Aggression

Saboteur. On Attack: If you control a leader unit, deal 2 damage to a ground unit or a base.

### SOR_159 — Partisan Insurgent
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/3**  **Traits:** Rebel,Trooper  **Aspects:** Aggression

While you control another [Aggression] unit, this unit gains Raid 2.

### SOR_160 — Wolffe *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/3**  **Traits:** Fringe,Clone  **Aspects:** Aggression

Saboteur. When Played/On Attack: Bases can't be healed for this phase.

### SOR_161 — Ardent Sympathizer
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/2**  **Traits:** Trooper  **Aspects:** Aggression

While you have the initiative, this unit gets +2/+0.

### SOR_162 — Disabling Fang Fighter
**Type:** Unit  **Arena:** Space  **Cost:** 3  **3/3**  **Traits:** Mandalorian,Vehicle,Fighter  **Aspects:** Aggression

When Played: You may defeat an upgrade.

### SOR_163 — Star Wing Scout
**Type:** Unit  **Arena:** Space  **Cost:** 2  **2/2**  **Traits:** Vehicle,Fighter  **Aspects:** Aggression

When Defeated: If you have the initiative, draw 2 cards.

### SOR_164 — Wampa
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **5/5**  **Traits:** Creature  **Aspects:** Aggression

Overwhelm

### SOR_165 — Occupier Siege Tank
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/7**  **Traits:** Imperial,Vehicle,Tank  **Aspects:** Aggression

Grit

### SOR_166 — Infiltrator's Skill
**Type:** Upgrade  **Cost:** 1  **+1/+0**  **Traits:** Learned  **Aspects:** Aggression

Attached unit gains Saboteur.

### SOR_167 — Force Throw
**Type:** Event  **Cost:** 2  **Traits:** Force  **Aspects:** Aggression

Choose a player. That player discards a card from their hand. Then, if you control a FORCE unit, you may deal damage to a unit equal to the cost of the discarded card.

### SOR_168 — Precision Fire
**Type:** Event  **Cost:** 3  **Traits:** Tactic  **Aspects:** Aggression

Attack with a unit. It gains Saboteur for this attack. If it's a TROOPER, it also gets +2/+0 for this attack.

### SOR_169 — Keep Fighting
**Type:** Event  **Cost:** 2  **Traits:** Tactic  **Aspects:** Aggression

Ready a unit with 3 or less power.

### SOR_170 — Power Failure
**Type:** Event  **Cost:** 2  **Traits:** Tactic  **Aspects:** Aggression

Defeat any number of upgrades on a unit.

### SOR_171 — Mission Briefing
**Type:** Event  **Cost:** 2  **Traits:** Plan  **Aspects:** Aggression

Choose a player. They draw 2 cards.

### SOR_172 — Open Fire
**Type:** Event  **Cost:** 3  **Traits:** Tactic  **Aspects:** Aggression

Deal 4 damage to a unit.

### SOR_173 — Bombing Run
**Type:** Event  **Cost:** 5  **Traits:** Tactic  **Aspects:** Aggression

Choose an arena (ground or space). Deal 3 damage to each unit in that arena.

### SOR_174 — Smoke and Cinders
**Type:** Event  **Cost:** 2  **Traits:** Disaster  **Aspects:** Aggression

Each player discards all but 2 cards (of their choice) from their hand.

### SOR_175 — Forced Surrender
**Type:** Event  **Cost:** 5  **Traits:** Plan  **Aspects:** Aggression

Draw 2 cards. Each opponent whose base you've damaged this phase discards 2 cards from their hand.

### SOR_176 — ISB Agent
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/3**  **Traits:** Imperial  **Aspects:** Cunning,Villainy

When Played: You may reveal an event from your hand. If you do, deal 1 damage to a unit.

### SOR_177 — Bib Fortuna *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **2/4**  **Traits:** Underworld,Twi'lek  **Aspects:** Cunning,Villainy

Shielded. Action [Exhaust]: Play an event from your hand. It costs 1 less.

### SOR_178 — Cartel Spacer
**Type:** Unit  **Arena:** Space  **Cost:** 3  **3/3**  **Traits:** Underworld,Vehicle,Fighter  **Aspects:** Cunning,Villainy

When Played: If you control another [Cunning] unit, exhaust an enemy unit that costs 4 or less.

### SOR_179 — Boba Fett *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **5/5**  **Traits:** Underworld,Bounty Hunter  **Aspects:** Cunning,Villainy

On Attack: If this unit is attacking an exhausted unit that didn't enter play this round, deal 3 damage to the defender.

### SOR_180 — Seventh Fleet Defender
**Type:** Unit  **Arena:** Space  **Cost:** 4  **4/4**  **Traits:** Imperial,Vehicle,Fighter  **Aspects:** Cunning,Villainy

Shielded

### SOR_181 — Jabba the Hutt *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 6  **5/7**  **Traits:** Underworld,Hutt  **Aspects:** Cunning,Villainy

Each TRICK event you play costs 1 less. When Played: Search the top 8 cards of your deck for a TRICK event, reveal it, and draw it.

### SOR_182 — Bossk *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **5/5**  **Traits:** Underworld,Bounty Hunter  **Aspects:** Cunning,Villainy

Ambush. When you play an event: You may deal 2 damage to a unit.

### SOR_183 — Bounty Hunter Crew
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/5**  **Traits:** Underworld,Bounty Hunter  **Aspects:** Cunning,Villainy

Ambush. When Played: You may return an event from a discard pile to its owner's hand.

### SOR_184 — Fett's Firespray *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 5  **4/6**  **Traits:** Underworld,Vehicle,Transport  **Aspects:** Cunning,Villainy

When Played: If you control Boba Fett or Jango Fett (as a leader or unit), ready this unit.
Action [2 resources]: Exhaust a non-unique unit.

### SOR_185 — Chimaera *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 7  **6/7**  **Traits:** Imperial,Vehicle,Capital Ship  **Aspects:** Cunning,Villainy

Shielded. On Attack: Name a card. An opponent reveals their hand and discards a card with that name from it.

### SOR_186 — No Good to Me Dead
**Type:** Event  **Cost:** 3  **Traits:** Plan  **Aspects:** Cunning,Villainy

Exhaust a unit. That unit can't ready this round (including during the regroup phase).

### SOR_187 — I Had No Choice
**Type:** Event  **Cost:** 4  **Traits:** Trick  **Aspects:** Cunning,Villainy

Choose up to 2 non-leader units. An opponent chooses 1 of those units. Return that unit to its owner's hand and put the other on the bottom of its owner's deck.

### SOR_188 — Chopper *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/3**  **Traits:** Rebel,Droid,Spectre  **Aspects:** Cunning,Heroism

While you control another SPECTRE unit, this unit gains Raid 1. On Attack: Discard a card from the defending player's deck. If it's an event, exhaust a resource that player controls.

### SOR_189 — Leia Organa *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/5**  **Traits:** Rebel,Official  **Aspects:** Cunning,Heroism

When Played: Either ready a resource or exhaust a unit.

### SOR_190 — Lothal Insurgent
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Rebel  **Aspects:** Cunning,Heroism

When Played: If you played another card this phase, each opponent draws a card then discards a random card from their hand.

### SOR_191 — Vanguard Ace
**Type:** Unit  **Arena:** Space  **Cost:** 3  **2/3**  **Traits:** New Republic,Vehicle,Fighter  **Aspects:** Cunning,Heroism

When Played: For each other card you played this phase, give an Experience token to this unit.

### SOR_192 — Ezra Bridger *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/5**  **Traits:** Force,Rebel,Spectre  **Aspects:** Cunning,Heroism

When this unit completes an attack: Look at the top card of your deck. You may play it, discard it, or leave it on top of your deck.

### SOR_193 — Millennium Falcon *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 6  **4/7**  **Traits:** Underworld,Vehicle,Transport  **Aspects:** Cunning,Heroism

This unit enters play ready. When you ready cards during the regroup phase: Either pay 1 resource or return this unit to her owner's hand.

### SOR_194 — Rogue Operative
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/3**  **Traits:** Rebel,Trooper  **Aspects:** Cunning,Heroism

Saboteur. Raid 2.

### SOR_195 — Auzituck Liberator Gunship
**Type:** Unit  **Arena:** Space  **Cost:** 3  **3/3**  **Traits:** Vehicle,Fighter  **Aspects:** Cunning,Heroism

Ambush

### SOR_196 — Chewbacca *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **6/5**  **Traits:** Underworld,Wookiee  **Aspects:** Cunning,Heroism

Sentinel. When this unit is attacked: Ready him.

### SOR_197 — Lando Calrissian *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 4  **3/5**  **Traits:** Fringe,Official  **Aspects:** Cunning,Heroism

Saboteur. When Played: Return up to 2 friendly resources to their owners' hands.

### SOR_198 — Han Solo *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 6  **5/6**  **Traits:** Underworld  **Aspects:** Cunning,Heroism

Ambush. While attacking, this unit deals combat damage before the defender.

### SOR_199 — Bamboozle
**Type:** Event  **Cost:** 2  **Traits:** Trick  **Aspects:** Cunning,Heroism

You may discard a [Cunning] card from your hand instead of paying this event's cost. Exhaust a unit and return each upgrade on it to its owner's hand.

### SOR_200 — Spark of Rebellion
**Type:** Event  **Cost:** 2  **Traits:** Spectre  **Aspects:** Cunning,Heroism

Look at an opponent's hand and discard a card from it.

### SOR_201 — Bodhi Rook *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/3**  **Traits:** Imperial,Rebel  **Aspects:** Cunning,Cunning

When Played: Look at an opponent's hand and discard a non-unit card from it.

### SOR_202 — Cantina Bouncer
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Fringe  **Aspects:** Cunning,Cunning

When Played: You may return a non-leader unit to its owner's hand.

### SOR_203 — Cunning
**Type:** Event  **Cost:** 4  **Traits:** Innate  **Aspects:** Cunning,Cunning

Choose two, in any order:
- Return a non-leader unit with 4 or less power to its owner's hand.
- Give a unit +4/+0 for this phase.
- Exhaust up to 2 units.
- An opponent discards a random card from their hand.

### SOR_204 — Greedo *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/2**  **Traits:** Underworld,Bounty Hunter  **Aspects:** Cunning

When Defeated: You may discard a card from your deck. If it's not a unit, deal 2 damage to a ground unit.

### SOR_205 — Jawa Scavenger
**Type:** Unit  **Arena:** Ground  **Cost:** 1  **1/1**  **Traits:** Fringe,Jawa  **Aspects:** Cunning

Saboteur

### SOR_206 — Mining Guild TIE Fighter
**Type:** Unit  **Arena:** Space  **Cost:** 2  **2/3**  **Traits:** Fringe,Vehicle,Fighter  **Aspects:** Cunning

On Attack: You may pay 2 resources. If you do, draw a card.

### SOR_207 — Crafty Smuggler
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Underworld  **Aspects:** Cunning

Shielded

### SOR_208 — Outer Rim Headhunter
**Type:** Unit  **Arena:** Space  **Cost:** 4  **4/4**  **Traits:** Fringe,Vehicle,Fighter  **Aspects:** Cunning

Raid 1. On Attack: If you control a leader unit, you may exhaust a non-leader unit.

### SOR_209 — Pirated Starfighter
**Type:** Unit  **Arena:** Space  **Cost:** 3  **3/3**  **Traits:** Underworld,Vehicle,Fighter  **Aspects:** Cunning

Raid 1. When Played: Return a friendly non-leader unit to its owner's hand.

### SOR_210 — Swoop Racer
**Type:** Unit  **Arena:** Ground  **Cost:** 1  **2/1**  **Traits:** Fringe  **Aspects:** Cunning

*(No text — vanilla)*

### SOR_211 — Gamorrean Guards
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Underworld  **Aspects:** Cunning

While you control another [Cunning] unit, this unit gains Sentinel.

### SOR_212 — Strafing Gunship
**Type:** Unit  **Arena:** Space  **Cost:** 4  **4/4**  **Traits:** Underworld,Vehicle,Fighter  **Aspects:** Cunning

This unit can attack units in the ground arena. While this unit is attacking a ground unit, the defender gets -2/-0.

### SOR_213 — Syndicate Lackeys
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/3**  **Traits:** Underworld  **Aspects:** Cunning

Ambush

### SOR_214 — Smuggling Compartment
**Type:** Upgrade  **Cost:** 2  **+0/+2**  **Traits:** Modification  **Aspects:** Cunning

Attach to a VEHICLE unit. Attached unit gains: "On Attack: Ready a resource."

### SOR_215 — Snapshot Reflexes
**Type:** Upgrade  **Cost:** 1  **+1/+1**  **Traits:** Learned  **Aspects:** Cunning

When Played: You may attack with attached unit.

### SOR_216 — Disarm
**Type:** Event  **Cost:** 2  **Traits:** Tactic  **Aspects:** Cunning

Give an enemy unit -4/-0 for this phase.

### SOR_217 — Shoot First
**Type:** Event  **Cost:** 2  **Traits:** Trick  **Aspects:** Cunning

Attack with a unit. It gets +1/+0 for this attack and deals its combat damage before the defender. (If the defender is defeated, it deals no combat damage.) *(already implemented and tested)*

### SOR_218 — Asteroid Sanctuary
**Type:** Event  **Cost:** 2  **Traits:** Trick  **Aspects:** Cunning

Exhaust an enemy unit. Give a Shield token to a friendly unit that costs 3 or less.

### SOR_219 — Sneak Attack
**Type:** Event  **Cost:** 2  **Traits:** Trick  **Aspects:** Cunning

Play a unit from your hand. It costs 3 less and enters play ready. At the start of the regroup phase, defeat it.

### SOR_220 — Surprise Strike
**Type:** Event  **Cost:** 2  **Traits:** Tactic  **Aspects:** Cunning

Attack with a unit. It gets +3/+0 for this attack.

### SOR_221 — Outmaneuver
**Type:** Event  **Cost:** 4  **Traits:** Tactic  **Aspects:** Cunning

Choose an arena (ground or space). Exhaust each unit in that arena.

### SOR_222 — Waylay
**Type:** Event  **Cost:** 3  **Traits:** Trick  **Aspects:** Cunning

Return a non-leader unit to its owner's hand.

### SOR_223 — Don't Get Cocky
**Type:** Event  **Cost:** 3  **Traits:** Gambit  **Aspects:** Cunning

Choose a unit. One at a time, reveal cards from your deck until you choose to stop or have revealed 7 cards. If the combined cost of the revealed cards is 7 or less, deal that much damage to the chosen unit. Put the revealed cards on the bottom of your deck in a random order.

### SOR_224 — Change of Heart
**Type:** Event  **Cost:** 4  **Traits:** Gambit  **Aspects:** Cunning

Take control of a non-leader unit. At the start of the regroup phase, its owner takes control of it.

### SOR_225 — TIE/ln Fighter
**Type:** Unit  **Arena:** Space  **Cost:** 1  **2/1**  **Traits:** Imperial,Vehicle,Fighter  **Aspects:** Villainy

*(No text — vanilla)*

### SOR_226 — Admiral Motti *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **2/4**  **Traits:** Imperial,Official  **Aspects:** Villainy

When Defeated: You may ready a [Villainy] unit.

### SOR_227 — Snowtrooper Lieutenant
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/3**  **Traits:** Imperial,Trooper  **Aspects:** Villainy

When Played: You may attack with a unit. If it's an Imperial unit, it gets +2/+0 for this attack.

### SOR_228 — Viper Probe Droid
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **1/3**  **Traits:** Imperial,Droid  **Aspects:** Villainy

When Played: Look at an opponent's hand.

### SOR_229 — Cell Block Guard
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Imperial,Trooper  **Aspects:** Villainy

Sentinel

### SOR_230 — General Veers *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **2/4**  **Traits:** Imperial,Official  **Aspects:** Villainy

Other friendly Imperial units get +1/+1.

### SOR_231 — TIE Advanced *(Unique)*
**Type:** Unit  **Arena:** Space  **Cost:** 3  **3/4**  **Traits:** Imperial,Vehicle,Fighter  **Aspects:** Villainy

When Played: Give 2 Experience tokens to another friendly IMPERIAL unit.

### SOR_232 — AT-ST
**Type:** Unit  **Arena:** Ground  **Cost:** 5  **5/5**  **Traits:** Imperial,Vehicle,Walker  **Aspects:** Villainy

Overwhelm

### SOR_233 — I Am Your Father
**Type:** Event  **Cost:** 5  **Traits:** Gambit  **Aspects:** Villainy

Deal 7 damage to an enemy unit unless its controller says "no." If they do, draw 3 cards.

### SOR_234 — Maximum Firepower
**Type:** Event  **Cost:** 4  **Traits:** Imperial,Tactic  **Aspects:** Villainy

A friendly Imperial unit deals damage equal to its power to a unit. Then, another friendly Imperial unit deals damage equal to its power to the same unit.

### SOR_235 — Galactic Ambition
**Type:** Event  **Cost:** 3  **Traits:** Innate  **Aspects:** Villainy

Play a non-[Heroism] unit from your hand for free. Deal damage to your base equal to its cost.

### SOR_236 — R2-D2 *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **1/4**  **Traits:** Rebel,Droid  **Aspects:** Heroism

When Played/On Attack: Look at the top card of your deck. You may put it on the bottom of your deck. (Otherwise, leave it on top of your deck.)

### SOR_237 — Alliance X-Wing
**Type:** Unit  **Arena:** Space  **Cost:** 2  **2/3**  **Traits:** Rebel,Vehicle,Fighter  **Aspects:** Heroism

*(No text — vanilla)*

### SOR_238 — C-3PO *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **1/4**  **Traits:** Rebel,Droid  **Aspects:** Heroism

When Played/On Attack: Choose a number, then look at the top card of your deck. If its cost is the chosen number, you may reveal and draw it.

### SOR_239 — Rebel Pathfinder
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/2**  **Traits:** Rebel,Trooper  **Aspects:** Heroism

Saboteur

### SOR_240 — Fleet Lieutenant
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/3**  **Traits:** Rebel,Trooper  **Aspects:** Heroism

When Played: You may attack with a unit. If it's a Rebel unit, it gets +2/+0 for this attack.

### SOR_241 — Wing Leader
**Type:** Unit  **Arena:** Space  **Cost:** 3  **3/3**  **Traits:** Rebel,Vehicle,Fighter  **Aspects:** Heroism

When Played: Give 2 Experience tokens to another friendly REBEL unit.

### SOR_242 — General Dodonna *(Unique)*
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **2/4**  **Traits:** Rebel,Official  **Aspects:** Heroism

Other friendly Rebel units get +1/+1.

### SOR_243 — Regional Sympathizers
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/4**  **Traits:** Rebel  **Aspects:** Heroism

Restore 2

### SOR_244 — Snowspeeder
**Type:** Unit  **Arena:** Ground  **Cost:** 3  **3/3**  **Traits:** Rebel,Vehicle,Speeder  **Aspects:** Heroism

Ambush. On Attack: Exhaust an enemy Vehicle ground unit.

### SOR_245 — Medal Ceremony
**Type:** Event  **Cost:** 3  **Traits:** Rebel  **Aspects:** Heroism

Give an Experience token to each of up to 3 REBEL units that attacked this phase.

### SOR_246 — You're My Only Hope
**Type:** Event  **Cost:** 5  **Traits:** Gambit  **Aspects:** Heroism

Look at the top card of your deck. You may play it. It costs 5 less. If your base has 5 or less remaining HP, you may play it for free instead.

### SOR_247 — Underworld Thug
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/2**  **Traits:** Underworld

*(No text — vanilla)*

### SOR_248 — Volunteer Soldier
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/2**  **Traits:** Trooper

Raid 1. If you control a TROOPER unit, this unit costs 1 less to play.

### SOR_249 — Frontier AT-RT
**Type:** Unit  **Arena:** Ground  **Cost:** 2  **2/2**  **Traits:** Vehicle,Walker

While you control another VEHICLE unit, this unit gains Ambush.

### SOR_250 — Corellian Freighter
**Type:** Unit  **Arena:** Space  **Cost:** 4  **3/5**  **Traits:** Vehicle,Transport

Sentinel

### SOR_251 — Confiscate
**Type:** Event  **Cost:** 1  **Traits:** Law

Defeat an upgrade.

### SOR_252 — Restock
**Type:** Event  **Cost:** 2  **Traits:** Supply

Choose up to 4 cards in a discard pile. Put them on the bottom of their owner's deck in a random order.

### SOR_T01 — Experience
**Type:** Token Upgrade  **Traits:** Learned

*(+1/+1 stat token — already implemented as subcard)*

### SOR_T02 — Shield
**Type:** Token Upgrade  **Traits:** Armor

If damage would be dealt to attached unit, prevent that damage. If you do, defeat a Shield token on it. *(already implemented)*

---

## Implementation Order Recommendation

### Phase 1 — Shared primitives (implement these first, unblocks most of Medium tier)
1. ✅ `DoExhaustUnit` — `EXHAUST_UNIT` custom DQ handler in GameLogic.php
2. ✅ `DoReadyUnit` — `READY_UNIT` custom DQ handler in GameLogic.php
3. ✅ `DoReturnUnitToHand` — `BOUNCE_UNIT` custom DQ handler in GameLogic.php
4. ✅ `DoScry($player, $N)` — universal SCRY_CHOICE + SCRY_REORDER handlers in GameLogic.php; P1DECKTOPCARD assertion added
5. `DoSearchDeckTop($player, $N, $filter, $count)` — search + reveal + draw

### Phase 2 — All Simple tier cards (no infrastructure needed)
~95 cards, each takes minutes to implement following existing patterns.

### Phase 3 — Medium groups (after Phase 1 primitives are done)
Work through M1–M12 groups. Each group is 3–8 cards once the primitive exists.

### Phase 4 — Complex tier (last)
Leaders, modal "choose 2" events, control-steal, phase-state tracking, Gambit events.

---

## Is giving Fable 5 the whole set a good idea?

**Short answer:** Feasible with the right plan structure, but risky as a single task.

**What works in your favor:**
- ~37% of the set (Simple tier) is pure pattern-matching against existing code. Fable could handle those reliably.
- The engine already has DoDrawCard, DoGiveExperienceToken, OnExhaustCard, OnReadyCard — so "Medium" cards largely just need wiring, not invention.
- You have 80 green tests providing a strong regression harness.

**Risks:**
- Phase 1 primitives (scry, deck search) need careful design — if Fable gets the DQ pattern wrong, dozens of Medium-tier cards will be subtly broken.
- Complex-tier cards (leaders, modal events, control-steal) need human review. An autonomous agent will likely cut corners on edge cases.
- 254 cards is a lot of context. A subagent-driven-development approach (one mechanic group per task, spec-reviewer gate between each) is safer than one monolithic task.

**Recommendation:** Use `swusim-implement-card` batched by Phase above, not by set. Do Phase 1 (primitives) as a human-reviewed design session, then turn Fable loose on Phase 2 (Simple tier), then Phase 3 group-by-group. Reserve Phase 4 (Complex) for human-guided implementation.
