# HMW — Card Implementation Plan

**⚠ PREVIEW SET.** 109 cards exist (107 numbered + 2 tokens) of ~262 printed — count re-derived from
`AppCore/SWU/CardMocks.php` on 2026-08-27; the last import wave was 2026-08-26 (three separate rewrites
that session: 103 → 108 → 109). Earlier waves: 2026-08-26 (HMW_175 / HMW_208 / HMW_225 / HMW_237), and the wave imported
2026-08-24 (HMW_018 / HMW_180 / HMW_212 / HMW_221 / HMW_222 / HMW_230 / HMW_240 / HMW_268 landed
then) — as mock entries in `AppCore/SWU/CardMocks.php`. Regenerate this plan (`swusim-generate-set-implement-doc HMW`) as more
previews land — the phases below cover only what was previewed when each was written.

⚠ The phase batches below cover the ORIGINAL 21 cards. Cards previewed later were implemented
individually and appear only on the `### Already Done` line — that line, diffed against the HMW
entries in `CardMocks.php`, is the authoritative "what is left" check. (Counting batches instead
would have reported this set complete while HMW_003 was still unimplemented.)

### Already Done
HMW_019, HMW_T02, HMW_T03, HMW_009, HMW_004, HMW_061, HMW_095, HMW_081, HMW_121, HMW_171, HMW_085, HMW_127, HMW_142, HMW_234, HMW_257, HMW_177, HMW_255, HMW_059, HMW_168, HMW_206, HMW_060, HMW_164, HMW_162, HMW_193, HMW_014, HMW_115, HMW_116, HMW_136, HMW_124, HMW_003, HMW_062, HMW_064, HMW_070, HMW_020, HMW_021, HMW_023, HMW_024, HMW_026, HMW_027, HMW_028, HMW_029, HMW_030, HMW_031, HMW_033, HMW_034, HMW_188, HMW_043, HMW_147, HMW_200, HMW_048, HMW_007, HMW_107, HMW_202, HMW_077, HMW_110, HMW_114, HMW_118, HMW_176, HMW_084, HMW_113, HMW_045, HMW_123, HMW_151, HMW_010, HMW_117, HMW_074, HMW_272, HMW_035, HMW_055, HMW_196, HMW_017, HMW_210, HMW_066, HMW_163, HMW_063, HMW_170, HMW_037, HMW_094, HMW_205, HMW_154, HMW_159, HMW_223, HMW_071, HMW_152, HMW_161, HMW_051, HMW_011, HMW_268, HMW_018, HMW_180, HMW_230, HMW_222, HMW_221, HMW_240, HMW_212, HMW_175, HMW_208, HMW_225, HMW_237, HMW_013, HMW_088, HMW_265, HMW_185, HMW_201, HMW_102, HMW_038, HMW_036, HMW_145, HMW_174, HMW_211, HMW_263, HMW_169, HMW_125, HMW_243, HMW_238, HMW_204, HMW_016, HMW_073, HMW_100, HMW_254, HMW_005


<!-- HMW_005 Jar Jar Binks, Bombad General — Done, 16/16. THE SET'S LAST CARD.
     Carries the engine's first "if you gave a token upgrade to a unit this phase" state
     (`SWU_GAVE_TOKEN_UPGRADE`, per-seat, cleared at RegroupPhaseStart).
     ⚠ There is NO chokepoint for giving a token upgrade — four token kinds (SOR_T01 Experience,
     SOR_T02 Shield, HMW_T02 Weakness, ASH_T02 Advantage) and four sibling Do* givers that each append
     their own subcard. All four are hooked via `_SWUNoteTokenUpgradeGiven`, and each has its OWN test
     section; mutation-verified one funnel at a time (unhooking any single giver reds only that
     funnel's sections). The offer wrappers (_SWUApplyTokenRider, CardHelpers' GiveTokenUpgrade,
     the GIVE_* universal handlers) route into these four, so they are covered transitively.
     ⚠ The condition is an EFFECT GATE, not a cost: an unmet condition is a SOFT PASS (leader still
     exhausts and pays) — NOT an unavailable action. Front_NoTokenGivenThisPhase_SOFTPASS vs
     Front_UnaffordableResource_COMPLETENoOp is the pair that pins it.
     ⚠ The deployed side's Shielded satisfies the condition for its own On Attack the turn he deploys.
     ⚠ TWO sections deliberately omit `P1OnlyActions` and assert `TURNPLAYER:2`. With that directive
     the action-close is UNOBSERVABLE — the first 14 sections were all green with the closer deleted.
     Both closers (the resolve chain and the soft-pass early return) mutation-verified independently. -->

<!-- HMW_011 Darth Sidious — Done, 12/12 including Twin Suns. Carries the engine's first
     "when you deal 4+ damage" observer, wired into all five damage funnels.
     ⚠ The "Twin Suns offer pool is truncated" scare was a FIXTURE FAULT, not an engine bug: seats 3/4
     get NO BASE unless WithP3Base/WithP4Base seeds one, so ZoneSearch('theirBase') correctly returned
     only p2Base-0 and the test then answered with a base that did not exist. Fan-out was fine. -->

<!-- HMW_016 Maul, Old Master — Done, 28 sections (front + deployed, each to its own floor).
     SEC_018 DJ is the direct sibling: same Action shape, same -1, same "(When Played abilities resolve
     after ...)" parenthetical, differing only in captures-vs-defeats. Epic deploy and Shielded need no
     code (deploy threshold IS the printed cost; Shielded is generator-derived from deployTextData).
     ★ RULING APPLIED (user, this session): a leader ability resolves BEFORE the abilities it sets off,
     so the synchronous defeat lands first and the played unit's triggers resolve afterwards. Proven by
     SEC_056 Escape Pod, whose "this unit captures a friendly unit" finds no captor and fizzles.
     ⚠ OPEN DIVERGENCE — the same ruling says a unit with BOTH a When Played and a When Defeated puts
     them on the stack together, resolvable in EITHER ORDER. The engine does not offer that choice: the
     When Played is flushed from the entry bag and the When Defeated is collected later at the defeat,
     so they land in separate batches and always go When-Played-first. MEASURED, not inferred — the
     boards pass identically with and without an EffectStack answer (i.e. it is silently absorbed).
     Observable on TWI_208 Favorable Delegate, where drawing first lets you discard the drawn card and
     discarding first cannot. Batching the two windows is shared trigger plumbing and would also change
     SEC_018, so it was left for a decision rather than changed here.
     ⚠ The uniqueness deferral copied from SEC_018 is NOT verified load-bearing on this card — DJ's
     second step is a CAPTURE (the copy becomes an unfindable subcard) whereas a DEFEAT compacts the
     arena predictably. Mutation could not find a board where removing it changes anything. Kept, but
     labelled in the card file so it is not copied onward as proven.
     ⚠ Deploying raises TWO entry triggers (Shielded + When Deployed), so every deployed-side section
     answers the EffectStack ordering prompt first.
     Interactions with TWI_208 / LAW_091 / LOF_207 / ASH_167 / SOR_134 (all dual-window units) are
     covered; note LOF_207/ASH_167/SOR_134 use the SLASH form, which is one ability firing in both
     windows rather than two separate clauses.
     Set state after this card: 117 of 117 mocked HMW CardIDs done. -->

<!-- HMW_204 Nightbrother, Maul's Gauntlet — Done, 12 sections. TWI_189 Unnatural Life is the
     near-exact precedent (discount + enters ready + SWU_SNEAK_DEFEAT at regroup); this one is
     optional, unrestricted, and -3. Offer via SWUOfferDiscountPlay over myDiscard, whose
     SWUPlayablesAtDiscount already prices candidates through SWUComputePlayCost minus the discount
     against SWUTotalPaymentCapacity — so the fizzle-only-optional and Credits-can-pay rules come free.
     ⚠ FOUND BY A GREEN MUTATION: 'discount' => 3 on the offer feeds ONLY the affordability filter,
     while the continuation charges whatever IT passes to ActivateCard. Changing one and not the other
     is silent (the first draft hardcoded 3 in both and mutating the offer's copy changed nothing). The
     number now rides the continuation's param, so the filter and the pay path cannot disagree — and
     mutating it reds four sections. Any card using a custom continuation with this helper has the
     same trap.
     ⚠ SWU_SNEAK_DEFEAT has NO $turnEffectRegistry row, which is what makes it permanent enough to
     reach the regroup (SWUExpireTurnEffects skips unregistered bases). Cost: no Active Effects
     provenance — a pre-existing gap shared with SOR_219/TWI_189/SHD_226.
     ⚠ TEST-DESIGN NOTE: after a regroup, RESAVAILABLE cannot prove a play happened (the phase readies
     every resource) and "the card is in the discard" is true whether or not it was ever played. Both
     regroup sections use an ATTACK (P2BASEDMG:7) as the receipt instead.
     Set state after this card: 116 of 117 mocked HMW CardIDs done; REMAINING = HMW_016 only. -->

<!-- HMW_238 Exploit Confidence — Done, 8 sections. "Return a non-leader unit with 6 or more power
     to its owner's hand" is SHD_078 Fell the Dragon's shape (nonLeader + ObjectCurrentPower >= N) with
     BOUNCE_UNIT instead of DEFEAT_UNIT; SOR_203's "4 or LESS power" mode is the mirror precedent.
     ⚠ THE nonLeader FILTER IS INVISIBLE TO BEHAVIOUR. SWUBounceUnit independently refuses any CardType
     containing 'Leader', so dropping the filter leaves a deployed leader OFFERED but immobile —
     mutation-confirmed to red the OFFER section only. Any future "return a unit" card needs its
     non-leader gate asserted in the pool, never in the outcome.
     Other guards: the 5-vs-6 boundary PAIR (5 fizzles, 6 returns), current-vs-printed power (a
     printed-4 unit wearing +2/+2 is legal), and owner-vs-control (a stolen unit goes to its OWNER's
     hand — mutating SWUBounceUnit's $owner to $obj->Controller reds exactly that section).
     Set state after this card: 115 of 117 mocked HMW CardIDs done; REMAINING = HMW_016, HMW_204. -->

<!-- HMW_243 Sun Fac, Poggle's Second — Done, 11 sections. "When Played: Give a unit Grit for this
     phase" is a word-for-word mirror of SEC_255 Remote Escort Tank (same unqualified target), so the
     card is a 3-line SWUOfferUnitTarget -> GRANT_PHASE_KEYWORD|GRIT^HMW_243; 'GRIT' was already a
     GRANT_KEYWORD registry row and HasKeyword_Grit already reads turn-effect grants.
     Coverage worth keeping: the per-damage SCALING pair (0 damage -> +0, 2 damage -> +2, so a flat
     "+1" reading reds), power-only (HP unchanged - the TS26 port once had Grit as +1/+1), the
     RECOMPUTE case (target gritted while undamaged, then damaged in-phase), and the expiry.
     Set state after this card: 114 of 117 mocked HMW CardIDs done; REMAINING = HMW_016,
     HMW_204, HMW_238 (derived from the CardMocks diff, which is the oracle - not the batches). -->

<!-- HMW_019 Dune Sea = blank-text base (52 of 92 released bases are likewise vanilla).
     HMW_T02 Weakness / HMW_T03 Beast = token CARDS; the engine handles tokens generically, so they
     get no per-card file. The ABILITIES that create them are HMW_059 / HMW_168 in Phase 4. -->

<!-- HMW_212 The Chieftain, Here Since the Oceans Dried — Done, 13 sections, 4 guards mutation-verified.
     "This unit gains Raid 1 for each other friendly Tusken unit. While a friendly Tusken unit is
     defending, it gets +1/+0 for each Raid it has." Two clauses that feed each other: the first sets
     HER Raid dynamically, the second turns any friendly Tusken's Raid into a DEFENDING bonus —
     including her own, so her value is read twice in different roles.
     Clause 1 → GetConditionalKeyword_Raid_Value (KeywordEffects.php), via _SWUCountFriendlyTraitUnits
     with the source excluded by UniqueID (she is herself a Tusken).
     Clause 2 → the $defendPower block in SWUCombatDamage, beside LOF_049 / SHD_042 / ASH_073 / ASH_018
     ("counter-damage only", never a stat write). Reads the DEFENDER's own Raid, gated on an
     ability-ACTIVE Chieftain and on the defender not being blanked (GetKeyword_Raid_Value does not
     honour suppression by itself).
     ⚠ FLAGGED INTERPRETATION — "for each Raid it has" is read as the Raid VALUE (Raid 2 → +2/+0), not
     one per Raid keyword instance. HMW is a preview set with no entry in card-specific-rulings.md, so
     this is reasoned rather than sourced: the value reading is what makes the clause meaningful (under
     the instance reading HMW_230 Raiding Party's Raid 6 would contribute +1). Pinned by
     Defending_ScalesWithARaidSixUnit, the only board where the two readings differ unmistakably.
     RE-CHECK WHEN HMW RELEASES and the rulings database refreshes.
     ⚠ NICE CROSS-CARD RESULT: removing clause 2's Chieftain-in-play gate reds THREE sections — its own
     guard plus TheWarrior_DeftDuelist::Deployed_RaidDoesNotApplyWhileDefending and
     RaidingParty::Raid6_DoesNotApplyWhileDefending. The "Raid is while-ATTACKING" negatives written for
     those two earlier cards independently catch a card that would otherwise make Raid apply on defence
     globally. -->

<!-- HMW_240 Sandstorm — Done, 14 sections, 5 guards mutation-verified.
     "While you control a Tatooine base, this event costs 1 less to play. Choose an arena, Give a
     Weakness token to each exhausted enemy unit in that arena."
     ⚠ THE COST DISCOUNT LIVES IN GameLogic.php's $playCostModifiers, NOT the card file — that array is
     initialized after cards/_loader.php, so a per-card registration is silently wiped and the discount
     never applies. Pinned by AFFORDABILITY rather than arithmetic: printed cost 3, on-aspect, with
     exactly 2 resources it is playable ONLY with the Tatooine base (and its partner section proves it
     is unplayable without).
     "Choose an arena" is a mandatory PARAMETER, so BOTH options are always offered and picking the
     empty arena is a legal play — the opposite of HMW_221 Teeka, where the labels are the EFFECTS and
     a fizzle-only mode is filtered off the menu. Worth keeping the two shapes distinct.
     Three independent restrictions on the AoE, each with its own section: EXHAUSTED (⚠ Status 0, the
     sense is easy to invert), ENEMY (their<Arena> only — Sandstorm is a Disaster but points outward),
     and IN THAT ARENA. ZoneSearch fans "their…" across every live opponent, so the Twin Suns loop is
     free — no hand-rolled seat enumeration.
     ⚠ ATTACH EVERY TOKEN, THEN SWEEP ONCE. Weakness is -1/-1 and its HP half is HP reduction, lethal
     only via SWUCheckShrinkDefeats. Sweeping per target compacts the arena mid-loop and strands every
     later mzID (the multi-unit debuff loop-shift family; SWUGiveSplitWeakness carries the same note
     for HMW_071 Ravage). PROVEN: moving the sweep inside the loop reds exactly the
     TwoOneHpUnits_BOTHDie section and nothing else. -->

<!-- HMW_221 Teeka, You're In Luck — Done, 14 sections, 5 guards mutation-verified.
     "When Played: Choose one: • Give a unit Sentinel for this phase. • A unit loses Sentinel for this
     phase." BOTH modes exist word-for-word as their own cards, and they carry DIFFERENT target rules —
     keep them asymmetric: SOR_086 Gladiator Star Destroyer grants to ANY unit with NO filtering, while
     SOR_140 SpecForce Soldier strips only from units that CURRENTLY have Sentinel (anything else is a
     zero-effect pick). "Choose one" is a mandatory branch → OPTIONCHOOSE, never a YESNO; a mode whose
     pool is empty is dropped from the LABEL LIST, and with one mode left it resolves with no menu.
     ⚠ THE TWO MODES MUST NOT SHARE A TOKEN. The strip tags the BARE CardID 'HMW_221' (the key
     SWUKeywordSuppressed looks up in $keywordSuppressors, registered in KeywordEffects.php beside
     SOR_140). So the grant CANNOT also be a $turnEffectRegistry row keyed 'HMW_221' — one token would
     mean both "gains Sentinel" and "loses Sentinel". The grant uses 'SENTINEL^HMW_221' instead: the
     synthetic base carries the keyword, the ^suffix carries Teeka's art for the Active Effects popup,
     and SWUKeywordSuppressed matches the RAW token so the two never collide.
     ⚠⚠ CONFIRMED ENGINE BUG, NOT FIXED HERE — "loses <keyword> for this phase" NEVER EXPIRES unless the
     suppressor CardID also has a $turnEffectRegistry row. SWUExpireTurnEffects bails on any token whose
     base is unregistered ("unregistered → untouched", GameLogic ~1317), so a bare-CardID suppressor with
     no row is PERMANENT. Teeka hit it and is fixed by her own row. SOR_140 has a row and is fine, but
     THREE existing suppressors do NOT: JTL_077 In the Heat of Battle (loses Saboteur, plus its
     'JTL_077_SENTINEL' grant), LOF_209 Tusken Tracker (loses Hidden), SEC_185 Screeching TIE Fighter
     (loses ALL keywords and can't gain any — the worst of the three). VERIFIED by live probe, not
     inferred: LOF_209's token still sits on the target after a full round boundary.
     Adding the four missing rows was measured and leaves the suite fully green (9532/0) — a ~4-line
     fix awaiting a go-ahead, deliberately left out of this card's scope. -->

<!-- HMW_222 Sandcrawler Sales Team — Done, 15 sections, 4 guards mutation-verified.
     Saboteur needs no code (generated registry). "When Played: If you control a Tatooine base, you may
     return an upgrade that costs 3 or less to its owner's hand."
     TWO OFFICIAL RULINGS settle the clause (Pre Vizsla - Power Hungry, card-specific-rulings.md), and
     both cut against the obvious implementation:
       • "Abilities that refer to a card's cost always refer to its PRINTED cost, regardless of
         modifiers" — a discount or an alternate Piloting cost never changes eligibility.
       • "TOKEN UPGRADES ARE CONSIDERED UPGRADES." A Shield/Experience token costs 0 and IS a legal
         target; returning one puts NO card in hand, because a token ceases on leaving play (CR 5.8).
         ⚠ LAW_224 Liberty explicitly SKIPS token upgrades on the same shape of clause ("return all
         upgrades ... that cost 4 or less"). That looks like a divergence from this ruling — worth a
         separate look, not touched here.
     Built on SWUGetUpgradeSubcardMzIDs('cost<=3') + SWUDefeatUpgradeByMzID(..., bounce: true). That
     collector is the right one for three reasons a hand-rolled scan loses: ZoneSearch's seat fan-out,
     the BASE scan (so a Fortify upgrade is reachable — HMW_205 Intelligence Agency), and no token
     filtering.
     ⚠ HARNESS BUG FIXED ALONGSIDE — Tests/Framework/GameStateBuilder.php: the runner accepted
     WithP3/P4GroundArenaUpgrade and filed the requests, but the resolve loop ran `foreach ([1, 2])`,
     so every FAR-SEAT upgrade request was silently DROPPED and the unit kept empty Subcards. A
     half-landed change: the runner even carried a "seats 3/4 supported" comment. Any four-seat
     assertion of the form "the far-seat upgrade is gone" was passing without the upgrade ever having
     existed — mine was, until a green-pre-implementation assertion gave it away. Loop widened to
     [1,2,3,4] (seats 3/4 have no deployed-leader splice, so they take the plain bracket-index path);
     no collateral, suite unchanged at 9511 before/after. The seed is now guarded by a SELECTABLEEXACT
     section, which cannot pass vacuously the way an "it's gone" assertion can. -->

<!-- HMW_230 Raiding Party — Done, 15 sections, 6 guards mutation-verified.
     Raid 6 needs no code (generated registry). "When Played: If you control another Tusken unit or a
     Tatooine base, you may exhaust a ground unit." — two OR'd gate limbs, each with its own negative;
     the sharp one is that Raiding Party is ITSELF a Tusken, so the source must be excluded by UniqueID
     (_SWUCountFriendlyTraitUnits) or the gate opens itself every time.
     Effect clause is word-for-word SHD_201 Principled Outlaw, so it reuses that shape: "a ground unit"
     spans BOTH sides, and only READY units are offered (exhausting an exhausted unit does nothing —
     the exhaust-only-ready convention, SHD_201 / SEC_069).
     ⚠ MEASURED, AGAINST MY OWN ASSUMPTION: ['myGroundArena','theirGroundArena'] is NOT a two-seat
     hardcode. I first hand-rolled a GetLiveSeatsArray()/SWUForeignMzID loop on the belief that it was,
     then ran the four-seat sections against BOTH shapes — identical pools, p2 and p3 present either
     way. ZoneSearch ITSELF fans "their<Zone>" across every live opponent at 3+ seats and returns
     seat-addressed p{n} mzIDs (Twin Suns Phase 3, GameLogic ~27685). The hand-rolled loop was also
     strictly worse: a raw arena walk skips AnyUnitFilter and ZoneSearch's leader-unit type mapping.
     Reverted to the idiomatic shape. SHD_201 and _SWUAllUnitsOnly / SWUAllUnits are all fine as-is —
     "shared code shape is not a shared bug", verified with a passing control rather than assumed.
     ⚠ At four seats there is no "their": even seat 2 comes back as p2GroundArena-0, which is the tell
     that the pool is genuinely seat-addressed rather than the two-seat pair plus extras. -->

<!-- HMW_180 Stormchaser — Done, 12 sections, 8 guards mutation-verified.
     "When Played: You may reveal a Disaster card from your hand. If you do OR if there's a Disaster
     card in your discard pile, draw a card." The clause is an **OR**, not the far commoner "If you
     do," rider — declining the reveal STILL draws when a Disaster sits in the discard, and satisfying
     both limbs still draws exactly one. USER-CONFIRMED reading 2026-08-24.
     ⚠ THE PROMPT IS ALWAYS OFFERED, even when the discard already guarantees the draw. Revealing is
     not strictly worse than declining: SEC_016 Padmé Amidala pays you for it ("When you reveal or
     discard 1 or more cards from your hand: ... deal 1 damage to a unit"), so auto-declining the
     "redundant" reveal would cost a real player real value. Pinned by RevealFiresThePadmeReaction.
     ⚠ NO CleanupRemovedCards() — deliberately, against the neighbouring house pattern. ASH_132 Queen
     Soruna and LOF_150 Cin Drallig both call it first, on the stated grounds that the just-played card
     lingers in the hand array and pushes every offered myHand-N off by one. MEASURED FALSE for a
     unit's When Played: the hand is already compacted by the time the entry-trigger flush dispatches
     it. Dumped the pending offer with and without the call, on BOTH dispatch paths (hand play, and
     played by HMW_018 The Warrior) — identical pools. The call was dropped rather than kept as a
     no-op carrying a false explanation, and the test section that was written believing the premise
     has been re-scoped and says so. ⚠ Worth re-checking whether ASH_132/LOF_150's calls are also
     no-ops before that comment gets copied onto another card.
     The reveal is now LOGGED ('P{n} revealed [[...]]', visibility ALL): DoRevealCard only sets a
     per-request client flash message, so a public reveal left no durable record for the opponent. -->

<!-- HMW_018 The Warrior, Deft Duelist — Done, 20 sections (front 11 / deployed 9), all six guards
     mutation-verified. FRONT: leader Action, modelled on SHD_016 Fennec Shand (same shape, POWER gate
     instead of a COST gate); the played unit gains Ambush via $gPlayGrantTurnEffect carrying the
     provenance token AMBUSH^HMW_018. DEPLOYED (Ambush + Raid 1): NO code — both keywords are generated
     registry entries and the Epic threshold is her printed cost 5, i.e. SWUDeployLeader's default.
     ⚠ ENGINE FIX SHIPPED WITH THIS CARD: an Ambush attack never exhausted its attacker. CR 6.3.1 step 3
     is "Begin attack: exhaust the attacker" and CR 5.9.e makes an Ambush attack take all the same
     steps; the engine's exhaust lives in BeginSWUAttack, which the Ambush path skips (it calls
     ExecuteSWUAttack directly with its own chosen target). Invisible on every prior Ambush card because
     a PLAYED unit enters exhausted (CR 5.9.c) — but a DEPLOYED LEADER enters READY, so The Warrior
     could attack on deploy and stay ready, i.e. a second attack off one deploy action. Fixed via
     _SWUBeginAmbushAttack() in GameLogic.php, wired into BOTH Ambush call sites (auto-fire at one legal
     target, and the multi-target MZCHOOSE path) and guarded on Status===1 so already-exhausted
     attackers are untouched.
     ⚠ ONE EXISTING TEST WAS CORRECTED (user-approved 2026-08-24):
     sor/GuerillaAttackPod.md::PlayedViaEnergyConversionLab_AmbushThenReadies answered EffectStack-0,
     which is the WHEN PLAYED (CollectEntryTriggers bags WhenPlayed first, Ambush second) — so it
     resolved the "ready this unit" BEFORE the Ambush attack, the opposite of the order the section is
     named for. It passed only because Ambush attacks were not exhausting at all. Answer changed to
     EffectStack-1; every assertion left untouched, and the READY end state it always claimed is now
     produced by the order it always described. Both orderings verified live via TestSchemaStep.
     USER RULING: Ambush does NOT ready the unit — modern reminder text is "it may attack an enemy
     unit" and CR 5.9.a lets it attack "even if this unit is exhausted", so the unit attacks WHILE
     exhausted and a separate ready effect is what can leave it ready afterwards.
     An audit of all 81 EffectStack-ordering answers in the suite found this was the ONLY comment
     asserting the wrong index; sor/ReinforcementWalker.md and sor/CountDooku_DarthTyranus.md both
     state "EffectStack-0 = the When Played" correctly. -->

<!-- HMW_268 Offworld Jawa — Step-0 VANILLA no-op (blank $textData, no keyword-registry membership,
     no ability stub). Cost 1, 2/1 Ground, NEUTRAL (no aspect pips), trait Jawa. No code, no tests.
     ⚠ Its Jawa trait is currently payload for NOTHING — no card in any set (released or previewed)
     references "Jawa" in its text. Re-check when a Jawa-matters card is previewed; it is then the
     cheap neutral fixture for that gate. -->

## Foundations already built (this session — do not re-do)

- **Fortify** — `HasKeyword_Fortify` + the `SWUGetUpgradeValidTargets` branch returning `['myBase-0']`
  + `Base.Subcards` + removal/uniqueness/observer coverage + the bottom-left count badge with its
  hover card-grid popup. 11 DSL cases in `Tests/Cases/keywords/Fortify.md`.
- **`SWUBaseIsUpgraded($player)` / `SWUBaseUpgradeCount($player)`** — what HMW_061 reads.
- **Base traits** — `CardTraitSupplement.php` backfills all 91 bases (the official API publishes none).
  HMW_142 (Kashyyyk), HMW_177 (Endor) and HMW_234 (Tatooine) depend on this.
- **Token plumbing that already generalizes:** `SWUCreateUnitToken($player, 'HMW_T03')` creates a token
  unit by CardID — Beast needs no new subsystem.

⚠ **Read subcards through `GetUpgradesOnUnit($obj)`, never `$sub->CardID`.** After a gamestate
round-trip `Subcards` decode as associative ARRAYS; direct property reads return null and
`(string)$sub` emits a warning that corrupts the response stream. This cost a live-game bug already.

## Phase 1 — Fortify cards + base-upgrade readers (autonomous)

- [x] **Batch 1.1 — HMW_061, HMW_095** — done, 9 cases, suite 5995/0.
  - HMW_061 Director Krennic: On Attack — if your base is upgraded, draw a card. One-liner over
    `SWUBaseIsUpgraded()`; guarded against reading "any base" instead of "your base".
  - HMW_095 Carbonite Chamber: Fortify; `Action [defeat this upgrade]:` choose a non-Vehicle unit — it
    doesn't ready during the next regroup phase.
  - **New seam: base-hosted activated Actions.** `$baseUpgradeAbilities[$upgradeCardID] =
    fn($player, $upgradeIndex)` + `_SWUBaseActionProviders()` / `_SWUDispatchBaseAction()`;
    `SWUBaseAction` is now a dispatcher over that list. Clicking the base is ONE undifferentiated input,
    so when a base has both its own Epic and a Fortify-upgrade Action the player gets an OPTIONCHOOSE
    (`BASE_ACTION_PICK`) rather than one silently shadowing the other — the deliberate difference from
    the single-provider unit model. Labels are single-token and re-derived, so no state rides the
    decision. Fixed en route: clicking a **vanilla** base used to set `EpicActionUsed` on a base that
    has no Epic at all; the provider list only offers Actions that exist.
  - **New marker: `SWU_SKIP_REGROUP_READY_{uid}`** (`SWUSkipNextRegroupReady($mz)`), read+consumed only
    by the regroup ready loop. Deliberately NOT SOR_186's `SWU_CANT_READY_`, which also blocks explicit
    mid-phase "ready a unit" effects — a scope guard test pins the difference. Batch 2.1's HMW_121
    reuses this.
- [x] **Batch 1.2 — HMW_081** — done, 4 cases, suite 5999/0. **HMW_060 DEFERRED (see below).**
  - HMW_081 Alliance Shield Generator: Fortify; if the attached base would be dealt 5+ damage, prevent
    it, then defeat this upgrade and draw. Sits in `SWUDealDamageToBase` beside JTL_074 / ASH_070 and
    reuses their hoisted `$baseDmgUnpreventable`, so indirect / ASH_196 damage lands in full — and,
    being conditional rather than a one-shot shield, a sub-threshold hit neither triggers nor consumes
    it. Ordered BEFORE the ASH_070 cap: capping 6 → 4 first would silently disarm the generator, and
    full prevention plus a draw is what that player would choose anyway (both effects are theirs).
  - New helper `SWUFindUpgradeIndex($obj, $cardID)` → the `$upgradeIndex` `SWUDefeatUpgrade` expects.

- [x] **HMW_060 Vice Admiral Rampart** — DONE (ruling settled; see the dedicated section below). "If an
  upgrade on your base would be defeated, you may defeat this unit instead" is an interactive REPLACEMENT in
  `SWUDefeatUpgrade`. Final ruling: replaceable for **any** ability/cost/effect defeat of a base upgrade
  (HMW_081, HMW_095, HMW_171). ⚠ An earlier draft here claimed a cost/effect distinction citing a made-up
  "CR 3.13"; that was wrong — the SWU CR states a replacement effect CAN replace a cost (the cost still
  counts as paid) and the "If you do" payoff still resolves. Uniqueness enforcement only hosts on arena
  units, so it never reaches the base branch.

## Phase 2 — Entry triggers & "doesn't ready" (autonomous)

- [x] **Batch 2.1 — HMW_121, HMW_171** — done, 8 cases (2 + 6), suite 6010/0.
  - HMW_121 Hijacked AT-ST: Overwhelm (auto-wired keyword) + When Played — this unit doesn't ready
    during the next regroup phase. `$whenPlayedAbilities["HMW_121:0"]` self-targets HMW_095's
    `SWUSkipNextRegroupReady` (one-shot; NOT SOR_186's `SWU_CANT_READY_`).
  - HMW_171 Trap Field: Fortify; when a non-leader ground unit enters play (**including token units**)
    you may defeat this upgrade and deal 3 to that unit. **First base-hosted REACTIVE entry observer.**
    New seam `SWUCollectTrapFieldReactions($mzID)` (GameLogic.php) loops BOTH bases, arms one
    `AddTrigger($baseOwner,'HMW_171',...,uid,count)` per base with Trap Field; hooked at the played-unit
    funnel (`CollectEntryTriggers`, batches with the existing flush) AND the token funnel
    (`_SWUCreateOneToken`, explicit flush since token creation has none). Reaction owned by the base
    owner → cross-player when the enemy's base reacts (drains like SHD_172; the test needs an extra
    `AnswerDecision` to drain `RESOLVE_NEXT_TRIGGER` before the YESNO). Entered unit carried by UID
    (frame-independent). `DispatchTrigger` case → `Hmw171TrapFieldReaction` → `HMW_171#0` continuation
    (`SWUDefeatUpgrade('myBase-0')` + `SWUDealDamageToUnit(...,3)`; loops for the rare 2-Trap-Field base).
    HMW_171 has NO generated stub — "When a non-leader ground unit enters play" isn't matched by the
    WhenPlayed detection ("When Played:"/"When Deployed:" only), so the observer is wired by hand and
    stub-independent.
- [x] **Batch 2.2 — HMW_085, HMW_127** — done, 5 cases (2 + 3), suite 6015/0.
  - HMW_085 Remote Scout: When Played — `DoTopDeckSearch($player, 8, fn upgrade, 1)` (mirror SOR_125).
    Note: a no-match search still PRESENTS the TOPDECKSEARCH decision (the player looks at the top 8);
    choosing none (empty `AnswerDecision:`) draws nothing and bottoms all peeked cards — it does NOT
    auto-skip.
  - HMW_127 Chewbacca's Bowcaster: `$whenPlayedAbilities["HMW_127:0"]` gets the HOST mzID (non-pilot
    upgrade WhenPlayed fallback); if `CardTitle(host) === 'Chewbacca'` → `SWURampResourceExhausted(
    'myDeck-0')`. Attach restriction = HMW_127 added to the non-Vehicle attach group in
    `SWUGetUpgradeValidTargets`. The mock typo "Attach **of**" was corrected to "Attach to" in
    CardMocks.php (cosmetic only — attach is per-card case, not text-matched; no regen done).

## Phase 3 — Conditional keywords & base-trait conditions (autonomous)

- [x] **Batch 3.1 — HMW_142, HMW_234, HMW_257** — done, 8 cases, suite 6023/0.
  - Shared helper `_SWUControlsBaseWithTrait($player, $trait)` (GameLogic.php) — `HasTrait` resolves base
    traits (CardTraitSupplement backfill), verified against JTL_030 Mos Eisley (Tatooine).
  - HMW_142 Wookie Rangers: `HasConditionalKeyword_Sentinel` case — another Wookiee unit (`TraitContains`,
    self-excluded by UID) OR a Kashyyyk base. ⚠ **No Kashyyyk base is previewed in any set**, so that
    branch is currently unexercisable; it reuses the base-trait helper covered by HMW_234/HMW_177.
  - HMW_234 Ritual Dragon: Saboteur (auto-wired) + `_SWURitualDragonEntersReady` hooked in BOTH entry
    paths (ActivateCard unit-entry + `_SWUCreateOneToken` token path). "Including this one" = the helper's
    `$cardID === 'HMW_234'` self-clause (it isn't in play yet at entry-status time).
  - HMW_257 Ewok Archers: `HasConditionalKeyword_Ambush` case — another unit costing ≤3 (self-excluded;
    tokens cost 0 so qualify).
- [x] **Batch 3.2 — HMW_177, HMW_255** — done, 8 cases (5 + 3), suite 6031/0.
  - HMW_177 Adamant Ewoks: gate = another Ewok (`TraitContains`, self-excluded) OR Endor base
    (`_SWUControlsBaseWithTrait`). The base `SWUQueueMayChooseTarget` (`myBase-0`/`theirBase-0`) IS the
    "may" entry (decline = neither); `HMW_177#0` deals 1 to the chosen base then `SWUQueueChooseTarget`s
    the enemy-unit half (fizzles cleanly with no enemy unit).
  - HMW_255 C-3PO: two independent `SWUQueueMayChooseTarget` queued up front (`HMW_255#0`/`#1`), so
    declining/empty-first still offers the second. Any Ewok / any Rebel (no friendly qualifier).
    `SWUApplyPhaseBuff(...,'HMW_255')` (registered STAT_BUFF) stacks per-application; no Ewok is also a
    Rebel in the pool, so same-unit +4/+4 isn't exercisable. Phase-expiry verified.

## Phase 4 — Tokens (autonomous)

- [x] **Batch 4.1 — token-upgrade give path + HMW_059** — done, 3 cases, suite 6037/0.
  - New generic `DoGiveTokenUpgrade($player, $targetMZ, $tokenCardID)` (GameLogic.php) — the arbitrary-token
    generalisation the hardcoded Shield/Exp/Advantage givers lacked; token Owner/Controller follow the
    HOST's controller (so a Weakness on an enemy unit is an enemy upgrade). New `GIVE_WEAKNESS` continuation
    attaches HMW_T02 then runs `SWUCheckShrinkDefeats` (the -1 HP has no SBA of its own).
  - HMW_059 Clone X Assassin: `$whenDefeatedAbilities["HMW_059:0"]` → `GiveTokenUpgrade(token:'WEAKNESS',
    friendlyOnly:false, may:true)`. -1/-1 flows through the upgrade stat loop; enemy-attachable + lethal
    shrink both verified.
- [x] **Batch 4.2 — HMW_168** — done, 3 cases, suite 6037/0.
  - HMW_168 Ezra Bridger: the "when you take the initiative" offer is armed in `SWUTakeInitiative` (beside
    ASH_155/SEC_168). `HMW_168#0` deals 3 to your OWN base then gates the Beast (`SWUCreateUnitToken('HMW_T03')`)
    on the base damage actually rising (skipped when prevented — Close the Shield Gate verified). ⚠ Test note:
    Claim ends the round → seed decks or the empty-deck regroup penalty (+6 to each base) masks base assertions.

## Phase 5 — Leaders (pair-programmed)

- [x] **Batch 5.1 — HMW_009 Chewbacca** — done, 5 cases, suite 5986/0.
  - Front `Action [2 resources, Exhaust]:` attack with a unit **even if it's exhausted**; it can't
    attack bases for that attack. Deployed side: the same attack, **once each round**, with no
    resource cost.
  - Both design forks turned out to be already-built seams (SEC_103 Mon Mothma): `BeginSWUAttack` has
    no ready requirement at all, so "even if it's exhausted" is just a matter of not filtering the
    attacker pool by `Status`, and `$noBases = true` is its third parameter. Shared
    `_SWUHmw009Attackers` + one `HMW_009#0` continuation serve both sides; they differ only in cost.
  - The once-each-round budget is the leader unit's **NumUses** (refreshed by `SWUResetAllNumUses` at
    RegroupPhaseStart), not a bespoke `SWU_*_USED` flag.
  - Asymmetric target-gating, deliberate: the deployed Action has NO cost, so it is gated in
    `SWUUnitActionAffordable` on a legal attacker existing (otherwise activating would burn the round's
    use on nothing); the front side's `[2 resources, Exhaust]` cost changes game state, so it stays
    available and fizzles (matching TWI_009/TWI_012).
- [x] **Batch 5.2 — HMW_004 Grand Moff Tarkin** — done, 8 cases (3 pre-existing deploy + 5 new).
  - Both sides: "Ignore the aspect penalties on upgrades with Fortify you play" — one line at the
    `SWUAspectPenalty` chokepoint (the SOR_008 Hera / TWI_001 Nala Se shape), which covers every play
    path and the affordability glow at once. Scoped to `$Fortify_Cards`; a plain upgrade still pays.
  - ⚠ Test-design trap: Tarkin himself provides **Vigilance + Villainy**, and `PlayerAspects` counts a
    leader's aspects whether or not it is deployed — so a Vigilance Fortify upgrade (HMW_095) is
    on-aspect under him and proves nothing. The waiver test uses **HMW_171** (Aggression + Heroism,
    both uncovered, 2 + 4 = 6 → attaches on 2 resources only if waived).
  - Deployed side: "When the regroup phase starts: you may defeat a base with 10 or less remaining HP."
    **Resolved, not blocked** — defeating a base is not a distinct board state: a base with damage >= its
    HP IS defeated and its owner immediately loses (SWU CR, base section), so `SWUDefeatBase` fills the
    damage in and lets the existing `SWUCheckBaseDefeatState` sweep declare the outcome (which already
    handles Twin Suns seat elimination too). "A base" carries no friendly/enemy qualifier, so your own
    base is a legal — if suicidal — target, and the "10 or less remaining HP" wording is the multi-base
    filter that matters in Twin Suns.

## Phase 6 — Base-granted abilities (pair-programmed)

- [x] **Batch 6.1 — HMW_206 The Tarkin Doctrine** — done, 4 cases, suite 6041/0. **NOT blocked after all.**
  - The grant clause needed NO general "base-hosted granted abilities" framework — it's a targeted
    extension of the own-play-upgrade reaction path. `_SWUFinalizeUpgradeAttach` (the Fortify-play path)
    already calls `CollectWhenPlayedAsUpgradeTriggers`, so a one-line `AddTrigger('HMW_206')` there —
    gated on `HasTrait($cardID,'Fortification') && _SWUBaseHasUpgrade($player,'HMW_206')` — arms the
    "exhaust an enemy unit" reaction (DispatchTrigger `HMW_206` case). **Self-trigger ruling resolved by
    data:** HMW_206's own trait is `Law`, not `Fortification`, so playing The Tarkin Doctrine itself never
    triggers its own grant (guarded by a test).
  - When Played half: `$whenPlayedAbilities["HMW_206:0"]` — gate on `_SWUControlsTitle(['Grand Moff
    Tarkin'])`, then `APPLY_PHASE_DEBUFF|3|0|HMW_206` on an enemy unit (registered STAT_DEBUFF).

## HMW_060 Vice Admiral Rampart — DONE (ruling settled by the user 2026-07-30)

- [x] **HMW_060 Vice Admiral Rampart** — done, 3 cases, suite 6044/0. Interactive REPLACEMENT in
  `SWUDefeatUpgrade` (the 12-call-site chokepoint), deferred to action end via `$gDeferredReplacements`
  (`kind:'rampart_save'`) → `SWUFlushDeferredReplacements` → `RAMPART_SAVE` continuation (JTL_094 timing;
  HMW CR unreleased, user gave the ruling). **Ruling:** replaceable for ability/effect defeats (HMW_081);
  NOT for a COST-defeat (HMW_095 Action + HMW_171 self-sacrifice both now pass `$skipReplacement=true`).
  Uniqueness enforcement only hosts on arena units so it never reaches the base branch (no change needed).
  The subcard is stamped a UID at defer time so the flush re-finds it (`_SWUBaseUpgradeIndexByUID`). The
  cross-player deferred YESNO (defender reacts to the attacker's action) drains without an extra step.

## Phase 7 — the second preview wave (autonomous)

⚠ Read this before trusting any earlier "all done" claim: **every batch above was `[x]` and the Status
below read "ALL 33 … are implemented" while 13 further cards sat unimplemented.** The batches only ever
covered the cards previewed when they were written, and a preview set GROWS. The oracle is the diff
(`### Already Done` vs `grep -oE "'HMW_[0-9T]+'" AppCore/SWU/CardMocks.php`), never the checkboxes.

- [x] **Batch 7.1 — HMW_020/021/023/024/026/027/028/029/030/031/033/034** — 12 blank-text 30-HP bases
  (Naboo / Kashyyyk / Endor / Tatooine), verify-only no-ops per the Step-0 vanilla triage. Their TRAITS
  are the payload, and they unblocked a branch that had been unexercisable since Phase 3:
  - **HMW_142 Wookie Rangers' "or a Kashyyyk base" Sentinel branch** — 3 new cases (suite 7042→7045):
    the positive via HMW_021 Kashirho, the controller-scoping negative (the OPPONENT holding Kashirho
    grants nothing), and a trait-scoping negative (HMW_020 Great Grass Plains, the same vanilla shell
    with the NABOO trait, grants nothing). Paired against the pre-existing ordinary-base negative on an
    identical board, so the base is provably the only differentiator. No code change — the branch was
    already correct, just unreachable; the stale "unexercisable" comments in `KeywordEffects.php` and
    the test file were corrected.
  - HMW_177 (Endor) and HMW_234 (Tatooine) already had base-trait coverage via JTL_020 / JTL_030.
- [x] **Batch 7.2 — HMW_188 Giant Gorax** — 17 cases, suite 7045→7062/0. **Plus one ENGINE bug.**
  - Overwhelm is free from the keyword registry. `$onAttackAbilities` and `$whenDefeatedAbilities` share
    ONE closure gated on `_SWUControlsBaseWithTrait($player,'Endor')`, evaluated against the RESOLVER.
  - The closure only queues an intermediate `CUSTOM` (`HMW_188#0`) — DispatchTrigger/OnAttackTrigger
    restore `$playerID`, so the cross-player OPTIONCHOOSE and every relative-mzID pick must be queued
    from a continuation (the LAW_080 shape). That is also what makes the Deal3 pick safe as a MANDATORY
    MZCHOOSE inside an On Attack. The caster rides the CUSTOM's own Param, so the chain survives the
    request boundary the opponent's decision creates (guarded by a `SimulateRequestBoundary` section).
  - Option B reuses `SWUDiscardCards` + SOR_017 Han Solo's bare-`myResources` MZCHOOSE →
    `HAN_DEFEAT_RESOURCE`. The two halves are joined by AND, not "if you do": empty hand still defeats a
    resource, and no resources still discards — both covered.
  - **★ ENGINE BUG — combat damage committed ahead of a still-pending pre-damage decision.** The
    `SWU_TRIGGER_RESUME` COMBAT branch hops onto the non-active player's queue when THEY owe a blocking
    decision (the On Defense pause). But a cross-player chain can bounce the decision BACK: the opponent
    picks the mode, then the CASTER picks the damage target. Once hopped, the resume could no longer see
    the active player's pending pick, so it committed combat — and the target then resolved against a
    board combat had already changed (Gorax's 3 damage landed on a unit combat had just defeated, and
    Overwhelm spilled the wrong number). Fixed with a symmetric hop-back, guarded on
    `$player !== $activePlayer` so a resume sitting behind the active player's own block can't re-queue
    itself forever. It uses a new **`_SWUPlayerHasPendingWork()`** rather than
    `_SWUPlayerHasBlockingDecision()` — an auto-resolving `PASSPARAMETER` (what the caster's pick becomes
    when the opponent controls only a base) is not "blocking" but is still pre-damage work.
  - ⚠ Harness note: the opponent's answer drains only THEIR queue, so a section that ends on a
    cross-player answer needs a trailing `P1>Drain` (the stand-in for production's post-action drain) or
    the caster's auto-resolving pick never runs.

## Status

**NOT card-complete — a THIRTEENTH preview wave landed (113 → 121 HMW CardIDs, 2026-08-31).**
Being worked one at a time via `swusim-implement-set-plan HMW --iterative`.

⚠ **THE ORACLE IS THE `### Already Done` LINE DIFFED AGAINST `CardMocks.php` — and mind how you match
that heading.** A regex anchored on the literal text `### Already Done` matches the PROSE MENTION of it
higher in this file first, captures only as far as the real heading, and reports ~1 card done and 120
missing. Match the heading as a whole line. A second oracle — a quoted-CardID grep under `Custom/` —
also mis-reports: registrations are keyed `"HMW_263:0"`, so an exact `'HMW_263'` match finds nothing and
a finished card looks unimplemented. Use both, and reconcile them before believing either.

| card | state |
|---|---|
| **HMW_073 Peppi Bow, Shaak Herder** | **DONE 2026-08-31** — 8 sections, suite 10199 → 10207 / 0 |
| **HMW_100 Torrent** | **DONE 2026-08-31** — 8 sections, suite 10207 → 10215 / 0 |
| **HMW_254 Captain Tarpals** | **DONE 2026-08-31** — Step-0 no-op, no code and no card test (see below) |
| **HMW_005 Jar Jar Binks, Bombad General** | **DONE — 16/16, the set's last card.** New engine state `SWU_GAVE_TOKEN_UPGRADE`, per-seat, cleared at RegroupPhaseStart, stamped by `_SWUNoteTokenUpgradeGiven` from ALL FOUR token-upgrade givers (there is no chokepoint). Condition is an EFFECT gate, so an unmet one soft-passes with the cost paid. Deployed side is a "you may" whose decline abandons both halves; its own Shielded satisfies the condition on the deploy turn. Two sections drop `P1OnlyActions` to make the action-close observable. |

<!-- HMW_254 Captain Tarpals, Grand Army Captain — DONE 2026-08-31, Step-0 verify-only NO-OP.
     Text is keyword-only (Shielded + Raid 2, nothing else); $Shielded_Cards contains it and
     $Raid_Cards gives it the value 2; both keywords already have generic tests under
     Tests/Cases/keywords/ (Shielded_EnterWithShield, Raid_AttackBoost, +4 more). So: no code, and no
     card-specific test — one would be GREEN on its first RED-check, which the scope rule says to drop.
     Behaviour was still WATCHED once via a throwaway probe rather than inferred from the registries:
     0/2 at rest, enters with a Shield, attacks for 2. The 0-PRINTED-POWER + Raid 2 statline is the
     unusual part and is why it was worth looking at. -->

<!-- HMW_100 Torrent — DONE 2026-08-31. Built on HMW_071 Ravage (same set, same HMW_T02 token, same
     _SWUAllUnitsOnly pool) + HMW_240 Sandstorm's `_SWUControlsBaseWithTrait` for the base condition +
     HMW_110 Palpatine's back-to-back DoGiveTokenUpgrade for "give 2". 8 sections; 4 mutations each
     reddening only their own: additive-instead-of-replacement, seat-agnostic base check, enemy-only
     pool, and the dropped shrink sweep.
     ⚠ The Naboo/non-Naboo fixture pair uses two bases of the SAME aspect (HMW_020 Great Grass Plains
     and HMW_019 Dune Sea, both Vigilance) so the base TRAIT is the only variable — a different-aspect
     negative would also move the event's cost. -->

<!-- HMW_073 Peppi Bow, Shaak Herder — DONE 2026-08-31. "While this unit is upgraded, she gets +1/+1"
     is SHD_056 Follower of The Way's sentence word for word, so it joined that card's existing pair of
     lines in ObjectCurrentPower/ObjectCurrentHP rather than getting its own handler. Restore 1 was
     already auto-wired from $Restore_Cards. 8 sections; 3 mutations each reddening only their own
     sections (power site → 4 sections, HP site → the same 4, the !$lost blank gate → 1).
     ⚠ SHD_056 itself had ONE section and no COVERAGE ledger — the twin's coverage was not a model. -->

<!-- HMW_125 The Marauder, A New Home — DONE 2026-08-27. 12 sections, suite 9923→9935/0;
     5 guards mutation-verified (chosen-vs-damaged count, friendly-vs-controlled pool, the offered-max
     clamp, the affordability branch, SWUQueueMultiChoose's dontSkipOnPass), each reddening only its own
     section.
     "While playing this unit, you may choose any number of friendly units. Deal 1 damage to each of
      them. For each unit chosen this way, this unit costs 1 resource less."
     ★ EXPLOIT'S SHAPE with three deliberate differences, each its own section:
       "up to N"                  → "ANY NUMBER" — the cap is the friendly POOL, not a printed X and not
                                    the cost; over-choosing is legal and the cost floors at 0.
       defeat the chosen units    → deal them 1 damage each (survivable, sometimes lethal).
       "for each unit DEFEATED"   → "for each unit CHOSEN this way" — a pick whose damage a SHIELD
                                    prevents STILL buys its resource. This is why the resolver counts
                                    picks up front instead of copying EXPLOIT_RESOLVE's
                                    count-successful-defeats loop; mutating it to count damage reds
                                    ShieldedPick_DamagePrevented_ButStillCountsForTheDiscount.
     Plumbing = HMW_048 Vernestra Rwoh's, which is Exploit's: _SWUBeginPlayCardUnitPath owns the offer,
     HMW_125#0 resolves it, then SWUContinuePlayAfterExploit charges the reduced cost through the one
     funnel every unit play uses. ⚠ SAME SCOPE AS EXPLOIT — a direct-ActivateCard nested play (Sneak
     Attack, play-from-deck) skips it, the documented engine-family gap, not a per-card choice.
     ⚠ AFFORDABILITY: CanAffordActivationReserve gained an HMW_125 branch (cost − friendly count) beside
     the Exploit one, or the card sits DARK BUT CLICKABLE exactly when the reduction is what makes it
     payable. Guarded as a PAIR with P1HANDGLOW / P1HANDGLOWNOT (5 resources + 2 friendly = glows;
     5 + 1 friendly = does not).
     ⚠ THE PICKER MUST GO THROUGH SWUQueueMultiChoose. Its continuation is what PLAYS THE CARD, and a
     0-minimum multi-select confirmed with nothing selected submits the sticky literal "PASS" — without
     dontSkipOnPass the CUSTOM is skipped and the card VANISHES from the game. Replacing the helper with
     a raw AddDecision pair reds ChooseNone_EmptyConfirm_FullPrice, which is a byte-for-byte twin of the
     `-` decline section precisely so the two declines cannot silently diverge.
     ⚠⚠ THE OFFERED MAX IS CARRIED IN THE CUSTOM PARAM AND RE-CLAMPED IN THE RESOLVER. Found by mutation:
     capping the picker at 2 left all 12 sections GREEN, because the harness (like a non-conforming
     client) feeds an answer straight to the handler without consulting the decision's {max} — so
     OverChoose_CostFloorsAtZero was testing the resolver and NOT the offer. This is the documented
     "a test that answers a number needs a resolver that bounds it" remedy. -->

<!-- HMW_169 Crosshair, I've Changed — DONE 2026-08-27. 15 sections, suite 9908→9923/0;
     6 guards mutation-verified (OpponentsOf-vs-OtherPlayer, each-player loop, opponent-vs-any-player,
     which base takes the 2, clause 2's LostAbilities gate, the action-phase gate, clause 1's survives
     gate), each reddening only its own section(s).
     "When this unit is dealt damage and survives: Each player draws a card.
      When an opponent draws 1 or more cards during the action phase: Deal 2 damage to their base."
     TWO clauses that FEED EACH OTHER — clause 1 makes the opponent draw, which is what clause 2
     punishes. Wired into two different shared hooks, so each has its own negative AND its own
     LostAbilities test (the clause-1 blank test leaves clause 2's gate completely unexercised —
     mutation N5 was green until LostAllAbilities_ClauseTwoAlsoStopsPunishing was added).
     Clause 1 = the same _SWUOnUnitDamaged self observer as HMW_211, below the $survived gate, queued as
     a CUSTOM (what it does — draw for every seat — can raise decisions on other seats' queues, which
     belongs after the combat cleanup). "EACH PLAYER" = SWUSeatsInPlayerOrder, caster included, and NOT
     team-scoped (a teammate draws too).
     Clause 2 = a field observer on _SWUOnPlayerDrew, which already carries the action-phase gate. Per
     draw EVENT, not per card (a 2-card draw is ONE trigger — pinned with ASH_185 Intimidation);
     "an OPPONENT" so the controller's own draw is free; OpponentsOf() excludes a TEAMMATE, so a
     partner's draw is free in Team Suns; "their base" is DETERMINED, so this card raises no prompt at all.
     ⚠⚠ THE TWIN SUNS FIXTURE PUTS CROSSHAIR ON SEAT 3, AND THAT IS LOAD-BEARING. OtherPlayer(n) answers
     1 for every seat but seat 1, so with Crosshair on SEAT 1 the legacy two-seat shape gives the CORRECT
     answer for every drawing seat and the section passes under the very bug it exists to catch — measured,
     not theorised (mutation N1 reddened only the Team Suns section until the seat was moved). This is the
     documented "pick fixture seats so the LEGACY answer != the CORRECT answer" rule. -->

<!-- ★ ENGINE BUG found while building HMW_169 — FIXED 2026-08-27, suite 9939→9940/0.
     JTL_111 Seasoned Fleet Admiral ("When an opponent draws 1 or more cards during the action phase…")
     sits in the SAME _SWUOnPlayerDrew hook and read `$reactor = OtherPlayer($drawingPlayer)` — the
     two-seat hardcode. At four seats it considered only ONE of the drawing player's opponents, so an
     Admiral on seat 3 or 4 never reacted at all. Now loops OpponentsOf(), which is also team-aware.
     Guard: jtl/SeasonedFleetAdmiral.md::TwinSuns_AdmiralOnAFarSeatStillReacts — ⚠ the Admiral sits on
     SEAT 3 deliberately, because OtherPlayer(n) answers 1 for every seat but seat 1 and a seat-1
     Admiral gets the CORRECT answer out of the broken code. -->

<!-- ★ THIRD ENGINE BUG, surfaced by the CLI runner's warning output during that fix — FIXED
     2026-08-27, suite 9940→9941/0. `_SWUOnUnitDamaged` (CardDQHandlers) had NO `global $playerID;`
     anywhere in its body, so its SHD_084 Phase-III Dark Trooper frame pin
     (`$shd084Saved = $playerID; $playerID = $shd084Ctrl;`) touched a LOCAL and left the global alone.
     `GetMzID()` reads the GLOBAL, so the Trooper's mzID was minted in whatever frame was ambient, and
     `DoGiveExperienceToken($ctrl, $mz)` then re-resolved that RELATIVE string under the Trooper's own
     frame. When the Trooper DEFENDED, "their…-0" flipped sides and its Experience token was handed to
     the unit that had just hit it.
     ⚠ CORRECTION to the first diagnosis: this is a TWO-PLAYER bug, not a 3+ seat one. Above two seats
     GetMzID takes its ABSOLUTE `p{n}…` branch for a foreign frame, which survives the re-resolve — so
     the damage was to ordinary Premier games. The seat-count reasoning is what found it; the fixture is
     2P. (The documented "a seat-count sweep can find TWO-PLAYER bugs" shape, again.)
     ⚠ The pre-existing section could not see it: there the Trooper ATTACKS, so the ambient frame already
     IS its controller's and the broken pin is harmless. Only a DEFENDING Trooper puts a foreign frame on
     the global at trigger time. Guard:
     shd/PhaseiiiDarkTrooper.md::DarkTrooper_DEFENDS_ExperienceGoesToTheTrooper_NotTheAttacker, which
     asserts BOTH sides (the Trooper gains the token AND the attacker does not).
     The `PHP Warning: Undefined variable $playerID` on every CLI suite run is gone with it. -->

<!-- HMW_263 Wrecker, Wrecking the Empire — DONE 2026-08-27. 13 sections, suite 9895→9908/0;
     5 guards mutation-verified (every-live-seat loop, own-units-only pool, the literal 3, caster
     included, the simultaneous-defeat window), each reddening only its own section(s).
     "When Played: Each player chooses a unit they control. Deal 3 damage to each chosen unit."
     ★ NO new plumbing — this is LAW_099 Governor's Shuttle's chain ("Each player chooses a unit they
     control. Defeat those units.") with damage swapped for the defeat; LOF_177 Time of Crisis is the
     same sentence INVERTED ("...each unit NOT chosen"). Grepping the printed SENTENCE found both.
     Chain = one queued pick per LIVE seat in SWUSeatsInPlayerOrder (caster first); the caster, the UIDs
     chosen so far and the seats still to ask all ride the CUSTOM's Param, because the chain spans one
     REQUEST per answering seat in production.
     "A unit they CONTROL" → SWUAllUnits('my') in that seat's own frame: a stolen unit is in the THIEF's
     pool, never the owner's. NO "non-leader" restriction (contrast TWI_238 Merciless Contest, one
     sentence away, which prints it) so a deployed leader unit is legal; no "another" either, and Wrecker
     is already in play when its own When Played resolves, so it can choose ITSELF.
     ⚠ Damage is dealt at ONE point after every seat has picked — that is what makes the choices
     simultaneous — and each unit is re-resolved BY UID, since the first defeat compacts its arena.
     ⚠ SWUSimulDefeatBegin/End wraps the loop: "deal N to each" from one ability is SIMULTANEOUS (official
     Rancor Keeper ruling 07/21/2026), so two chosen units that die do so in ONE batch and an observer
     that is itself a victim must still see its co-victim. Pinned by
     SimultaneousDefeat_ObserverThatDiesInTheSameBatchStillFires — and the ORDER is load-bearing there:
     the observer must be the CASTER's pick (asked first) so it is already dead when the defeat it must
     observe happens. Removing the window reds exactly that section.
     ⚠ HARNESS: each non-acting seat needs its own `P{n}>Drain` before it answers. -->

<!-- ★ ENGINE BUG found while building HMW_263 — FIXED 2026-08-27, suite 9935→9939/0.
     `_SWUApplySplitHits` (GameLogic, the DIVIDED-damage applier behind MZSPLITASSIGN → SPLIT_DAMAGE)
     never called `_SWUOnUnitDamaged`, so every "when this unit is dealt damage" observer was BLIND to
     divided damage: SEC_143 The Elite Squad, HMW_211 Tech, SEC_002 Jabba, SHD_250 Tarfful, ASH_032
     Rancor Keeper and the ASH_188 damaged-this-phase marker. The INDIRECT path had been given its own
     explicit call for exactly this reason, with a comment saying so; the split path never was. The
     JTL_177 Stay on Target shape: one trigger, several damage funnels, one funnel missed.
     Fix: record the POST-PREVENTION amount actually dealt per hit ($landed), then fire the observers
     AFTER the defeat sweep so $survived is accurate — a fully shielded share triggers nothing, an
     "and survives" observer stays silent for a unit the same effect just killed, and one WITHOUT that
     clause (SEC_143) still fires. Guard: Tests/Cases/core/SplitDamageFiresOnUnitDamagedObservers.md,
     4 sections; reverting the flush reds 3, and firing inside the damage loop (survived always true)
     reds the 4th — so the placement is pinned, not just the call. -->

<!-- HMW_211 Tech, I Thought It Was Obvious — DONE 2026-08-27. 14 sections, suite 9881→9895/0;
     6 guards mutation-verified (survives gate, ready-only filter, queued-vs-inline offer, controller-
     vs-owner, both-sides pool, LostAbilities), each reddening only its own section(s).
     "When this unit is dealt damage and survives: You may exhaust a unit."
     Self observer on _SWUOnUnitDamaged, hooked BELOW its $survived gate (contrast HMW_045 Logray and
     HMW_013 Cham, which have no survives clause and sit above it). Handler in
     cards/hmw/Tech_IThoughtItWasObvious.php.
     ★ THE FIRST NEW "when this unit is dealt damage and survives" CARD SINCE THE HOOK EXISTED — and it
     needed NO new infrastructure: _SWUOnUnitDamaged($obj,$amount,$isCombat,$survived) is already called
     from all THREE damage funnels (combat via _SWUCollectOnUnitDamagedReactions, ability via
     SWUDealDamageToUnit, indirect from its own site because indirect writes ->Damage directly). All
     three have their own section.
     Text says "dealt damage", NOT "dealt combat damage" — so non-combat counts. Settled by the OFFICIAL
     ruling on the identical wording (Jabba the Hutt, Wonderful Human Being, 10/31/2025), not inferred;
     contrast SHD_250 Tarfful, which prints "COMBAT damage" and passes $isCombat.
     Offer is QUEUED as an intermediate CUSTOM (the SEC_143 shape) so its pool is built POST-cleanup —
     the unit that damaged Tech usually dies to Tech's counter in that same combat, and an inline pool
     would carry stale positional mzIDs. Mutating it to inline reds 3 sections.
     Ready-only target pool (SEC_015 / SHD_201 / SEC_069 convention — exhausting an exhausted unit is a
     no-op) and no offer at all when nothing is ready (no fizzle-only optional).
     ⚠ NO "once each round" clause, unlike ASH_032 Rancor Keeper and SEC_002 Jabba — every qualifying
     damage instance gets its own offer (TwoDamageInstances_TriggersEachTime pins it).
     ⚠ HARNESS: the offer lands on the non-acting controller's queue, so every cross-player section needs
     an explicit `P1>Drain`. Production is fine — ProcessGoldfishAutomation drains EVERY live seat after
     each action, and its own comment documents this exact case. -->

**(historical, 2026-08-27 — card-complete at 109 of 109, before the twelfth wave.)** Verified by the diff (`### Already Done` vs
`grep -oE "'HMW_[0-9T]+'" AppCore/SWU/CardMocks.php`): **empty in both directions**, and cross-checked
against the 109 HMW entries in `SWUSim/GeneratedCode/GeneratedCardDictionaries.php` (also an empty
diff). Suite **9881 passed / 0 failed**. The 19 Done cards with no reference under `SWUSim/Custom/`
are all legitimately handler-free: 13 blank-text bases (HMW_019/020/021/023/024/026/027/028/029/030/
031/033/034), 4 blank-text units (HMW_116 Ewok Brigade, HMW_163 Champion of Endor, HMW_174 Maul,
HMW_268 Offworld Jawa) and 2 keyword-only units (HMW_175 Fennec Shand = Raid 2, HMW_201 Sandtrooper
Squad = Ambush + Raid 1) — all served by the generic keyword engine.

⚠ **Data-quality flag, not a code gap:** HMW_174 Maul (Only Revenge Remains) is mocked as a *vanilla*
4-cost 6/6 Rare with `text: ''`. That stat line for a blank Rare is unusual; if the real preview has
printed text, the MOCK is incomplete and the card needs re-importing, not re-implementing. Same
question, lower stakes, for HMW_116 / HMW_163 / HMW_268.

⚠ True only until the next preview wave lands. Re-derive with the diff — never a Status line, never
the batch checkboxes.

**(historical, 2026-08-26 — the NINTH preview wave.)** `CardMocks.php` now holds **103** HMW CardIDs, and
the `### Already Done` diff (run 2026-08-26) named THREE unimplemented cards, ALL NOW DONE:
**HMW_088 Numa, HMW_265 Twi'lek Kalikori, HMW_185 Ty Yorrick.**

**The tenth wave is CLOSED too — all five done 2026-08-26 — and so is an ELEVENTH (HMW_174, vanilla).**
⚠ `CardMocks.php` was rewritten THREE separate times during the 2026-08-26 session (103 → 108 → 109
HMW CardIDs). Re-derive the diff at the END of a run as well as the start; a list derived once is stale
by the time it is finished.

⚠⚠ **A TENTH WAVE LANDED WHILE THAT WORK WAS IN FLIGHT.** `CardMocks.php` was rewritten mid-session
(103 → **108** HMW CardIDs) and the re-run diff names FIVE more unimplemented cards:
**HMW_036 Kelnacca (Solitary Master), HMW_038 Bestial Bond, HMW_102 Dragon's Might,
HMW_145 Origin Tree Shyyyo, HMW_201 Sandtrooper Squad.** All five are already in the generated
dictionaries. **ALL FIVE ARE DONE** (HMW_201 verify-only; HMW_102, HMW_038, HMW_036, HMW_145 implemented). HMW is **NOT card-complete.**
The reusable lesson: on a preview set, re-derive the diff at the END of a run as well as the start —
the card pool can grow *during* the session, so a list derived once is stale by the time it is finished. The batch checkboxes below are ALL ticked
and were ticked while these three did not exist — which is exactly why the checkboxes are not the
oracle. Re-run the diff before making any completeness claim.

**(historical, 2026-08-25 — card-complete at the EIGHTH wave, 95 of 95.)** `CardMocks.php` then held
**95** HMW CardIDs (93 numbered + 2 tokens) and the `### Already Done` diff was EMPTY. The 2026-08-24/25
wave closed the last eight: HMW_268 (vanilla no-op), HMW_018 The Warrior, HMW_180 Stormchaser,
HMW_230 Raiding Party, HMW_222 Sandcrawler Sales Team, HMW_221 Teeka, HMW_240 Sandstorm,
HMW_212 The Chieftain.
⚠ This number is true only until the next preview wave lands — **re-derive it with the diff**
(`### Already Done` vs `grep -oE "'HMW_[0-9T]+'" AppCore/SWU/CardMocks.php`), never by reading a Status
line or counting batches. Every "card-complete" claim below this line is a snapshot of an EARLIER,
SMALLER wave and is retained only as history.

**(historical, 2026-08-14 — the FOURTH wave)** `CardMocks.php` then held **56** HMW
CardIDs. Batches 9.1-9.4 closed ALL FIVE (HMW_107, HMW_202, HMW_077, HMW_110, HMW_114); the diff is empty as of 2026-08-14. Everything below
this line describes the state as of the THIRD wave and is retained as history — re-derive the real
number with the diff, never by reading a Status line or counting batches.

**(historical, 2026-08-12)** All 46 then-mocked HMW cards were implemented (verified by diffing the
`### Already Done` line against the HMW entries in `AppCore/SWU/CardMocks.php`, not by counting batches).
The "no base-hosted granted abilities" (HMW_206) and "no base-defeat primitive" (HMW_004) blockers both
turned out to be non-blockers; HMW_060 landed once the user settled the replacement-timing ruling.
**This number is only true until the next preview wave lands — re-run the diff, don't re-read this line.**

**HMW_003 Doctor Hemlock (2026-08-12)** — 15 sections, suite 7004→7019/0. Leader, both sides:
front `Action [1 resource, Exhaust]` gives a Weakness token to a unit *without* one (the exclusion is a
TARGET FILTER, asserted on the offer via `P1SELECTABLEEXACT`); deployed `On Attack` may give one with
**no** exclusion, so it can stack a second -1/-1 — that asymmetry is printed and is why the two sides
cannot share a filter. The Epic Action needed **zero code**: the generic deploy threshold already equals
the leader's printed cost (6), pinned by a 6-vs-5 boundary pair. Reused `GIVE_WEAKNESS` +
`DoGiveTokenUpgrade` from Phase 4 — no new infrastructure.

**HMW_062 / HMW_064 / HMW_070 (2026-08-12)** — 23 sections, suite 7019→7042/0. A regen was required
first: none of the three had generated code yet, so `HMW_062`/`HMW_064` had no trigger stub (handlers
would have silently never fired) and `HMW_070` was absent from `$Fortify_Cards` (it could not attach to
a base at all). The regen was drift-checked against a pre-copy — only the 3 new cards plus index
renumbering, zero change to existing card data — and the suite was re-verified green BEFORE any card
code was written.
- **HMW_064 Scorch** — On Attack, may deal 1 to an upgraded unit. `_SWUIsUpgraded` counts token
  upgrades, so a Shield-only unit qualifies (and its shield then absorbs the 1 — covered).
- **HMW_070 Dark Sanctum** — Fortify (free from the keyword registry) + a base-granted regroup trigger
  hooked in `RegroupPhaseStart` beside HMW_004's. Fires once PER ATTACHED COPY (non-unique), and its
  self-damage can defeat your own base — covered by a 28/27 boundary pair.
- **HMW_062 Nuvo Vindi** — When Played, plus "when an enemy unit WITH A WEAKNESS TOKEN is defeated".
  That condition cannot be read at reaction time (subcards are stripped by then), so a `'weakened'`
  key was added beside the existing `'upgraded'` capture at all **6** defeat-entry sites — the same
  shape SHD_137 relies on. Observer + once-each-round gate live in `SWUCollectLeavePlayReactions`;
  the flag is consumed at collect time so declining still spends the round, and it is cleared in
  `RegroupPhaseStart`. Both the combat AND effect (`SWUDefeatUnit`) defeat paths are covered.


## Phase 8 — third preview wave (autonomous)

- [x] **Batch 8.1 — HMW_043 Darth Vader, Any Methods Necessary** — 12 sections, suite 7066 → 7078, 0 failed.
  Saboteur was free (already in `$Saboteur_Cards`, generic keyword coverage exists); the When Played half
  is new. **2 gate bugs found and fixed during the build**, both in this card's own filter — see below.

### ⚠ Set status after Batch 8.1
`### Already Done` now covers **47 of 47** HMW CardIDs in `CardMocks.php`. Re-derive with the diff, never
the checkboxes: `grep -oE "'HMW_[0-9T]+'" AppCore/SWU/CardMocks.php` vs the Already Done line, then a
plain-substring `grep -rn "<CardID>" SWUSim/Custom/` for anything it surfaces (registration keys use
DOUBLE quotes — `$leaderAbilities["HMW_003"]` — so a `'HMW_003'`-quoted grep reports false gaps).

### ✅ Fixed during the build — the search filter was FRONTEND-ONLY
`_topDeckSearchBegin($player, $n, $filter, …)` uses `$filter` only to build the `matchIDs` hint sent to
the client. The finalize resolves the answer via `_topDeckResolveFromIDs($allIDs, …)` — against **every
peeked card**, not the matching ones. So any pick that reaches the handler is honoured. Before the fix,
answering HMW_043's search with a cost-5 unit played it, and answering with a cost-3 **event** placed the
event into the ground arena as a unit. `_SWUHmw043IsLegalPick()` is now THE gate, used to build the offer
AND re-checked per pick in the handler; illegal picks join the cards going to the bottom.
✅ **CLOSED (same session) — and it was set-wide, not 4 cards.** ALL **20** `_topDeckSearchBegin` call
sites were filter-advisory, not just the `SOR_087#0` family. Fixed centrally instead of per card:
`_topDeckSearchBegin` now stores the match list (`TopDeckLegalIDs`, `~`-sentinelled) and the constraint
(`TopDeckConstraint`), and `_topDeckResolveFromIDs` — which all 20 finalize handlers share — re-applies
both. One store + one check fixes every caller with no signature churn. Illegal/overflow picks fall
through to `remaining`, the disposition callers already give an unpicked card (bottom of deck).
⚠ **The `~` sentinel is load-bearing**: an EMPTY match list means "a filter is in force and nothing
matched", not "unrestricted". My first cut treated empty as unrestricted, which is the exact inversion —
and it is the common case (a search whose top N holds no legal card). It was caught only because
`IllegalPick_NonUnitEvent_IsRefused` seeds a deck of pure events.
⚠ **A second, independent hole in the same place:** the CONSTRAINT (`count:N` / `cost:N` / `cost:N:M`) was
client-only too. SOR_087's "combined cost 3 or less" played two cost-2 units for a combined 4. Now
enforced in pick order, dropping the overflow. Guarded by
`sor/DarthVader_CommandingTheFirstLegion.md::SearchFilter_CombinedCostBudgetIsENFORCED` (mutation-verified)
and its partner `SearchFilter_NonVillainyPickIsREFUSED` for the filter — two mechanisms, two sections,
because fixing either alone leaves the other open.

### ⚠ DEFERRED (NOT a bug fix) — "play them for free" is a PUT INTO PLAY across this whole family
`SOR_087#0` places fetched units with a bare `AddGroundArena`/`AddSpaceArena`, so the fetched unit's own
**When Played does not fire** and no entry ceremony runs (no `FlushEntryTriggerBag`, no Shielded, no
Ambush, no uniqueness sweep). Every card in the family is printed "play them for free", and by the rules
playing a unit from the deck IS playing it — so those abilities should fire. HMW_043 deliberately matches
its five siblings rather than being the single card that behaves differently.
Correcting it is a six-card change (SOR_087, LAW_063, ASH Ackbar, SOR_104, HMW_043 + the shared handler)
and it re-introduces an ORDERING hazard this card currently dodges: once a play can queue decisions
(When Played / Shielded / Ambush / uniqueness), HMW_043's inline "deal 2 damage to each" would run BEFORE
them and re-index the arena underneath a pending offer — the SEC_018 family. The handler's comment says
so, so whoever does that pass is warned at the call site.

### Load-bearing checks (mutation-verified)
Each gate was removed in turn and the expected section failed, nothing else:
| mutation | section that caught it |
|---|---|
| drop the server-side filter re-check | both `IllegalPick_*` |
| cost cap 4 → 5 | `IllegalPick_UnitCostingFIVE_IsRefused` |
| search depth 8 → 9 | `Top8Depth_NinthCardIsNotReachable` |
| damage arena slots instead of the recorded UIDs | 6 sections incl. `TwoDamage_HitsONLYTheUnitsPlayedThisWay` |

### ✅ LOF_100 Kelleran Beq converted to a REAL play (2026-08-13, user-directed)
Audit of the nine search-and-play cards found Kelleran Beq was the odd one out inside its own wording
family. SHD_194 Triple Dark Raid and LAW_074 Maz Kanata are printed identically — "search … and play it.
It costs N less" — and both route through the real play path (`ActivateCard` / `SWUPlayTopDeckCard`).
LOF_100 used a bare `AddGroundArena`, so the fetched unit's **When Played never fired**, and no entry
ceremony ran. It now uses the LAW_074 idiom (chosen card to the top of the deck, then
`SWUPlayTopDeckCard(..., false, 3)`), which also gives it affordability through the real cost pipeline.
Two guards added, both mutation-verified:
- `FetchedUnitFiresItsOwnWhenPlayed` — SHD_080 Salacious Crumb's mandatory "heal 1 from your base" fires
  (base 5 → 4). Under the old placement it stayed at 5.
- `FetchedPILOTingUnitIsOfferedTheUnitVsPilotChoice` — the **card-vs-unit** distinction. Kelleran searches
  for "a UNIT" and a Piloting card IS a unit card, so it is a legal find; but once you are PLAYING it, it
  may be played as a unit or as a Pilot upgrade. `SWUPlayTopDeckCard` detects Piloting and routes to
  `SWUBeginPlayCard`; the old path could only slam it into the arena as a unit.
  ⚠ Resources are load-bearing in that fixture: at 7 (all spent on Kelleran) the 1-resource PILOT cost is
  unaffordable, so the engine correctly drops the pilot option and the play auto-resolves with NO prompt.
  10 is what makes the choice reachable — an under-resourced fixture passes for the wrong reason.
  Mutation: flipping `SWUPlayTopDeckCard`'s `$ignoreCost` to true skips the Piloting routing and fails
  exactly this section.

### ✅ ASH_090 Reforge — Pilot-exclusion guard added (the mirror case)
Reforge searches for "an UPGRADE", so the very same Piloting card must NOT be found: that filter is a
card-TYPE test, and a Piloting card's type is `Unit`. Already correct in code; it now has
`SearchExcludesPILOTUnitCards` (JTL_215 BoShek over a SEC_214 Vehicle host — a host BoShek can genuinely
attach to, so only the type gate excludes him).
⚠ Note the existing `SearchExcludesUnitCards` (plain unit SOR_051) also fails if the type gate is deleted
outright — `SWUGetUpgradeValidTargets` falls back to "all friendly units" for an unknown CardID. The Pilot
section earns its place against a filter that is WRONG rather than absent: swap the type gate for a
can-this-attach test and SOR_051 is still excluded while BoShek sails through.

### Audit result for the whole search-and-play family (9 cards)
All nine route through `_topDeckSearchBegin`, so all inherit the server-side filter + constraint
enforcement. Placement splits three ways: **free → put-into-play** (SOR_087, SOR_104, SHD_123, LAW_063,
ASH_110 — consistent, and the When-Played deferral below still applies to them); **discounted → real
play** (SHD_194, LAW_074, and now LOF_100); **upgrade attach** (ASH_090, via
`_SWUFinalizeUpgradeAttach`). Numbers were re-checked against the dictionary text and all match.

## Phase 9 — fourth preview wave (autonomous; HMW_048 PARKED by user direction)

- [x] **Batch 9.1 — HMW_147 Beast Lair + HMW_200 Rish Loo** — 11 sections, suite 7120 → 7131, 0 failed.
  Card data was regenerated first (`zzCardCodeGenerator.php rootName=SWUSim` → `cardArrayCache.json`,
  then `Data/ProcessKeywordsSWU.php` for the keyword registries — ⚠ `zzGameCodeGenerator.php` alone does
  NOT refresh `$Fortify_Cards`/`$Hidden_Cards`).
  - **HMW_147 Beast Lair** (6): Fortify half free; the granted half is a base-hosted ACTION-phase-start
    trigger (`_SWUHmw147ActionPhaseTriggers` hooked in ActionPhaseStart — the phase-mirror of HMW_070's
    regroup hook). Per-copy, mandatory discard with player card-choice, "if you do" Beast (HMW_T03).
    ⚠ Harness: crossing a full round needs `P1>Pass / [P2>Pass] / P1>ResourcePass / P2>ResourcePass /
    P{n}>Drain` — the resource prompt appears even with an EMPTY hand (zone-form offer). Mutation:
    unhooking the trigger fails 4 sections.
  - **HMW_200 Rish Loo** (5): Hidden free; mandatory steal of a weakened enemy non-leader
    (`SWUQueueChooseTarget`, single auto-resolves), give-back at regroup start via the JTL_235-shaped
    PERM per-UID global (`SWU_HMW200_RETURN_<uid>`) — returning CONTROL, not the card (block lives
    beside Commandeer's in RegroupPhaseStart). Offer section excludes non-weakened / weakened-friendly /
    weakened-deployed-leader in one SELECTABLEEXACT. Note: the explicit `IsLeaderUnit` check is masked
    by `NonLeaderUnitFilter` (defense-in-depth, not load-bearing — the offer test pins the behaviour).

### ✅ HMW_048 Vernestra Rwoh — DONE (2026-08-13). Set status: **50 of 50** — HMW is card-complete.
11 sections, suite 7131 → 7143/0 (with the LOF_197 Ambush hardening). No new framework was needed:
- **Additional cost** = Exploit's play-path shape (offer in `_SWUBeginPlayCardUnitPath`, resolve in
  `HMW_048#0`, continue via `SWUContinuePlayAfterExploit`). Both queue entries are `dontSkipOnPass` —
  cost + orchestration must survive a sticky PASS. The consume-once play-grant globals
  (`gForceEnterReady`/`gPlayGrantTurnEffect`/`gPlayGrantShield`) are SNAPSHOTTED INTO THE PARAM and
  restored in the handler — the caller nulls them long before the queued cost resolves.
- **Gains** ride an SWUVar (`SWU_HMW048_GAINS`) from the cost step to `CollectEntryTriggers`, which
  stamps `SWU_HMW048_GAIN_<CID>` on her (phase sweep = the "for this phase" expiry) and bags one
  `HMW048Gain` trigger per donor with a registered `$whenPlayedAbilities` closure. Dispatch reuses
  `OnWhenPlayed` with HER mzID — so "this unit" = her, multiple gains order via the normal prompt, and
  a gained ability counts as "using a When Played ability" for LOF_197's repeat.
- **Rulings applied**: Shielded/Ambush are NOT When Played abilities (donor keyword-only = gains
  nothing; hardened generically on LOF_197 with `NoRepeat_AmbushKeyword` beside the existing Shielded
  section); bottom order RANDOM (`_topDeckPutRemainingToBottom` shuffles); gains resolve via the bag.
- Mutations: cost cap 5→6 fails exactly the offer section; collection hook off fails 4.

### ⚠ OPEN ENGINE-FAMILY FOLLOW-UP — additional costs are SKIPPED on direct-ActivateCard nested plays
A play dispatched straight through `ActivateCard` (SOR_219 Sneak Attack, play-from-deck effects) never
passes `_SWUBeginPlayCardUnitPath`, so it skips **Exploit** — and now Vernestra's additional cost, which
deliberately matches that scope rather than hacking one card past it. ⚠ Severity note (user,
2026-08-13): because the cost is "UP TO 2", zero is a legal payment — so the nested-path skip is an
implicitly FORCED zero, not an illegal play. The gap loses the player an OPTION (and Vernestra her
gains); it does not break the play's legality. Same logic applies to Exploit ("up to X"). Lower
severity than first framed, still worth the one-seam fix. An in-drain fix needs care: a queued cost prompt gets eaten by the outer answer's
sticky drain (probed: block-2 + dontSkipOnPass was not sufficient; the mid-drain MZMULTICHOOSE consumed
the stale `myHand-0`). Fix the family at ONE seam; the section that must FLIP when fixed is
`VernestraRwoh::NestedDirectPlay_SkipsTheAdditionalCost_LikeExploit` (its comment says so).

## Phase 9 — fourth preview wave (autonomous)

Wave landed 2026-08-14: HMW_077, HMW_107, HMW_110, HMW_114, HMW_202 (mock art + `CardMocks.php`
entries; the `zzCardCodeGenerator` + `ProcessKeywordsSWU` regen had already been run, so all five were
in the dictionary and keyword registries before implementation started). Suite baseline 7785/0.

- [x] **Batch 9.1 — HMW_107 Stormtrooper Patrol + HMW_202 Inferno Squad, We Can Grieve Later** —
  21 sections, suite 7785 → 7806, 0 failed. No new infrastructure; no engine bugs found.
  - **HMW_107** — Sentinel was FREE (already auto-registered in `$Sentinel_Cards` by the keyword
    generator). The real work is the rider, "While you control another unit that costs 3 or more, this
    unit gets +2/+0" — a continuous self-passive in `ObjectCurrentPower` beside the TWI_163/TWI_130
    family, power only, gated on `!$lost`. Three printed details each got their own guard: cost is the
    PRINTED cost (a 0-cost token ally never qualifies), "another" excludes self by **UniqueID** so two
    copies each buff the other, and "you control" means an enemy 3+ cost unit grants nothing. A
    DEPLOYED LEADER does qualify — `GetUnitsInPlay` reads the arenas directly, so it is included; a
    printed-CardType `'Unit'` filter would have wrongly excluded it (leader units are CardType
    `'Leader'`). Covered the aura ENDING (ally trades in combat → back to 2 power), which is the cell a
    permanent-buff bug would sail past.
  - **HMW_202** — When Played **and** When Defeated on one shared closure so the two windows cannot
    drift. "a unit" is unqualified → friendly, enemy and ITSELF are all legal (no "another"), so
    `side: 'any'` with no `excludeSelf`; asserted via `P1SELECTABLEEXACT` over all three units. The two
    halves are joined by "and", not "if you do", so neither gates the other — but they compound: 1
    damage leaving a target at 1 remaining HP is then finished by the Weakness's -1 HP via
    `SWUCheckShrinkDefeats`. ⚠ The damage can DEFEAT the target, and that runs `CleanupRemovedCards`
    and re-indexes the arena — so the host is re-resolved by **UniqueID** after the damage, or the
    token strands on whichever bystander shifted into the vacated slot (that is its own section).
    When Defeated is covered on BOTH the combat and the effect-defeat path, plus a control-change
    section (owner P1 / controller P2) proving the CONTROLLER resolves it while the card still goes to
    the OWNER's discard.
  - ⚠ Harness note reconfirmed: that control-change section needs a **`P2>Drain` before P2's answer**.
    P1's action leaves P2 holding an undispatched `CUSTOM RESOLVE_TRIGGER|WhenDefeated|HMW_202`; without
    the drain the answer lands on that entry and CANCELS the trigger, presenting exactly like "the When
    Defeated never fired". Diagnosed with `TestSchemaStep`, not guessed.

- [x] **Batch 9.2 — HMW_077 Boss Nass, Otoh Gunga Boss** — 11 sections, suite 7806 -> 7817, 0 failed. — When Played/On Attack, "you may defeat a
  Shield token on a friendly Gungan unit. If you do, create a Beast token and give a Shield token to
  it." Optional COST (defeat a shield) + an "if you do" payoff; Beast = HMW_T03, created by HMW_168's
  existing path.
- [x] **Batch 9.3 — HMW_110 Emperor Palpatine, Consolidating Power** — 9 sections, suite 7817 -> 7826, 0 failed. — When Played take-control of an
  enemy non-leader unit costing 3 or less, then 2 Weakness tokens. ⚠ leader-unit exclusion must read
  the LIVE object (`IsLeaderUnit`), not printed CardType.
- [x] **Batch 9.4 — HMW_114 Breach** — 12 sections, suite 7826 -> 7838, 0 failed. — a friendly unit deals damage equal to its power to an enemy unit
  in ITS arena; if that unit has Overwhelm, excess goes to an enemy base.

<!-- HMW_175 Fennec Shand, A Ship For a Life — Step-0 NO-OP, verified 2026-08-26. Text is exclusively
     "Raid 2" + its reminder text, $Raid_Cards['HMW_175'] => 2 is auto-derived correctly by the
     generator, and keywords/Raid_AttackBoost.md covers the mechanic. No code, no per-card test. -->

<!-- HMW_208 Luke Skywalker, Dreaming Farmboy — Done, 8 sections, both guards mutation-verified.
     "Raid 1. / While it's the first round of the game, this unit enters play ready."
     Raid 1 is auto-derived ($Raid_Cards). The rider is TWO edits and BOTH are load-bearing (removing
     either alone reds 4 of the 8 sections — measured, not assumed):
       1. a per-card branch in _SWUCardEntersReadyFor: `return intval(GetTurnNumber()) === 1`
          (CreateGame seeds TurnNumber = 1; RegroupPhaseStart increments at the END of a round, so the
          whole of round 1 reads 1);
       2. membership in the conditional allowlist at the ActivateCard call site.
     ⚠ THE CARD WAS ACTIVELY WRONG, not merely unimplemented, the moment it entered the dictionary:
     SWUUnitEntersReady() is a bare substring match for "this unit enters play ready", and that phrase
     sits INSIDE Luke's own conditional clause — so he entered ready in every round. All four of the
     existing conditional enters-ready cards (SEC_170 / LAW_210 / LAW_223 / ASH_224) share the shape,
     which is why the allowlist exists.
     ⚠ FLAGGED FOR REVIEW — the allowlist's DEFAULT is best-case ready, so the NEXT conditional
     enters-ready card is silently wrong until someone remembers to list it. That is the
     allowlist-whose-default-contradicts-the-rules shape. Not refactored here (it would change behaviour
     for 4 shipped cards mid-preview-run); raised with the user instead.
     ⚠ PREVIEW SET — no card-specific-rulings.md entry, so "the first round of the game" == TurnNumber 1
     is reasoned from the CR + the four released analogues, not sourced. RE-CHECK WHEN HMW RELEASES.
     HARNESS: added the `WithRound: N` GIVEN directive — GameStateBuilder::WithCurrentRoundBeing() had
     existed all along with nothing wired to it, so no test could sit outside round 1 without driving a
     full regroup. One section deliberately drives the REAL regroup anyway, so the gate is proven against
     the counter the game actually increments rather than against the new directive. -->

<!-- HMW_225 Boba Fett, Family Found — Done, 10 sections, all four guards mutation-verified.
     "Ambush / When a friendly unit with Ambush enters play (including this one): Give it Raid 1 and
     Saboteur for this phase."
     Ambush is auto-derived ($Ambush_Cards). The observer is modelled on ASH_041 Outcast, which carries
     the identical "(including this one)" wording, and like it needs BOTH entry funnels:
     CollectEntryTriggers (the play path) and _SWUCreateOneToken (creation never reaches the play path).
     Placed just after the Ambush trigger is BAGGED but before the bag is FLUSHED, so the entering unit
     carries both grants into its own Ambush attack — pinned by Raid1AppliesToHisOwnAmbushAttack and
     SaboteurLetsHisAmbushAttackIgnoreSentinel.
     Grants use generic registered bases with a provenance suffix — 'RAID-1^HMW_225' / 'SABOTEUR^HMW_225'
     (SWUMakeTurnEffect's $source arg). The base is what makes them EXPIRE; the suffix is what shows
     Boba's art in Active Effects. No new $turnEffectRegistry rows. Boba is unique, so the grant fires
     once rather than once-per-copy (two identical tokens would de-dupe anyway).
     ⚠ MUTATION CAUGHT A WEAK SECTION OF MINE: Raid1AppliesToHisOwnAmbushAttack originally ambushed a
     3/1, which dies to 1 damage with or without Raid — it bore the name and proved nothing. Retargeted
     at SEC_237 (2/2), where only 1 power + Raid 1 is lethal, and it now reds when the Raid grant is
     removed. Worth remembering whenever a section's premise is "+N made the difference": pick a target
     whose HP sits strictly between the two readings.
     ⚠ KNOWN UNTESTED PATH — the _SWUCreateOneToken hook. No fixture in the pool creates a token that
     HAS Ambush (it would need an Ambush-granting aura over a token-creating effect, e.g. SOR_100 Wedge
     + a Vehicle token), so the token half is implemented by symmetry with ASH_041 and is NOT covered by
     a section. Flagged rather than omitted; revisit when such a fixture exists.
     ⚠ PREVIEW SET — no card-specific-rulings.md entry; readings reasoned from the CR + ASH_041.
     ⚠ HARNESS NOTE: Ambush prompts as a YESNO ("it MAY attack"), and with ONE enemy unit the target
     auto-resolves — answering with a target mzID there is silently absorbed and no attack happens,
     which reads exactly like the trigger never firing. Two enemy units => YES, then the target. -->

<!-- HMW_237 Easy Prey — Done, 10 sections, four guards mutation-verified.
     "Create a Beast token. / An opponent creates a Beast token. Give a Weakness token to it."
     Two creations with DIFFERENT riders — yours is a clean 3/3 Beast (HMW_T03), theirs arrives carrying
     a Weakness (HMW_T02, -1/-1) so it reads 2/2. "it" = the Beast named by the immediately preceding
     sentence, i.e. the OPPONENT's; that asymmetry IS the card.
     "An opponent" is a CHOICE -> SWUQueueChooseOpponent (auto-resolves invisibly at two seats, so
     Premier gains no one-answer prompt). NO eligibility filter: this is the "something is done TO them"
     shape, and a free 2/2 may even be welcome — filtering on whether a seat wants it is the mistake that
     classification exists to prevent.
     ⚠ THE RIDER MUST RIDE THE BATCH API. The Weakness is passed as SWUCreateUnitTokens' $upgradeToken,
     not stamped on the returned UID: ASH_094 Moff Jerjerrod creates the doubled tokens later inside his
     own handler, so a stamped rider leaves the second Beast bare. Pinned by
     Jerjerrod_DoublesAndBOTHBeastsAreWeakened plus its decline partner.
     ⚠ ENGINE FIX THIS CARD REQUIRED: _SWUApplyTokenRider knew only 'EXPERIENCE' and 'SHIELD' and
     SILENTLY DID NOTHING for a token-upgrade CardID — so $upgradeToken='HMW_T02' looked wired and
     produced a bare token. It now falls through to DoGiveTokenUpgrade. Any future token-upgrade rider
     depends on that fallback; mutation D (reverting it) reds 7 of the 10 sections.
     ⚠ PREVIEW SET — no card-specific-rulings.md entry; both the "it" reading and "an opponent" = choice
     are reasoned from the CR and flagged. RE-CHECK WHEN HMW RELEASES. -->

<!-- HMW_013 Cham Syndulla, Hammer of Ryloth — Done, 16 sections, four guards mutation-verified.
     FRONT : "When non-combat damage is dealt to a friendly unit or base: You may exhaust this leader.
              If you do, deal 1 damage to an enemy unit or base."
     DEPLOY: same trigger, no exhaust — "You may deal 1 damage to an enemy unit or base."
     EPIC is the generic 6-resource deploy: zero code.
     ⚠ THE OPTIONALITY SITS IN A DIFFERENT PLACE ON EACH SIDE. Front: the "may" is on paying the
     EXHAUST, and the damage that follows is MANDATORY ("If you do") -> YESNO, then a mandatory target.
     Deployed: there is no cost, so the "may" is on the TARGET -> a single MZMAYCHOOSE. Collapsing the
     two into one shape reds Deployed_FriendlyUnitDamaged_PingsWithoutExhausting (measured).
     ONE trigger, TWO funnels: _SWUOnUnitDamaged (units, above the $survived gate — no "and survives"
     clause, same placement and reason as HMW_045 Logray) and SWUDealDamageToBase (bases, beside the
     JTL_009 Boba hook). ⚠ Boba is the MIRROR of this card — he keys on who DEALT the damage, Cham on
     who RECEIVED it — so do not reuse his dealer-side collector.
     ⚠ USER RULINGS 2026-08-26 (preview set: HMW has no card-specific-rulings.md entry, so both are
     reasoned + confirmed rather than sourced — RE-CHECK WHEN HMW RELEASES):
       (a) triggers ONCE PER DAMAGED THING, not per damage effect. Matches Logray through the same
           seam. Pinned by Deployed_TwoFriendlyUnitsDamaged_TwoTriggers (Blood Sport hits two friendly
           ground units AND an enemy one: the enemy must not add a third).
       (b) "friendly" spans the TEAM in Team Suns — a teammate's damaged unit/base triggers your Cham.
           Implemented as SWUTeamOf($seat) === SWUTeamOf($damagedCtrl), which reduces to self-only
           outside a team game, so Premier/Twin Suns are byte-identical. Pinned by
           TeamSuns_TeammatesUnitDamaged_Triggers + a teams-OFF control on the identical board.
     ⚠ This is the FIRST card built to ruling (b). The ~17 existing cards whose text says "friendly"
     but that read GetUnitsInPlay (a per-seat accessor the Phase 3 helper sweep structurally could not
     reach) are still self-only — Logray among them. Separate audit, still owed.
     ⚠ HARNESS: the cross-player sections need an explicit `P1>Drain` after the opponent's answer — the
     observer queues a lone CUSTOM on an otherwise idle player, which never drains on its own. Without
     it every cross-player section fails looking exactly like the trigger not firing. -->

<!-- HMW_088 Numa, Still Fighting — Done 2026-08-26 (ninth-wave card, one-card-per-pass).
     Clause 1 "Restore 1" is auto-wired ($Restore_Cards['HMW_088'] => 1, generator-derived).
     Clause 2 "If this unit would be dealt damage, prevent 1 of that damage" is THREE lines in
     _SWUApplyDamagePrevention (GameLogic ~2357), beside SHD_224 Boba Fett's Armor and TWI_053 Finn —
     the shared prevention funnel every non-indirect damage path already routes through (combat via
     _SWUShieldOrReduceCombat, ability via SWUDealDamageToUnit, divided via
     _SWUNonInteractiveDamagePrevention). No card file: the card has no registrations of its own, same
     as SEC_050 Vigil.
     Keyed on the OBJECT's CardID, not on a controller — the text says "this unit", not "a friendly
     unit" (SEC_050 Vigil is the aura version and the obvious mis-implementation). 13 sections; the
     three that were green pre-implementation are each mutation-proven:
       • AnotherFriendlyUnit_NotProtected  — reds when the gate is rewritten as a controller aura
       • Indirect_UnpreventableTakesFullAmount — reds when the indirect assignment loop is routed
         through _SWUApplyDamagePrevention
       • Shield_AbsorbsWhenPreventionCannotCover — reds when "prevent 1" is widened to "prevent all"
         (the shield is then kept instead of spent)
     A fourth mutation (controller === the acting frame's $playerID) reds
     EnemyControlledNuma_StillPrevents plus all four combat-DEFENCE sections, which is what pins the
     object-scope reading.
     ⚠ PREVIEW ASSUMPTION (HMW is absent from card-specific-rulings.md): read as a plain continuous
     replacement applying to every damage instance from every source, with indirect staying
     unpreventable per CR. Closest released analogues: TWI_053 Finn (granted, phase-scoped, prevent 1)
     and SHD_224 Boba Fett's Armor (continuous, prevent 2). Re-check when HMW releases. -->

<!-- HMW_265 Twi'lek Kalikori — Done 2026-08-26 (ninth-wave card, one-card-per-pass).
     Ten lines in cards/hmw/TwilekKalikori.php. Joins the released "search top N, play for free within
     a combined-cost budget" family verbatim (SOR_087 Vader, SOR_104 U-Wing Reinforcement, LAW_063
     L3-37, ASH_110 Ackbar): DoTopDeckPlay($player, 8, <filter>, 5). No new plumbing — that helper
     already stores the legal-ID list and the "cost:5" constraint as DQ VARIABLES and re-enforces both
     server-side in _topDeckResolveFromIDs, and TOPDECK_PLAY_NEXT plays each pick through the REAL
     pipeline so the fetched unit's own When Played fires.
     Non-pilot upgrade ⇒ the CollectWhenPlayedAsUpgradeTriggers fallback hands the closure the HOST's
     mzID, while $player is whoever PLAYED the Kalikori. Those differ on an enemy host, and "your deck"
     follows $player — pinned by EnemyTwilekHost_SearchesTheKalikoriControllersDeck (asserts BOTH decks).
     Host gate uses TraitContains (object-aware), not bare HasTrait.
     12 sections, 11 red before implementation. Eight mutations, each asserting exactly 1 replacement:
     removing the host gate, dropping either filter conjunct, depth 8→9 AND 8→7, budget 5→6 AND 5→4,
     and re-scoping "your deck" to the host's controller — each reds its own section and nothing else
     (the depth mutations red the pair in both directions).
     ⚠ ONE FIXTURE HAD TO BE SWAPPED AFTER A GREEN MUTATION. TwilekLeaderPickIsRefused (the "Twi'lek
     UNITS" type conjunct) was first built on HMW_013 Cham Syndulla — a Twi'lek Leader — and dropping
     the type conjunct left it GREEN, because HMW_013 costs 6 and the combined-cost budget of 5 was
     already refusing it. The section was silently testing the budget, not the type. The corpus has
     exactly three Twi'lek non-units, all leaders: SOR_008 (6), HMW_013 (6), LAW_009 (5). Only LAW_009
     sits inside the budget, so it is the ONLY fixture that can isolate this branch; with it the
     mutation reds correctly.
     ⚠ ENGINE BEHAVIOUR WORTH KNOWING (not a bug, and not introduced here): a TOPDECKSEARCH with ZERO
     legal matches still PROMPTS — the player is shown the peeked cards and answers blank. The
     dontSkipOnPass comment in _topDeckSearchBegin reads like an auto-skip and is easy to mis-read as
     "no prompt appears". Shared across the whole family (cf.
     ash/AdmiralAckbar_AssumeAttackCoordinates::SelfDefeat_TakeNothing).
     ⚠ PREVIEW ASSUMPTION (HMW is absent from card-specific-rulings.md): read straight from the CR plus
     the four released members of the family whose wording this card reproduces. Re-check on release. -->

<!-- HMW_185 Ty Yorrick, Monster Hunter — PARTIAL, 2026-08-26. NOT on the Already Done line.
     Text: "If a friendly ability would deal damage, you may have that ability deal that much damage
            plus 1 instead. / On Attack: You may deal 1 damage to a Creature unit."

     CLAUSE 2 (On Attack) — DONE. cards/hmw/TyYorrick_MonsterHunter.php, ~8 lines.
       $onAttackAbilities["HMW_185:0"] → _SWUCollectUnits over all four arenas filtered by
       TraitContains($o,'Creature') → SWUQueueMayChooseTarget → DEAL_UNIT_DAMAGE|1. MZMAYCHOOSE, not a
       mandatory choose: the text says "You may" AND a mandatory multi-target MZCHOOSE queued directly
       in an OnAttack closure auto-resolves to nothing. Combat owns the after-action.
       "a Creature unit" is unqualified — no friendly/enemy and no arena restriction — so the pool spans
       both sides and both arenas (a SPACE Creature is a legal target for this GROUND attacker); Ty
       himself is excluded on the TRAIT (Force / Bounty Hunter), not by self-exclusion.
       9 sections, 7 red before implementation. Mutations: mandatory-instead-of-may reds the decline
       section; trait Creature→Force reds 8 sections.
       ⚠ A third mutation was GREEN and the code was DELETED rather than kept: an
       `if (empty($targets)) return;` before the offer is a no-op, because SWUQueueMayChooseTarget
       already returns on an empty list (GameLogic ~1719). The behaviour it claimed to enforce is real
       and still guarded by NoCreatureInPlay_NoOfferAtAll — the LINE was redundant, and a redundant
       guard carrying a justification comment is what gets copied onto the next card.

     CLAUSE 1 (the friendly-ability damage replacement) — DONE 2026-08-26, to USER RULINGS: it fires
     EVERY time a trigger/ability deals damage; divided damage takes the +1 on the POOL, not per share;
     it STACKS with JTL_165 Hunting Aggressor. Combat damage is excluded (it is not an ability).
     Shared infrastructure, in GameLogic beside the funnels:
       _SWUHmw185Decider($dealer)  → the seat that must answer (controller of a FRIENDLY Ty; "friendly"
                                     spans the TEAM via SWUTeamOf, which collapses to the seat itself
                                     outside a team game, so Premier/Twin Suns are unchanged)
       _SWUHmw185Defer($ty,$param) → YESNO + a dontSkipOnPass CUSTOM (the SEC_101 Amidala shape)
       _SWUHmw185Accepted($ans)
     Hooked into all FOUR ability-damage funnels; the continuations HMW_185#0..#3 live in the card file
     and re-enter each funnel with the amount adjusted and its own offer suppressed:
       #0 SWUDealDamageToUnit  — offered FIRST, above the ASH_196 unpreventable branch and every
                                 prevention, because it changes what the ability DEALS. Target rides as
                                 a UniqueID; the source rides as a UID (or its raw mzID when it is not
                                 an arena object) so the LOF_108 / ASH_196 / SEC_050 source checks
                                 survive the round trip.
       #1 SWUDealIndirectDamage — on the POOL, at the JTL_165 injection point.
       #2 SWUOfferSplitDamage   — NEW shared entry point for divided damage. There was none: seven card
                                 sites hand-built their own MZSPLITASSIGN + SPLIT_DAMAGE pair, so the
                                 pool had nowhere to be modified. All seven migrated (SHD Vambrace
                                 Flamethrower, JTL_009 Boba as an upgrade, LOF The Legacy Run,
                                 ASH_148 Ninth Sister, ASH Hold Them Off, SOR_135 Palpatine,
                                 SOR_092 Overwhelming Barrage).
       #3 SWUDealDamageToBase   — gated on !gInCombatDamage, the flag the three combat call sites
                                 already set and that JTL_009 Boba Fett's "non-combat damage" reaction
                                 already reads. No new $isCombat threading was needed after all.
     20 sections. Six mutations, each asserting exactly 1 replacement, each redding its own set:
     drop the combat gate on base damage · disable the unit hook · disable the split hook · disable the
     indirect hook · always-accept (reds the decline branch across 7 sections) · leak the +1 into
     combat unit damage (reds both combat negatives).

     ⚠ TWO REAL BUGS WERE FOUND BY THE TESTS, both in the re-entry:
     (a) THE RESUME RE-APPLIED HUNTING AGGRESSOR. HMW_185#1 re-enters SWUDealIndirectDamage, which
         re-ran the JTL_165 loop on the already-increased pool — 1 became 4 instead of 3. The parameter
         is therefore named $increasesApplied, not $skipTyOffer, and suppresses EVERY increase in that
         function. General shape: a re-entrant funnel must suppress everything upstream of the resume
         point, not only the effect that caused the deferral.
     (b) A SPARE ANSWER SILENTLY MIS-RESOLVED AN OPTIONCHOOSE. JTL_009 Boba Fett deals indirect damage
         "to A PLAYER", so a You/Opponent OPTIONCHOOSE sits between his exhaust question and Ty's +1.
         The first draft omitted it; the OPTIONCHOOSE took its FIRST option ("You"), sending the pool
         into P1's OWN base, and Ty's question was left dangling. Diagnosed by stepping the scenario
         through TestSchemaStep, not by reading code.

     ⚠ KNOWN SIMPLIFICATION: Ty is unique, so a player controls at most one — but two TEAMMATES could
     each control one in Team Suns, which is strictly two separate replacement effects. Only one offer
     is made. Deliberate, and there is no fixture for it today.
     ⚠ NOT COVERED BY A TEST: the Team Suns "a teammate's ability is friendly" path. SWUTeamOf collapses
     to the seat itself at two seats, so no 2-player section can discriminate it. Worth a four-seat
     section when the next Team Suns pass runs. -->

<!-- HMW_201 Sandtrooper Squad — Done 2026-08-26. VERIFY-ONLY NO-OP: no code, no test file.
     Cost 4 - [Cunning][Villainy] - Unit (Ground) 3/4 - traits Imperial, Trooper.
     Text is EXCLUSIVELY two keyword lines with their reminder text:
       "Ambush (When you play this unit, it may attack an enemy unit.)
        Raid 1 (This unit gets +1/+0 while attacking.)"
     — no rider sentence, so the Step-0 keyword-only fast path applies. All four checks confirmed:
       • $Ambush_Cards['HMW_201'] => true          (generated, auto-derived from the text)
       • $Raid_Cards['HMW_201']   => 1             (value matches the printed "Raid 1")
       • NO entry in GeneratedAbilityStubs.php — no trigger was detected, so there is no stub-without-
         handler silent no-op (the inverse trap).
       • nothing under SWUSim/Custom/ referencing it.
     Generic behaviour coverage already exists for each keyword — Tests/Cases/keywords/Ambush_Yes.md,
     Ambush_No.md, Ambush_NoTargets.md, Raid_AttackBoost.md — and, importantly, the COMBINATION is
     already covered end to end by hmw/TheWarrior_DeftDuelist.md::Deployed_AmbushFiresOnDeployWithRaidOne
     (an Ambush attack that receives the Raid bonus), plus
     Deployed_AmbushAttackExhaustsTheLeaderUnit, which guards the 2026-08-25 Ambush-exhaust engine bug.
     A per-card test here would be GREEN on its first RED check, which the scope rule says to drop. -->

<!-- HMW_102 Dragon's Might — Done 2026-08-26. Event, cost 4, [Vigilance], trait Innate.
     "Defeat a non-leader unit with 4 or less power."
     cards/hmw/DragonsMight.php, one SWUOfferUnitTarget call. It is the intersection of two released
     cards whose sentences already exist verbatim: SOR_077 Takedown (threshold-filtered defeat) and
     SOR_078 Vanquish (the non-leader exclusion). Nothing new was built.
       'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true,
       'extraFilter'  => fn($o) => intval(ObjectCurrentPower($o)) <= 4
     Three readings, each guarded:
       • POWER IS CURRENT, not printed — ObjectCurrentPower folds in upgrades and phase buffs/debuffs.
       • "non-leader" is a LIVE-object question (the nonLeader option runs IsLeaderUnit), so both a
         deployed leader and an ASH_135 Darksaber host are excluded.
       • no controller and no arena qualifier → friendly, enemy, ground, space and token units are all
         legal.
     Mandatory (no "you may"/"up to"), so a plain MZCHOOSE with no decline branch.
     12 sections, 11 red before implementation. Five mutations, each asserting exactly 1 replacement:
     drop nonLeader (reds both leader sections) · threshold 4→5 and 4→3 (reds the boundary in BOTH
     directions, 3 and 9 sections respectively) · printed CardPower instead of ObjectCurrentPower (reds
     exactly the buffed/debuffed pair) · side=>'their' (reds exactly the two friendly/ownership
     sections).

     ⚠ TWO FIXTURE LESSONS, both the same shape as the HMW_265 LAW_009 swap — an exclusion is only
     tested when the EXCLUDED thing would otherwise have been legal:
     (a) THE DARKSABER FIXTURE HAD TO BE CHOSEN FOR ITS ARITHMETIC. ASH_135 is +4/+2, so nearly any
         host ends up above 4 power and is excluded by the THRESHOLD rather than by the leader check —
         the section would pass with nonLeader deleted. SHD_028 Doctor Pershing is a static 0/5 unique
         non-Vehicle unit, so with the Darksaber he sits at EXACTLY 4: inside the threshold, excluded
         only for being a leader unit. (SOR_118 97th Legion looks like the same fixture but scales
         +1/+1 per resource, so its power is not static and it is unusable here.)
     (b) ALL FOUR OFFER SECTIONS FIRST FAILED FOR THE WRONG REASON. The exclusions worked, which
         narrowed each pool to ONE legal target — and a MANDATORY choose AUTO-RESOLVES at one target,
         so there was no pending decision left to assert against. Every offer section now seeds one
         excluded target plus TWO legal ones. "One excluded + one legal" is the instinctive board and
         it is always off by one.
     ⚠ PREVIEW ASSUMPTION (HMW is absent from card-specific-rulings.md): the only genuinely new
     decision is that the metric is POWER, and power is current everywhere else in the engine
     (cost is the value that is always printed). Re-check on release. -->

<!-- HMW_038 Bestial Bond — Done 2026-08-26. Upgrade, cost 3, +2/+2, [Command][Vigilance], Innate.
     "When Played: If attached unit is a Creature or a Force unit, create a Beast token."
     cards/hmw/BestialBond.php, ~6 lines. Non-pilot upgrade ⇒ the CollectWhenPlayedAsUpgradeTriggers
     fallback hands the closure the HOST's mzID while $player is whoever PLAYED it. No printed attach
     restriction, so the CR 2.e default (any unit, either side) already applies — nothing registered in
     SWUGetUpgradeValidTargets.
     The Beast is HMW_T03 (3/3 ground Creature, enters EXHAUSTED). SWUCreateUnitToken is used even
     though there is no rider to carry, because it already routes ASH_094 Moff Jerjerrod's doubling
     through _SWUMaybeOfferJerjerrodDouble.
     Three readings, each with its own guard:
       • the token belongs to the UPGRADE'S PLAYER, not the host's controller (they differ on an enemy
         host — same reading as HMW_265's "your deck", settled the same day);
       • "a Creature OR a Force unit" is ONE condition with two ways to be true, written as a single ||
         — a host that is both (SOR_056) still makes exactly one Beast;
       • the trait is read from the LIVE object via TraitContains, so SEC_054 Exiled from the Force
         genuinely removes it.
     9 sections, 6 red before implementation. The three that were green are each mutation-proven:
       B1 drop the whole gate            → NeitherCreatureNorForce + ForceTraitRemoved
       B2 HasTrait instead of TraitContains → ForceTraitRemoved ONLY (the sharp one)
       B3 the OR as two independent ifs  → CreatureAndForceHost_StillOnlyOneBeast
       B4 token to the host's controller → EnemyCreatureHost_TokenGoesToTheUpgradesController
       B5 attach pool restricted to friendly (a mutation in the SHARED SWUGetUpgradeValidTargets, since
          that section guards engine behaviour this card does not own) → HostPoolIncludesEnemyUnits
          + EnemyCreatureHost
     ⚠ PREVIEW ASSUMPTION (HMW absent from card-specific-rulings.md): only the beneficiary reading is a
     genuine judgement call, and it matches the released upgrade-ownership precedent. Re-check on
     release. -->

<!-- HMW_036 Kelnacca, Solitary Master — Done 2026-08-26. Unit, Ground, cost 4, 4/5, unique,
     [Command][Vigilance], traits Force/Jedi/Wookiee.
     "Restore 2 / When Played: You may pay any number of resources. For every 3 resources paid this
      way, deal damage equal to this unit's power to an enemy unit."
     Restore 2 is generator-derived ($Restore_Cards['HMW_036'] => 2) — no code, generic coverage.

     ★ "PAY ANY NUMBER OF RESOURCES" IS THE **SEC_040 EMERGENCY POWERS** SHAPE (user-directed
     2026-08-26). That card carries the same sentence — "Choose a non-leader unit and pay any number of
     resources. For each resource paid this way, …" — and is the house pattern for it:
       ONE NUMBERCHOOSE over the FULL range 0..(ready resources).
     ⚠ The first draft here used the LOF_255 Curious Flock ITERATIVE loop instead (a repeated
     "pay 3?" YESNO), which also clipped the payment to useful multiples of 3 on the "an optional cost
     that can only fizzle is never offered" rule. That was wrong twice over: it diverged from the house
     pattern for this exact sentence, and "any number" is LITERAL — paying 4 or 7 is a legal choice
     that wastes the remainder, and it matters to a player feeding something that counts EXHAUSTED
     resources (HMW_117 Chewbacca). Reach for SEC_040 for "pay any number", and LOF_255 only for
     "pay up to N" where each single resource buys something.
     The only difference from Emergency Powers is the divisor: intdiv(paid, 3) instances, and the
     prompt is suppressed below 3 ready (where it genuinely could only fizzle).

     ⚠ RESOURCES ONLY — never Credit tokens or SEC_122 Droids (USER-CONFIRMED 2026-08-26: "Credits
     cannot be used as they are not resources, they are separate tokens"). "For every 3 RESOURCES paid
     this way" is a SCALED effect and a Credit is not a resource (CR 3.13). SWUExhaustResources (which
     skips Credits), NOT SWUPayInlineAbilityCost — the documented exception SEC_040#1 and LOF_255#0
     already carry. Shared coverage: Tests/Cases/core/CreditsDoNotScaleResourcePaidEffects.md.

     Power is re-read per instance via ObjectCurrentPower on the live object; Kelnacca rides the loop as
     a UniqueID. The next instance is a QUEUED CUSTOM (#1), not an inline call, so "is Kelnacca still
     here / is there still an enemy unit" are evaluated at DRAIN time, after the previous instance's
     damage resolved (the HMW_035 recompute-before-every-pick lesson).

     13 sections. EIGHT mutations, each asserting exactly 1 replacement:
       Kd drop the below-three guard   → FewerThanThreeReady + CreditsCannotPayAScaledCost
       Ke drop the no-enemy-unit guard → NoEnemyUnit_NoOfferAtAll
       Kj BOTH zero-guards mutated TOGETHER → Decline_NothingPaidNothingDealt
          (⚠ neither alone reds it: `if ($x <= 0) return;` and `intdiv($x,3) <= 0` are REDUNDANT and
           each suppresses a zero payment on its own. Kh mutated only the first and came back green,
           Ki only the second and came back green — the documented "two redundant fixes make
           single-mutation testing lie" case. The early return is kept anyway: it mirrors SEC_040#1
           and stops a negative answer reaching SWUExhaustResources.)
       Kf printed CardPower            → PowerIsCurrentNotPrinted_SnokeShrinksTheDamage
       Kg damage pool not enemy-scoped → 5 sections incl. TeamSuns
       Kb per-resource instead of per-3 → PayThree + PayTwoOfAThree
       Kc gate/range on SWUTotalPaymentCapacity → CreditsCannotPayAScaledCost
       Ka3 range AND clamp clipped to multiples of 3 → PayFourOrSeven + PayTwoOfAThree

     ★★ Ka3 IS THE ONE WORTH REMEMBERING. Its first version — clipping only the NUMBERCHOOSE's displayed
     range — came back GREEN, because the schema harness feeds an AnswerDecision straight to the handler
     without consulting the decision's range. The sections "answer 7" and "answer 4" were therefore
     testing intdiv and nothing else; the OFFERED RANGE was untested. The fix was to make the resolver
     enforce it: the offered maximum now rides in the CUSTOM param and HMW_036#0 clamps to it. Only then
     does clipping the range red those sections. This is the documented "the client cap is UX, the
     server MUST re-validate" rule, and the reason a test that answers a number must be paired with a
     resolver that bounds it.
     ⚠ Kf first came back `repl 0` — a broken probe (whitespace), not a green mutation. Retried.
     ⚠ php -l reported a phantom parse error on this file immediately after a scripted write while the
     suite ran 9755/0; a re-lint was clean. Known bind-mount flush race — re-run before "fixing".
     ⚠ SHD_037 Supreme Leader Snoke (-2/-2 to each enemy non-leader unit) is the fixture that makes the
     current-vs-printed power read observable at PLAY time: Kelnacca enters at 2/3, so each hit is 2.
     ⚠ Kd's board (4 resources + 3 Credits, resources spent on Kelnacca himself) leaves ZERO ready
     resources but a payment CAPACITY of 3 — gating on capacity, which is the CORRECT gate for an
     ordinary "you may pay N" (JTL_096 Blue Leader), raises the prompt there and is the bug. -->

<!-- HMW_145 Origin Tree Shyyyo — Done 2026-08-26. Unit, Ground, cost 6, 4/8, [Command], Creature.
     "Restore 1 / While you control a Kashyyyk base, the first, second, and third units you play each
      round cost [1 resource] less, [2 resources] less, and [3 resources] less, respectively."
     Restore 1 is generator-derived — no code.
     The passive is the JTL_032 Director Krennic shape (a $playCostFieldModifiers field-presence
     modifier scoped per round) but with an ORDINAL LADDER instead of one boolean slot, so it reads a
     COUNTER rather than a _USED flag.
     ⚠ It lives in GameLogic, NOT in a per-card file: $playCostFieldModifiers is initialised at ~2964,
     AFTER cards/_loader.php runs at line 16, so a per-card registration would be silently wiped.

     NEW STATE: SWU_UNITS_PLAYED_ROUND, bumped in ActivateCard's UNIT-ENTRY branch (beside
     SWU_PLAYED_UNIT_{uid}) and cleared at RegroupPhaseStart. That placement is load-bearing twice over:
       • it is AFTER the cost is charged, so a modifier reading it sees the units that came BEFORE the
         one being paid for (the TS26_36 Tribunal lesson);
       • only real UNIT plays reach it — an event, an upgrade, and a Piloting card played as a pilot
         all miss it.

     ★★ THREE USER RULINGS (2026-08-26), all of which fall out of that placement rather than needing
     special cases — they are encoded verbatim as the first three sections:
       1. Shyyyo himself IS the first unit played, but gets NO discount: a unit's passive does not apply
          until it is in play, and his cost is computed while he is still in hand (the modifier loop
          only walks units already on the field). So on a 6-resource round he costs the full 6 and then
          lets a 2-cost and a 3-cost unit follow for free.
       2. A unit played BEFORE Shyyyo still advances the ladder, so the unit after him is the THIRD and
          takes -3 — only that last one is discounted.
       3. LOF_100 Kelleran Beq chains: Beq is the first unit (-1 → costs 6, leaving 1 ready) and the
          unit he fetches is the SECOND (-2) ON TOP of his own -3, so its ceiling is 1+2+3 = 6.

     ⚠⚠ RULING 3 EXPOSED A LATENT BUG IN KELLERAN BEQ, now fixed. His search filter priced candidates
     by hand as `CardCost + SWUAspectPenalty - 3`, which ignores every play-cost FIELD MODIFIER — so
     with Shyyyo out the offer capped at (ready + 3) and the cost-6 unit this ruling says is legal was
     never on the menu. It now prices through SWUComputePlayCost, the same pipeline that charges the
     play (which already includes the aspect penalty, so the hand-rolled version was double-counting
     that too). Reverting it reds Ruling3 and nothing else.
     The reusable form: an affordability FILTER that re-derives a price instead of calling the pricing
     pipeline will drift the moment any modifier exists. Grep for other hand-rolled `CardCost + ... -`
     affordability checks.

     15 sections, 10 red before implementation. EIGHT mutations, each asserting exactly 1 replacement:
       S1  drop the Kashyyyk-base gate      → NoKashyyykBase_NoDiscount
       S2b drop the your-own-plays gate     → OpponentPlaysGetNoDiscount
       S4  flat -1 instead of the ladder    → 8 sections
       S5  add a fourth rung                → FourthUnitOfTheRoundGetsNoDiscount
       S6  never clear at the round boundary→ DiscountResetsOnTheNextRound
       S7  bump the ladder on ANY card play → EventsDoNotConsumeATier
       S8  revert the Beq offer filter      → Ruling3_KelleranBeqChainsBothDiscounts

     ⚠ TWO GREEN MUTATIONS, both diagnosed rather than shrugged at:
     (a) S2 (drop the your-own-plays gate) was GREEN at first, because the base check read
         GetBase($subjectPlayer) — the PAYER's base — which independently blocked the opponent. Two
         gates covering for each other. "While YOU control a Kashyyyk base" means SHYYYO'S controller,
         so the check now reads GetBase($srcController): behaviourally identical while the other gate
         is present, but the two are now independent and S2b reds correctly.
     (b) S3 (drop a `$host !== null` pilot guard) was GREEN because the guard was DEAD CODE — the
         attach path prices through SWUComputePilotCost, a separate function that never consults the
         play-cost modifiers at all. The line was DELETED rather than kept with a comment implying this
         closure protects that case; the behaviour is still guarded end-to-end by
         PilotPlayedAsAnUpgrade_NoDiscountAndNoTierConsumed. (That section's "no tier consumed" half is
         structural too — the attach path never reaches the counter — so no mutation reds it; stated
         here rather than claimed as verified.)

     ⚠ FIXTURE GOTCHAS, each of which first read as the card misfiring:
       • A PILOTING CARD HAS ITS OWN piloting cost ($pilotingCostData), often different from its unit
         cost — JTL_057 is unit-cost 1 but piloting-cost 2, and the attach path charges the latter.
       • JTL_057 also has a When-Played-as-upgrade heal offer; a pending decision blocks EVERY action,
         so leaving it unanswered silently refuses the next PlayHand.
       • The round-advance chain needs a trailing `P2>Pass` under P1OnlyActions (P2 holds the claimed
         initiative and LEADS the new round), plus a seeded deck for BOTH players (the regroup draws).
     ⚠ PREVIEW ASSUMPTION: HMW is absent from card-specific-rulings.md, but all three interactions above
     are user rulings rather than inferences. -->

<!-- HMW_174 Maul, Only Revenger Remains — Done 2026-08-26. VERIFY-ONLY NO-OP: no code, no test file.
     Cost 4 - [Aggression][Aggression] - Unit (Ground) 6/6 - unique - traits Force, Underworld.
     Blank text box, so the Step-0 vanilla path applies: the dictionaries implement it completely (a
     vanilla unit is just its statline). Six checks, all clear:
       1. IN the dictionary (15 entries) — this is the check that matters most on a preview set, because
          a card present in CardMocks.php but ABSENT from GeneratedCardDictionaries.php also awks to an
          empty $textData and reads exactly like "vanilla".
       2. $textData => '' .
       3. CROSS-CHECKED AGAINST THE SOURCE MOCK: CardMocks.php also has text/epicAction/deployText all
          empty, so the dictionary is not merely stale.
       4. no entry in GeneratedAbilityStubs.php (no trigger detected → no stub-without-handler no-op).
       5. no keyword-registry membership.
       6. nothing under SWUSim/Custom/.
     ⚠ TRAIT-PAYLOAD CHECK (a vanilla card is a no-op for its own text, but its TRAITS can be the
     payload — the HMW_142 Kashyyyk-base lesson). Force + Underworld is not new (7 such units exist),
     and the one branch still flagged UNEXERCISABLE in this set —
     hmw/EmperorPalpatine_ConsolidatingPower.md's "If you do" failure path, which needs a
     take-control-immune unit costing 3 or less — is not unblocked by a 4-cost unit with no text.
     Still open. -->
