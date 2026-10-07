<?php
// Per-seat FEATURE SWITCHES for the heuristic stack (RL bots spec, Section 7). Each change is named here and checked with
// SWUBotFeatureOn() where its behaviour lives, so a chooser profile can switch some of them off.
// ⚠ SHIP/HOLD RULE (owner, 2026-10-03 — replaces 2026-09-14's "every change must beat the stack head-to-head", which the
// 2026-09-16 fidelity reframe had already retired): a lever that CORRECTS something demonstrably wrong (shown on a test
// board), measures with no deck significantly hurt (a NULL passes), and has every line under a mutation-checked test ships
// ON as a feature group. A proposal stays a "@try-" only when it measures HARMFUL or is a judgement call rather than a fix.
// Owner: "i fear this outdated rule might be hurting potential explorations." The measurement is a SAFETY check.
// Chooser profiles:
//   heuristic-<style>            everything on
//   heuristic-<style>@base       every feature in SWUBotFeatureList() off — the stack before them
//   heuristic-<style>@no-<name>  only <name> off
// The active set belongs to the DECIDING seat: SWUBotHeuristicChoose() sets it at the start of every decision.
// Code outside a decision (unit tests calling a helper directly) sees everything on.

// Phase 1b part 3 (Control piloting and Talzin): each task appends its feature here; '@no-p3' turns all of them off.
const SWU_BOT_PART3_FEATURES = ['dudgate', 'wipegate', 'targeting2', 'modes', 'force', 'setup', 'baserace', 'buffs', 'unique', 'noeffect', 'fodder', 'pilotdeploy', 'plotdeploy', 'wipekeep', 'flavourrank', 'bombtiming'];

// Part 4 (2026-09-18, the anti-control investigation): owner rulings measured as proposals, then SHIPPED.
//   sentinelkeep — keep Sentinels when resourcing (owner Q4). +28 aggro / +43 midrange / +34 control, never a loss.
//   wipethreat   — a wipe also qualifies on the base damage it prevents (owner: the one-unit Boba wipe). +10 / 2,160
//                  vs aggro, neutral elsewhere.
// Measured TOGETHER before shipping: pooled +124 vs baseline (247:123, p<0.001), no interaction vs sentinelkeep
// alone. '@no-p4' = the stack before them. See the OTMTCGE memory `control-loses-by-not-reaching-round-8`.
const SWU_BOT_PART4_FEATURES = ['sentinelkeep', 'wipethreat'];

// Part 5 (2026-09-19): 'threathold' — a control-wing seat holds a bomb-killer removal event while every enemy unit it
// would kill is both cheap and low-threat by base damage (SWUBotShouldHoldBombKiller, BotFlavours.php). Owner ruling
// 2026-09-18. Session 125 (pre-p4 stack): mid +12, mirror +6, missed its bar. STRICT fresh-seed confirmation vs the
// SHIPPED p4 stack (s021-s040, 13,680 games, pre-registered): mid+mirror +34 (126:92, p=0.025) on fresh seeds alone;
// aggro −4 (p .56, it rarely holds vs aggro — nearly every unit is a threat). '@no-p5' = the stack before it.
// Record: docs/superpowers/research/2026-09-premier-meta/bot-sweeps/2026-09-19_threathold_prereg.md.
const SWU_BOT_PART5_FEATURES = ['threathold'];

// Part 6 (2026-09-20): 'shrinkfirst' — a control-wing seat REMOVES A READY THREAT BEFORE ATTACKING. Owner ruling
// 2026-09-19 on a real lost position (Knowledge and Defense vs a ready Lepi Lookout): "if that Lepi was ready, it
// might be best to shrink it to kill before it attacks. the draw is also very valuable to control." A castable card
// that would defeat a READY enemy unit of 3+ power is played first; among lines, the one removing the most ready
// enemy power wins, then the one that also draws. Rule 'shrink-first' (BotRules.php).
// Discovery (batch 1, s046-s055): +28 mid+mirror, p .182 — "no effect", but the only upward trend.
// STRICT confirmation (batch 2, s056-s095, PRE-REGISTERED primary, 10,798 fresh paired mid+mirror games sized for
// edge >= 0.048): **+136 (866:730), p = 0.001**, ~ +1.3 win-rate points; aggro +11 (n.s.); stable across halves
// (+51 / +85). ⚠ SHIPPED ALONE: the 'lm3' trio (with sentinelpot + freekill) measured WORSE than shrinkfirst by
// itself (−42 paired, p .061), so those two stay proposals. ⚠ The 3-power bar is MEASURED: 'shrinkfirst2' (bar 2)
// lost −88 (p .015). '@no-p6' = the stack before it.
// Record: docs/superpowers/research/2026-09-premier-meta/bot-sweeps/2026-09-20_batch2_prereg.md.
const SWU_BOT_PART6_FEATURES = ['shrinkfirst'];

// Part 7 (2026-09-20): 'buffattack' — a power buff is used BEFORE the attack it improves. Owner report #1052 on
// game 690588: "after i claimed initiative, the bot attacked with Gungi and then buffed him with Ahsoka's
// ability. they should have buffed first and then attacked". ASH_009's "+2/+0 for this phase" on LOF_093 Gungi
// (2/5) was spent AFTER he had swung, so it did nothing at all. Cause: an Action scores a flat W['ability']
// (0.40) while the attack it would improve scores W['base'] x power (0.60 x 2 = 1.20), so the attack always wins
// and the Action is taken afterwards because 0.40 still beats passing. _SWUBotBuffAttackGain (BotFallback.php)
// now adds what the buff is worth to the attacks my READY units can still make, priced with the same weights.
// ⚠ SHIPPED ON THE RULING, NOT ON A MEASUREMENT (owner decision 2026-09-20): buffing a unit that has already
// attacked is strictly zero value, so the floor is "no worse". The ordering is NOT free in general — buffing
// first gives the opponent an action in which to remove the buffed unit — so if an A/B is ever run, '@no-p7' is
// the stack before it. Guard: SWUSim/DevTools/tests/bot_buffattack_test.php.
const SWU_BOT_PART7_FEATURES = ['buffattack'];

// Part 8 (2026-09-22): 'resourcing3' — the OWNER'S RESOURCING RULINGS for the control wing vs an AGGRO-LEADER opponent
// (SWU_BOT_AGGRO_LEADERS, BotResourcing.php), as ordered tiers:
//   - duplicates first;
//   - before the flip, the 7+ drops, non-answers first;
//   - then keep answers, a relevant wipe and a curve.
// Protected: Hyperspace Disaster vs space aggro, Chimaera, and a capital-ship deck's Capital Ships. Against every
// other opponent it is the shipped resourcer, unchanged. Method: the owner's first FOCUSED block (one style, one
// opponent).
// Measured (one-sided, paired, fresh seeds):
//   - Thrawn DV vs Vader: canary +27, confirmation **+104 / 2,000 (19.1 → 24.3%)**, the first effect that did not
//     shrink on confirmation.
//   - Piett vs Vader: **+104 (24.1 → 34.5%)**.
//   - Aurra +21, Lando −3, Thrawn Yellow −4, Krennic Splash −1.
//   - Safety vs Ahsoka +9. Identical to base vs Dedra / Luke ASH.
// Its forerunner 'resourcing2' FAILED safety (Dedra −142, Piett −46): it read "aggressive" off the board and dropped
// the ruling's exceptions ("unless the matchup is slow / you can ramp"). '@no-p8' = the stack before it.
// Record: docs/superpowers/research/2026-09-premier-meta/bot-sweeps/2026-09-22_*.
const SWU_BOT_PART8_FEATURES = ['resourcing3'];

// Part 9 (2026-09-22): 'nogift' — never make a play whose BEST line still makes the opponent stronger.
// FOUND in Bug Report #1066 (game 1097180): Arenabot had no units, played ASH_089 Perseverance ("Heal 3 damage from
// a unit and give a Shield token to it"), and its only legal target was the opponent's LAW_113 — which ended the
// action with 2 Shields. The play case priced the event by cost and tags alone, so nothing saw who it helped.
// _SWUBotPlayIsGift (BotFallback.php) plays the card in the lookahead (targets chosen for the best board change) and
// holds it when the opponent's units / base still come out ahead. Abilities already had the same guard ('buffs').
// ⚠ SHIPPED ON THE OWNER'S RULING, NOT ON A MEASUREMENT ("the bot should not give any advantageous plays to the
// opponent", 2026-09-22), like p7. '@no-p9' is the stack before it. Guard: SWUSim/DevTools/tests/bot_nogift_test.php.
const SWU_BOT_PART9_FEATURES = ['nogift'];

// Part 10 (2026-09-22): 'enablerfirst' — a card whose WHEN PLAYED text improves "the next unit you play this phase"
// is played BEFORE the unit it improves, and is worth what it adds to it.
// FOUND in a Bug Report (game 1105765): "Ahsoka played a 0 power unit before Neel. it should be the other way around
// to be able to 1) ready Tarpals 2) buff him and start the game strong with 4 damage to base". ASH_248 Neel readies
// the next unit played with 1 or less power; HMW_254 Captain Tarpals is 0 power with Raid 2. Neel → Tarpals (ready)
// → Ahsoka's Action (+2/+0) → 4 damage at the base. The bot played Tarpals first and attacked with nothing.
// Root cause: _SWUBotPlayValue is ORDER-BLIND (develop x cost + tags + unitPlay), so two 1-drops tie and the order
// is whatever the enumerator lists first. _SWUBotEnablerFirstBonus (BotFallback.php) prices the grant the way
// 'buffattack' prices a buff — the attack it unlocks, or the resources it saves — and only when an eligible payoff
// is in hand AND still affordable after the enabler.
// ⚠ SHIPPED ON THE REPORT, NOT ON A MEASUREMENT, like p7/p9: an unused "next unit you play this phase" grant is
// strictly zero, so the floor is "no worse". '@no-p10' is the stack before it.
//
// ⚠ SECOND HALF, added 2026-09-23 from Bug Report #1071 (game 1139599), the SAME mistake one layer up: "it played
// Han Solo before Neel. ideally, it should play Neel first, then Han Solo. then buff Han Solo with Ahsoka's
// ability." A softaggro Ahsoka seat with Neel and LAW_037 Han Solo (1 cost, 1/1, 1 POWER, so Neel readies him)
// both in hand. The scorer's bonus above was live and still lost: on the AGGRO WING the go-wide guide
// (SWUBotAggroMaxUnitsPick, BotGuides.php) picks the card, it had already chosen to play BOTH — the subset with
// the most units — and was only deciding which goes first, by "most expensive", which ties at cost 1 and falls
// through to the HAND INDEX. Its weight is 3.0 soft aggro / 4.0 hyper aggro against the bonus's 0.6, so p10 was
// inert for every aggro seat and the order was decided by where the cards sat in hand (measured both ways on the
// reported board). The guide now puts an enabler in its own subset first, under the same switch — '@no-enablerfirst'
// and '@no-p10' turn off BOTH halves, so they stay one A/B.
// Guard: SWUSim/DevTools/tests/bot_enablerfirst_test.php (A-E the scorer half, F-J the guide half).
const SWU_BOT_PART10_FEATURES = ['enablerfirst'];

// Part 11 (2026-09-23): 'mgkeep' — THE FIRST MIDRANGE RULE THIS ENGINE HAS. Owner ruling 2026-09-23 (6):
// "Resourcing ONE duplicate is fine. Do not resource an efficient on-curve body — a second Koska Reeves (4 cost,
// 4/4) was the wrong pick." Until now the keep value at rank 2 was the AGGRO WING'S FALLBACK, -$cost ("resource
// the most expensive card"), with every refinement in BotResourcing.php gated to rank >= 3. The 2026-09-23
// ablation measured the consequence: every feature group shipped since part 4 changes ZERO games for a midrange
// seat. Implementation and the clause ORDER (an efficient body is kept even when it IS the duplicate) are in
// SWUBotChooseResourceCards.
// MEASURED — and read the second line before trusting the first:
//   Luke ASH DV vs Krennic: +8.0 on the screen (BH q=0.027 / 8,000 games) and +8.4 on FRESH seeds (51/30,
//     p=0.026 / 1,000). A screen's winner normally shrinks on confirmation; this one did not.
//   ⚠ THE GAIN DID NOT GENERALISE. The safety panel (2,016 games: Armorer Nabat, Obi-Wan Vergence, Piett Red,
//     Talzin Force vs Vader Yellow and Krennic Splash) pooled to −0.2 points, 72/74 discordant, p=0.93. Nothing
//     was significantly hurt anywhere (worst cell −4.0, q=0.88), which is what a safety panel tests — but at
//     n=126 a cell can only rule out LARGE harm, and the +8 is so far a Luke ASH DV result, not a midrange one.
//     Shipped because it is the owner's own ruling, it replicated on its own deck, and no cell shows harm.
//     Re-measure on the new bot loop before treating the size as real.
// Records: bot-sweeps/2026-09-23_midrange_arms_result.md, _mgkeep_split_result.md, _mgkeep_safety_result.md.
// Guard: SWUSim/DevTools/tests/bot_mgresource_test.php.
const SWU_BOT_PART11_FEATURES = ['mgkeep'];

// Part 12 (2026-09-24): 'mgbomb' — MIDRANGE gets the resourcing rule the control wing already had (owner ruling
// 2026-09-13: "keep a hand it can CAST — everything castable within ~2 regroups, plus ONE copy of its biggest
// card"). It was gated `$rank >= 3`, so midrange fell through to the bare `-$cost` fallback and buried its most
// expensive card. FOUND in the owner's 99-game human-vs-bot run: the midrange bot's resource pick sat +0.67
// (block 1) and +0.92 (block 4) ABOVE its own hand average and was the most expensive card 40-44% of the time,
// while the owner's own pick sat -0.57 and -0.54 BELOW his — the SAME skew across two completely different
// archetypes (Ahsoka Blue, hand avg 3.31; Colossus, 4.88), so his rule is "bury ~0.55 below your hand average"
// and it is deck-independent. Shipping 'mgkeep' as p11 did NOT move the bot's skew; this does.
// MEASURED (2026-09-24 screen, 5 arms x 8,000 games, FOCUS on the 5 midrange decks, 0 timeouts): paired McNemar
// on the 4,400 shared midrange cells against BOTH jitter nulls — 590:454 vs jitter-up and 595:454 vs jitter-down,
// **p = 0.0000 against each**. Midrange 44.59% against nulls at 41.50 / 41.39, i.e. **+3.14pp on a 0.11pp null
// spread**. Beat the rival 'mgkill' arm head to head 599:503 (p=0.0042). No style regressed: every NEW column is
// identical across arms (this touches rank 2 only) and the OLD columns fall only because their midrange
// OPPONENTS improved. '@no-p12' / '@no-mgbomb' = the stack before it.
// ⚠ Bot-vs-bot. Self-play rated hyperaggro the strongest style immediately before it went 0W/25L against the owner.
// Guard: SWUSim/DevTools/tests/bot_midrange_levers_test.php.
const SWU_BOT_PART12_FEATURES = ['mgbomb'];

// Part 13 (2026-09-25): 'mgkill' — MIDRANGE prices a kill at 0.54 instead of 0.90, against a flat base of 0.60.
// The shipped table had kill at 1.5x base, so a midrange seat preferred the TRADE to the SWING before reading
// the board at all; p13 inverts that to 0.9:1. The owner's 99-game run showed the consequence from the other
// side: the bot's damage per base swing never moved across three decks and three styles.
// MEASURED THREE TIMES. Screen 1 (global `@w-kill-down` probe, 10,120 games/arm): midrange +2.2pp. Screen 2
// (scoped, 8,000/arm, PRE-p12 baseline): +0.96pp, paired p = 0.072 / 0.0525 — right direction, NOT significant.
// CONFIRMATION (24,000 games/arm at 60 seeds, ON TOP OF the shipped p12): midrange **44.89% against jitter nulls
// at 43.50 / 43.09 = +1.59pp**, paired McNemar **781:597 and 879:642, p < 0.00001 against BOTH**, on 1,378 and
// 1,521 discordant pairs against the ~1,220 the run was sized for. No style regressed (hardcontrol, hyperaggro,
// softaggro all PASS). '@no-p13' / '@no-mgkill' = the stack before it.
// ⚠ THE NOISE FLOOR IS NOT ZERO: jitter-up vs jitter-down was itself 233:180, p = 0.0105. The two nulls are
// opposite +/-3% develop nudges, not identical stacks, so a small real difference between them is expected.
// mgkill's imbalance is ~4x theirs and its p is five orders of magnitude smaller, which is why it still reads
// as confirmed — but do not treat "paired vs a null" as a zero-noise test.
// ⚠ Its rival 'mgtrade' (pay MORE for a kill) was measured HARMFUL on the same bench — see its entry.
// Guard: SWUSim/DevTools/tests/bot_midrange_levers_test.php.
const SWU_BOT_PART13_FEATURES = ['mgkill'];

// Part 14 (2026-09-28): 'upgradepicks' — a decision whose candidates are UPGRADES or TOKENS is scored by what the
// upgrade is worth and WHOSE it is, not by enumeration order.
// FOUND in two bot reports that turned out to be ONE root cause: "Bot used Alliance Outpost to defeat shield on
// Secretive Sage and then gave it another shield", and "Bot defeated its own shield with Outer Rim Constable".
// An upgrade candidate is a SUBCARD mzID ("myGroundArena-0.u0"). SWUBotViewForMz resolves with GetZoneObject, which
// returns null for that form BY DESIGN (Core/CoreZoneModifiers.php: "an un-taught caller gets a clean miss instead of
// silent corruption" — the generic resolver is MZResolveObject). The bot was that un-taught caller, so every upgrade
// candidate scored null and the scorer fell through to `$s ?? -$index * 1e-6`, the stable-order tiebreak. "my*" is
// enumerated before "their*", so the first candidate is always the bot's own.
// ⚠ NOT A TIE-BREAK MISTAKE — IT IS DETERMINISTIC SELF-HARM. Measured before the fix: my Shield 0, the ENEMY's
// Shield -1.0e-6, PASS 0. The enemy's scored strictly WORST, so the bot destroyed its own Shield every time, and
// preferred that to declining a "you may".
// ⚠ SHIPPED ON THE REPORTS, NOT ON A MEASUREMENT, like p7/p9/p10: hitting your own Shield instead of the
// opponent's is strictly worse, so the floor is "no worse". '@no-p14' is the stack before it.
// Guard: SWUSim/DevTools/tests/bot_upgradepicks_test.php.
const SWU_BOT_PART14_FEATURES = ['upgradepicks'];

// Part 15 (2026-09-28): 'tags3' — the V3 CARD TAGS. Owner rulings (27 answers) after reading three real
// Online game logs: tag BOTH leader faces (v2 read $textData only, so 87 leaders' deployed side was
// invisible), tag the KEYWORDS a card has as well as its effects, and split the coarse tags so each half can
// be priced on its own — damage(units) / burn(base) / indirect-damage, exhaust-enemy / exhaust-friendly,
// heal(immediate) / restore(keyword), buff(temporary stats) / gives-experience / gives-shield / gives-weakness,
// resource-ramp / credit-ramp, mill-self / mill-opponent, gains-the-force / uses-the-force, one
// create-<token>-token per kind, plus sacrifice, capture, debuff, discount, gives-sentinel, recursion,
// search-top-deck. Kebab-case throughout.
// FOUND while analysing those logs: the v2 table was also STALE — 77 cards matched a tag rule and had none,
// 27 in HMW and 42 in TS26, the two preview sets. HMW_186 Mining Guild Trespasser, which closed all three
// games, was untagged and therefore priced as a vanilla body.
// ⚠ '@no-tags3' reads the FROZEN v2 table (CardTags.v2.generated.php), so every arm measured against v2
// stays reproducible; '@no-tags2' still reads v1.
// ⚠ NEW TAG KINDS ARE INERT UNTIL WIRED: _SWUBotPlayValue sums $W[$tag] ?? 0.0 and BotDeckStyle filters to
// removal/wipe/burn/draw, so behaviour moves only through the corrected/re-split tags. RL move keys are
// PINNED to the nine v2 names by SWUBotCardTagsForRlKey() — otherwise the key space would explode and
// invalidate every trained policy.
// Guard: SWUSim/DevTools/tests/bot_card_tags_test.php.
const SWU_BOT_PART15_FEATURES = ['tags3'];


// Part 16 (2026-09-29): 'aspectwaiver' — a base Epic Action that WAIVES AN ASPECT PENALTY is worth the card
// it UNLOCKS, with NO flat ability floor.
// SHIPPED off BUG REPORT #1098 (prod Arenabot, game 1402804): "bot wasted its base epic action to play an
// in-aspect card". The log is unambiguous — P2 used Daimyo's Palace's Epic Action and then played LAW_097
// Imperial Door Technician, which is Vigilance/Villainy and therefore on-aspect for a Vigilance base +
// Command/Villainy leader, so the once-per-game waiver bought nothing. LAW_044 Single Reactor Ignition
// (Aggression, +2) sat in hand unplayed.
// TWO defects, both traced: _SWUBotEnabledPlayValue credited the action for the best card the prompt
// OFFERS whether or not it was ALREADY CASTABLE, and _SWUBotAbilityValue floors every ability at
// W['ability'] = 0.40, which beats PASS on any board. Valuing it correctly IS the "save it" mechanism.
// ⚠ WHY IT SHIPPED DESPITE AN EARLIER "HARMFUL" READING. As a proposal it measured 0/24 wins with base
// damage 7.9 -> 5.3, and that kept it off. n=24 is far below this matchup's noise floor (+-2 wins per 100;
// two disjoint 100-game samples of the SAME config gave 9 and 11). Re-measured at n=500 on 2026-09-29:
//   baseline      35 wins (7.0%)  95 reached R7 (19.0%)  base damage 8.7
//   aspectwaiver  42 wins (8.4%) 108 reached R7 (21.6%)  base damage 9.0
// Directionally better on every metric (arrival +2.6pp p=0.31, wins +1.4pp p=0.41) and base damage UP, not
// down — the 7.9 -> 5.3 was noise. Not significantly better either; it ships on CORRECTNESS, because
// burning a once-per-game waiver on a card that needs no waiver has zero upside on any board.
// Guard: SWUSim/DevTools/tests/bot_aspectwaiver_test.php (section F is the reported board).
const SWU_BOT_PART16_FEATURES = ['aspectwaiver'];

// Part 17 (2026-10-01): 'hostpolicy' — every attachment is an "Upgrade", but some are DOWNGRADES. Owner rulings
// place each listed upgrade on a side of the table (SWU_BOT_UPGRADE_HOST_POLICY, BotFallback.php): Bounty granters
// and ability/ready/stat suppressors only on an ENEMY unit; Preparation, Battle Fury, Death Star Plans, Sith
// Holocron and Han's Golden Dice only on my own; Size Matters Not only on my own small unit; Entrenched on an enemy
// without Overwhelm or my own Sentinel. A listed upgrade with no allowed host is held.
// SHIPPED on the rulings, not on an A/B (like p9 'nogift'). Two defects it closes: the host pick put Wanted / Death
// Mark / In Debt to Crimson Dawn / Grav Charge on the bot's OWN unit whenever it had one (the board read sees no
// Bounty or "can't ready"), and the same-day extension of 'nogift' to every upgrade (LAW_129 Mastery, game
// 1438045) made every 0/0 downgrade read as a gift on an enemy host, so none was ever played.
// Guard: SWUSim/DevTools/tests/bot_hostpolicy_test.php.
const SWU_BOT_PART17_FEATURES = ['hostpolicy'];

// Part 18 (2026-10-01): 'doomedsac' — a unit that is going to die anyway is cheap to sacrifice. Owner rulings, from the
// Karabast Krennic games (the human sent their own deployed Krennic back with Chimaera):
//   2a a DEPLOYED LEADER is a sacrifice (Chimaera's friendly pick) when it is Condemned (SEC_038 — sending it back
//      restores its front side) or has 2 or less HP left and the opponent can deal with it — but only when no other
//      fodder is available, so it is priced above a 2-cost body (SWU_BOT_DOOMED_LEADER_SAC_COST).
//   2b any other unit with 2 or less HP left that an enemy unit can finish (a high-HP Sentinel on HP-1/HP-2) is cheap
//      fodder: "the Credit may be more valuable next round", and outvalues the base damage it would have blocked.
// "Can deal with it" is read from the visible board: an enemy unit in its arena with power >= its remaining HP, and
// no Shield on it. Shipped on the rulings. Guard: SWUSim/DevTools/tests/bot_doomedsac_test.php.
// 'unusedsac' (same day, task 3): a sacrifice that can wait (a repeatable Action) charges a still-READY unit for the attack
// it would throw away — attack first, then cash the body in. Guard: SWUSim/DevTools/tests/bot_unusedsac_test.php.
const SWU_BOT_PART18_FEATURES = ['doomedsac', 'unusedsac'];

// Part 19 (2026-10-01): 'weakness' — Weakness tokens (HMW_T02, -1/-1) are DOWNGRADES, and a Weakness give or spread
// (Torrent, Ravage, Talzin's Shuttle, Hemlock…) looks for the kill it makes before the body it shrinks. Owner request,
// after game 1438045 (the bot Torrented its own Sentinel). Three defects it closes:
//   - the value model counted a Weakness as an upgrade PREMIUM, so a Weakened unit priced above a healthy copy, and
//     "defeat an upgrade" (SEC_163) took the Weakness off an ENEMY unit;
//   - a Weakness prompt carried no amount, so the picker never saw the unit its tokens defeat ("clean up") and a Shield
//     read as stopping it — Weakness is HP reduction, unpreventable;
//   - a non-lethal token was priced the same on every body, so a spread went by enumeration order, not on the strongest
//     body (the one whose -1 power matters: "soften up").
// SHIPPED on the owner's request, not on an A/B. Guard: SWUSim/DevTools/tests/bot_weakness_test.php.
const SWU_BOT_PART19_FEATURES = ['weakness'];

// Part 20 (2026-10-02): owner rulings from Ninin's Ahsoka Yellow (ASH_009) Karabast games.
// 'buffspread' — a Support leader's flip turn SPREADS its "+N for this phase" buffs so every one lands on a unit that
//   still attacks: "+2 Raid from the Naboo ship onto the Supported unit, +2 from Jar Jar to this or another unit, +2
//   Supported attack buffs someone else (or Ahsoka), then +2 Raid from Ahsoka attacking and another +2 on her own On
//   Attack" — a +10 turn. Concretely:
//     · while my Support attack is still pending, a friendly phase buff (SEC_111 Jar Jar's +2/+2 off Plot) goes on the
//       unit that will MAKE the Support attack, never on the leader: its borrowed "less power than this unit" On Attack
//       then reaches the leader (a 2-power unit + 2 + the Starship's lent Raid 2 = 6 > Ahsoka's 5);
//     · the Support attacker is that same planned unit;
//     · the Supported unit's borrowed buff goes on the leader (she attacks next), else a ready unit;
//     · an arena with an enemy SENTINEL is where buffs are wasted — attacks there cannot reach the base — so the plan and
//       every buff go to the clear arena (usually space, where this deck goes wide), the leader included.
//   Measured on a thin board (one other unit): the bot buffed Ahsoka with Jar Jar, so the Supported attack's +2 had no
//   attacker left and fell on the just-played, exhausted Jar Jar. Guard: SWUSim/DevTools/tests/bot_buffspread_test.php.
// 'readyhost' — an upgrade that attacks with its host when played ('grants-attack': JTL_203 Han Solo's Piloting, SOR_215 /
//   SHD_223 Snapshot Reflexes, SHD_174 Hotshot DL-44) goes on a READY host: an exhausted one cannot attack, so the play's
//   attack is thrown away. Han piloted an exhausted T-6 Shuttle over a ready Mando's N-1. Guard: bot_readyhost_test.php.
const SWU_BOT_PART20_FEATURES = ['buffspread', 'readyhost'];

// Part 21 (2026-10-02): 'namecard' — "Name a card" names the OPPONENT'S cards (Ryder Azadi, Regional Governor, Galen,
// Transmission Jamming, Chimaera, Garindan, Inspector's Shuttle, Stolen Starpath Unit, Zuckuss) from what they have shown
// plus the meta lists for their leader; Foresight names the commonest title left in MY deck. It used to name the first
// title, alphabetically, of the bot's OWN deck. See SWUSim/Custom/BotNameCard.php. Guard: bot_namecard_test.php.
const SWU_BOT_PART21_FEATURES = ['namecard'];

// Part 22 (2026-10-03): 'powersource' — a friendly unit chosen to DEAL damage equal to its power (Krennic's When Deployed,
// Strike True, Breach, Hold Them Off, Volley Fire's Raid, Turbolaser Salvo) is the strongest one, not the cheapest. The
// "deal damage" tooltip had it priced as self-harm, so Krennic struck with a 0-power Spy (owner report, game 1438045).
// Guard: SWUSim/DevTools/tests/bot_powersource_test.php.
const SWU_BOT_PART22_FEATURES = ['powersource'];

// Part 23 (2026-10-03): 'bigcredit' — a credit-ramp deck (Krennic; not Lando's tempo Credits) spends a banked Credit only
// on a big play (cost 5+, removal, wipe, damage): a cheap play that NEEDS the Credit is held. Owner report, game 1438045
// ("wasted them right away on Onyx Squad Brute"). Guard: SWUSim/DevTools/tests/bot_bigcredit_test.php.
const SWU_BOT_PART23_FEATURES = ['bigcredit'];

// Part 24 (2026-10-03): 'waiverhold' — a LAW waiver base's once-per-game Epic Action that unlocks NOTHING scores below
// PASS. It scored exactly 0 (p16 removed the flat floor), which TIED pass, so with the initiative already taken the
// waiver went by enumeration order and its mandatory play spent a banked Credit on an on-aspect 2-drop (owner report,
// game 1438045). Guard: SWUSim/DevTools/tests/bot_waiverhold_test.php.
const SWU_BOT_PART24_FEATURES = ['waiverhold'];
const SWU_BOT_WAIVER_UNLOCKS_NOTHING = -0.1;

// Part 25 (2026-10-03): 'defeatpick' — an enemy pick under "defeat those units / that unit / them" is a DEFEAT, scored by
// the unit's value. Chimaera's enemy half was unscored, so it took the first-listed enemy — a 1-cost Han Solo over an
// 8-cost Pre Vizsla — even when it sacrificed ITSELF for it (owner report, game 1438045). Guard: bot_chimaera_test.php.
const SWU_BOT_PART25_FEATURES = ['defeatpick'];

// Part 26 (2026-10-03): 'breach' — a Sentinel kill is worth the base damage it UNLOCKS: W['base'] x the attack power of my
// other ready, non-Saboteur units in that arena (BotFallback.php _SWUBotBreachOpened); 0 if another Sentinel of that
// player remains there or, Twin Suns, another opponent's base is already reachable in that arena. Owner ruling (Ahsoka
// research): "if the opponent has a high HP sentinel, then try to buff something low-value so that it can crash in and
// make way for the other units to attack. however, if that sentinel can be cleared with a unit post-buff that survives,
// take that line if and only if it enables more damage from the other units" — ties go to the survivor (no loss term).
// buffspread (p20) no longer writes off a Sentinel arena when a unit (+ the buff + the leader's Raid that Support LENDS)
// can breach it AND the breach opens something. ⚠ W['base'] is FLAT for every style, so this mostly moves AGGRO (control
// already trades into Sentinels); owner kept the table. One step only: no pop-the-Shield-then-kill plan.
// MEASURED one-sided on fresh seeds as @try-breach: focus (Ninin's Ahsoka Yellow vs 5 panel decks, 1,000 pairs) 110 games
// changed, 757 vs 757 wins, discordant 9:9 (p 1.0); safety (Vader Y, Ezra Y, Luke ASH DV, Maul Blue, Mando Colossus, 200
// pairs each) pooled 5:5, no deck below p .69. A NULL — SHIPPED on the owner's ruling because it does not hurt ("if it
// doesn't hurt, then let's ship it"), like p7/p9. Its flip-turn board goes 9 -> 14 base damage (bot_breach_test G).
// Spec: docs/superpowers/specs/2026-10-03-swusim-bot-sentinel-breach-design.md. Guard: SWUSim/DevTools/tests/bot_breach_test.php.
const SWU_BOT_PART26_FEATURES = ['breach'];

// Part 27 (2026-10-03): 'popkill' — popping a Shield is worth the KILL it sets up: an attack that only pops a unit's ONE
// Shield ('bounce', or 'die' — the popper still takes the Shield) is credited with the best kill another READY unit of
// mine in that arena then makes on the unshielded unit (SWUBotTargetValue on the popped copy, so a breach counts). A pop
// used to be chip − 0.05 × power (≤ 0 for any 3+ power hyperaggro attacker) or a plain loss, so the wrong unit popped (the
// big one, under rule 8) and the cheap one never did. Research 2026-10-03 (traces of 1,670 games): an upgraded Sentinel
// walls the bot in 18% of games, nearly all ASH_048 Imperial Armored Commando (Krennic Blue) / LAW_118 Droid Laser Turret
// (Mando Colossus) — cheap printed Sentinel + Shielded.
// MEASURED (fresh seeds h001-h050; Vader Y / Ahsoka (Ninin) / Ezra Y vs Mando Colossus + Krennic Blue, 600 pairs): 13 games
// changed, discordant 2:0 against (p .5) — NEAR-INERT, no deck hurt. 265 of 299 wall-rounds had fewer than 2 ready units in
// the wall's arena, so the two-step was never on: the gap is BODIES there, not pricing. SHIPPED under the 2026-10-03
// ship/hold rule (a null correction ships ON; see the header). One step only: two Shields are not credited.
// Guard: SWUSim/DevTools/tests/bot_popkill_test.php.
const SWU_BOT_PART27_FEATURES = ['popkill'];

// Part 28 (2026-10-03): FIVE HELD PROPOSALS re-reviewed under the 2026-10-03 ship/hold rule (header) and shipped — each
// corrects something the stack got wrong, none measured harmful:
//   aurathreat — a unit's threat/value includes the power its aura GRANTS (Victor Leader: +1 per other ship);
//   mgcost     — the resourcer judges a card at the cost THIS SEAT pays (printed + aspect penalty: Chimaera is a 9 for Luke);
//   mgbuff     — a buff-and-attack Action that applies its buff in its own handler is priced by what it adds;
//   leaderrisk — a deployed LEADER unit that dies RETURNS (exhausted): its loss in a trade costs less;
//   ctxpower   — a hand card whose power depends on the board is valued ON the board (Clone Combat Squadron).
// SAFETY SWEEP (fresh seeds k001-k024; each alone, one-sided, on Vader Y / Ezra Y / Luke ASH DV / Maul Blue / Mando Colossus
// / Krennic Blue vs the 5-deck panel, 1,440 pairs each; base-only : arm-only, games changed):
//   aurathreat  1:0   13 changed (inert)        mgbuff      3:2   36 changed (Luke only)
//   leaderrisk  3:2   49 changed                mgcost     25:31 201 changed (Luke 2:6, Mando 23:25)
//   ctxpower   31:38 316 changed (Ezra 17:9 p .17, Mando 10:20 p .10, Vader 4:9) — no deck hurt at p < 0.05 for any of them.
// mgcost re-priced 16 pinned resourcing checks (their boards are off-aspect); the owner had them updated to its picks.
// HELD from the same sweep, owner's decision: 'sentinelpot' (pooled 29:19 AGAINST, p .19; Krennic 12:5 p .14 — a second negative trend after
// the 2026-09-19 lm3 trio — owner: "hold that one") and 'creditbank' (superseded by p23 'bigcredit'; measured worse vs Ahsoka Blue).
const SWU_BOT_PART28_FEATURES = ['aurathreat', 'mgcost', 'mgbuff', 'leaderrisk', 'ctxpower'];

// Part 29 (2026-10-03): a card DISCARDED FROM MY OWN HAND is a real cost. From a human pilot's 53 Arenabot games with Darth
// Vader, Unstoppable (BotData bundle swusim-botdata-20261004-025711, 44 won): ~1.8 leader pings a game, 80 of 82 at UNITS (64%
// killed), paid with dead cards. The bot on the same list (fixture arenabot-ideas/darth-vader_law_blue-force): ~4.5 pings a
// game, 715 of 907 at the BASE, paid with hand index 0 — Chimaera 95 times, Anakin 79 — and won 10% of 200 games.
//   discardpick — an own-hand discard prompt takes the card worth least to KEEP (play value less its aspect penalty);
//   heropitch   — owner 2026-10-03: an off-aspect Heroism card is "worth more in the discard to activate Anakin fully";
//   pingvalue   — an Action that costs a discarded card is worth its effect less that card; a "deal N to a unit or base"
//                 ping is removal (a kill) or a finisher (lethal), never chip on a base;
//   dumpdamage  — deployed Vader's "discard any number, deal that much": k cards are worth k damage (a kill, lethal, or base
//                 chip) less their keep; owner: hold the hand while Aggressive Negotiations (+1/+0 per card) is in it;
//   anvader     — a hand-size attack event (SEC_179 Aggressive Negotiations) is worth the attack it makes, and goes to the
//                 attacker the hand pays most — deployed Vader, whose On Attack cashes the hand again ("double buffed").
// Guards: SWUSim/DevTools/tests/bot_discardcost_test.php, bot_anvader_test.php.
const SWU_BOT_PART29_FEATURES = ['discardpick', 'heropitch', 'pingvalue', 'dumpdamage', 'anvader'];

// Part 30 (2026-10-03): from the owner's 5 Arenabot games with Hemlock Red vs his Krennic Blue Splash (BotData, 1 bot win):
//   fodderfirst — a paired-defeat card (Chimaera) with no friendly unit in play to give waits for the cheap unit that will be
//                 its price (1483356 R6, 1483359 R16: Chimaera defeated ITSELF, then 0-0-0 was played the same round);
//   wipeaware   — once an opponent has cast a "defeat all units, 1 damage per enemy unit" wipe (LAW_044 Single Reactor
//                 Ignition), a play that leaves as many units as my base has HP is held: 1483356 R19, a second Pre Vizsla
//                 took the board to 7 units on ~7 HP and the next SRI dealt exactly lethal.
// Guards: SWUSim/DevTools/tests/bot_fodderfirst_test.php, bot_wipeaware_test.php.
const SWU_BOT_PART30_FEATURES = ['fodderfirst', 'wipeaware'];

// Part 31 (2026-10-04): three plays from Ninin's human-vs-human Premier game, Hemlock Red vs Lando Blue. FOLDED IN WITHOUT A
// MEASUREMENT on the owner's instruction ("no measure. just fold it in as is"):
//   etbsetup       — a When Played gated on "If you control a unit that costs N or less" waits for the cheap unit that turns it
//                    on (Imperial Door Technician, then Dooku's Solar Sailer: Lando discarded Chimaera);
//   weaknessaction — "Action […]: Give a Weakness token to a unit" is worth its best target (kill or soften) less its resource;
//   spentetb       — a unit whose only text is a used When Played is its BODY when sacrificed (Chimaera took the Sailer);
//   observerfirst  — (Ninin vs Maul Blue, R16) an HK-47-style "when an enemy unit is defeated: deal N to its controller's base"
//                    unit goes down before this round's kill. Same game widened p30 'fodderfirst': a cheaper hand unit is the
//                    price even when units are in play;
//   uniquereplay   — (Ninin vs Ackbar Data Vault, R3) a second copy of a CHEAP unique (cost <= 3) with a When Played, over a SPENT
//                    copy (exhausted or damaged): the old copy goes, the When Played resolves again. Owner: "usually true for
//                    cheap units with When Played abilities."
// Guard: SWUSim/DevTools/tests/bot_hemlockplays_test.php.
const SWU_BOT_PART31_FEATURES = ['etbsetup', 'weaknessaction', 'spentetb', 'observerfirst', 'uniquereplay'];

// Part 32 (2026-10-04): 'splitpop' — in a damage SPLIT, a point that pops an enemy Shield is worth a chip (and popping my own
// costs one), the price SWUBotTargetValue already gives an attack that only pops a Shield ('bounce'). _SWUBotSplitScore
// skipped a Shielded target outright, so "spy:1,commando:1" and "spy:2" both scored the Spy kill alone and enumeration order
// broke the tie. Owner report, game 1485163: ASH_148 Ninth Sister (2 to split) put both on a 1-HP Spy instead of killing it
// and popping the Imperial Armored Commando's Shield. A null correction — it only separates ties a Shield made — so it ships
// ON under the 2026-10-03 ship/hold rule (header). Guard: SWUSim/DevTools/tests/bot_splitpop_test.php.
const SWU_BOT_PART32_FEATURES = ['splitpop'];

// Part 33 (2026-10-04): 'splitlethal' — bug #1126, game 1485163. A damage split that can finish an enemy base takes the win:
// Devastator's 4 indirect went to two kills (Latts Razzi, Lepi Lookout) with the Krennic base on 4 HP. _SWUBotSplitScore priced
// base damage W['base'] a point with no lethal check. A correction (bug fix) — ships ON. Guard: bot_splitlethal_test.php.
const SWU_BOT_PART33_FEATURES = ['splitlethal'];

// Part 34 (2026-10-04): 'lethalrace' — rule 2 'lethal-now' summed every ready attacker against the enemy base, but SWU
// alternates actions: K attacks hand the opponent K-1 actions, and when their ready attackers kill me in those the lethal
// never lands. 400-game Hemlock Red vs Vader Yellow sweep: in that spot the firing seat lost 80 of 84; all 9 Hemlock losses
// with Hyperspace Disaster castable-but-uncast were this rule (hv024: 2 HP, 4 attacks needed, nine ships ready). Rule 2 now
// stands aside and rules 4/5 decide. A correction — ships ON. Guard: SWUSim/DevTools/tests/bot_lethalrace_test.php.
const SWU_BOT_PART34_FEATURES = ['lethalrace'];

// Part 35 (2026-10-04): 'lockpiece' — Ninin vs Luke (ASH) Data Vault: Galen Erso (SEC_046) named Chimaera and Ryder Azadi
// (ASH_077) named Pre Vizsla; Ninin killed Galen, then Chimaera came down whole. An enemy unit whose name-lock holds a card in
// my hand is worth half that card's printed cost more dead, and all of it on a FREE kill (an attack) that leaves the card
// castable this round — owner 2026-10-04: "valued higher if i have a way to kill them without spending resources. this way i
// can still play the bomb same round." The bomb a Galen blanks waits while an attack can kill that Galen this round. Priced
// only from the locked seat's view (SWUBotViewerSeat). Guard: SWUSim/DevTools/tests/bot_lockpiece_test.php.
const SWU_BOT_PART35_FEATURES = ['lockpiece'];

// Part 36 (2026-10-04): levers from Ninin's human games, owner "build all 7". Each correction ships ON under the ship/hold rule.
//   upgradecost — an attachment is worth its card's printed cost (a token 1, a downgrade 0), not a flat +1: No Glory, Only
//                 Results on Open Circle Ace took Han Solo with it (Reprint_Cad vs Ninin, R4). Guard: bot_upgradecost_test.php.
//   defeatimmune — a defeat effect of mine is worth nothing on a unit that "can't be defeated by enemy card abilities" (SWUAvoidsDefeat):
//                 target pick, wipe value, Chimaera's best enemy, SWUBotHandCardKills. Chewbacca piloted the Sheathipede (same game).
//                 Guard: bot_defeatimmune_test.php.
//   disclosereserve — while my Condemn sits on an enemy unit, the last card that can disclose Vigilance+Villainy is priced at
//                 the power its -6/-0 blanks when played, and kept off the resource pick. Reprint_Cad played Marrok (same game).
//                 Guard: bot_disclosereserve_test.php.
//   condemnfore — an attacker under an ENEMY Condemn expects -6 power when the defender can disclose (the defender knows its hand;
//                 others see its hand size). Condemned Ahsoka was blanked three times (same game). Guard: bot_condemnfore_test.php.
//   wipeinit    — a relevant wipe (Hyperspace Disaster / Single Reactor Ignition) castable NEXT round: claim the initiative so it
//                 goes first, and hold units out of its arena (owner ruling; Ninin's R5/R6 claims). Guard: bot_wipeinit_test.php.
//   wipedraw    — no such wipe in hand, but some in the deck: claim on the 2-draw chance x the damage it stops (owner: "they claim in
//                 hopes of drawing a wipe" — Reprint_Cad, every round). Guard: bot_wipeinit_test.php.
//   searchpick  — a deck-search pick never takes a unique already in play or twice (-1 each); the bridge now also OFFERS the maximal
//                 picks its 40-candidate cap cut off (ungated). Admiral Ackbar's flip: 4 ships for 5 (Ninin R4); 51/125 traced bot
//                 searches took fewer ships than possible, 17 broke uniqueness. Guard: bot_searchpick_test.php.
//   ndsetup     — a hit leaving 1 HP is a 'setup' when a ready undeployed Hemlock (a resource ready, target un-Weakened) or Talzin (with
//                 the Force) can finish it: No Disintegrations -> Hemlock (owner ruling). Guard: bot_finisher_test.php.
//   onattackfinish — the same when a READY deployed Hemlock / Talzin will attack (On Attack Weakness / -1/-1): Ninth Sister 1/1/1
//                 then Hemlock's token (Reprint_Cad R6). Guard: bot_finisher_test.php.
//   stacklethal — a Support flip turn plans its stacked-buff attacker by its projected SINGLE attack (+ Plot buffs left + its own On
//                 Attack boost); a lethal one goes first. Three Jar Jars on Mando's N-1 (Ninin vs RussellHoskins R5). Guard:
//                 bot_stacklethal_test.php.
const SWU_BOT_PART36_FEATURES = ['upgradecost', 'defeatimmune', 'disclosereserve', 'condemnfore', 'wipeinit', 'wipedraw', 'searchpick',
                                 'ndsetup', 'onattackfinish', 'stacklethal'];

// Part 37 (2026-10-05): 'actionclock' — "am I winning the race?" (SWUBotIsRacing) counted in ACTIONS: every unit attacks once a round,
// largest first, the sides alternating, the initiative holder first. Owner's "tall beats wide per action" (Ahsoka Yellow vs Han Solo
// JTL Red); measured first — 7.9% of race decisions disagree, the action verdict names the winner 79% of the time there. Owner chose
// a shipped feature (option A). Guard: SWUSim/DevTools/tests/bot_actionclock_test.php.
// 'blockerfirst' — PROMOTED from a proposal 2026-10-05 for every style (owner: "promote it for all. hyperaggro may not even play
// sentinels"): behind on bodies, a unit goes down before attacking. Supersedes the midrange-only 2026-09-23 Sentinel/power ruling
// ('mgsentinel' stays a proposal). Fidelity screen: four styles toward real (+1.2 to +2.5 pp). Guard: bot_blockerfirst_test.php.
// 'wipekeepaggro' — against an aggro leader the control tiers keep a relevant wipe (tier 9) instead of resourcing it as a dead 7-drop
// before the board fills: owner (Krennic Splash questionnaire 2026-10-06) "never the wipes"; SRI was resourced 19 of 20 traced games
// vs Ahsoka Blue. Guard: bot_wipekeepaggro_test.php.
// 'epicwipe' — rule 5 may open a wipe castable only through the base's aspect-waiver Epic (Krennic Splash: SRI 10 -> 8, owner "5R + 3C or
// 6R + 2C"), judged on the board by rule 5's own tests. Guard: bot_epicwipe_test.php.
const SWU_BOT_PART37_FEATURES = ['actionclock', 'blockerfirst', 'wipekeepaggro', 'epicwipe', 'shieldtrader'];
// Part 38 (2026-10-06): CURVE VALUE — a card in hand priced against its cost from the owner's prices (BotCurveValue.php,
// spec docs/superpowers/specs/2026-10-05-swusim-curve-value-design.md). Measured ONE AT A TIME, 14,040 games each, fresh
// 'cv' seeds, 27-deck gate (docs/superpowers/research/curve-value/2026-10-06-measurement.md):
//   'curveplay'     — play score + W['curve'] × surplus: NEW 50.8% (p .032), hard control +4.0pp; no deck hurt.
//   'curveresource' — surplus breaks resourcing ties:  NEW 50.7% (p .055), hard control +3.0pp; no deck hurt.
//   'curvemull'     — the curve-value mulligan:          NEW 51.0% (p .009), hyper aggro +2.9, midrange +2.2; no deck hurt.
//                     ⚠ Hard control mulligans 58% of hands under it (hyper 17%) — flagged for the owner.
// Owner shipped all three, no combined run ("Ship all 3, no combined run"). Guards: bot_curvevalue_test.php, bot_curvedecisions_test.php.
const SWU_BOT_PART38_FEATURES = ['curveplay', 'curveresource', 'curvemull'];
// Part 39 (2026-10-06): Krennic (LAW) Blue — the owner's questionnaire (docs/superpowers/research/2026-09-premier-meta/2026-10-06_deck_krennic-blue.md).
//   wallkeep    — vs an aggro leader, control never resources its Sentinel wall (Koska included); vs space aggro it keeps Lawbringer.
//   traskreturn — Trask Walker takes back the best answer (Chimaera) and returns it to hand (was "bottom + heal 3" 225/225).
//   deploystrike — Krennic deploys only when his When Deployed strike (another friendly unit's power) kills (was R6 in 305/366).
//   wallfirst    — R1-4 vs an aggro leader, control plays its first Sentinel in an arena before anything else (R2 Gideon: 16/38).
const SWU_BOT_PART39_FEATURES = ['wallkeep', 'traskreturn', 'deploystrike', 'wallfirst'];
// Part 40 (2026-10-06): the Krennic-vs-aggro AUTOPSY (docs/superpowers/research/2026-09-premier-meta/2026-10-06_autopsy_krennic-vs-aggro.md) +
// the owner's rulings on it. The owner ends R1 with 1.2 bodies and R2 with 2.2; the bot with 0.2 and 0.9.
//   discountfirst — a static cost reducer (the Krennic unit) goes before the card it makes fit (Krennic + Ant Droid: 28/28 played backwards).
//   keepbody      — R1-3 vs aggro, Krennic's Credit Action keeps my only unit unless its When Defeated draws ("Sac it if it draws").
//   earlycredits  — R1-3 vs aggro, banked Credits may pay for a cheap body ("Spend by R3, then bank"); 'bigcredit' holds from R4.
const SWU_BOT_PART40_FEATURES = ['discountfirst', 'keepbody', 'earlycredits'];
// Part 41 (2026-10-07): NininTCG Hemlock Red vs a Wicket Green guest (human game) — owner: "build them all".
//   phaseexpiry  — a "+N/+N for this phase" buff expires and the damage stays: HK-47 into a C-3PO-buffed Cassian is a trade,
//                  not a loss (R4); my own buffed attacker that "survives" dies at the phase end. Guard: bot_phaseexpiry_test.php.
//   observertax  — an attack that loses my unit while the opponent controls an HK-47 ("When an enemy unit is defeated: Deal N
//                  damage to its controller's base") costs my base N; a death that pings my base to 0 is never made (R11).
//                  Guard: bot_observertax_test.php.
//   uniquerefresh — a second copy of a unique unit over a WORN copy (damage, Weakness tokens) is a refresh: a Weakness-ed copy is
//                  no longer read as healthy, and the 'picks' clash charge shrinks by the copy's wear (R7: Logray, Wicket).
//                  Guard: bot_uniquerefresh_test.php.
//   budgetsetup  — with a budget wipe in hand (ASH_053 Pre Vizsla: "…non-leader units with a total of N or less remaining HP"),
//                  castable within two rounds, a Weakness token is also worth part of what it adds to the wipe's best kill set
//                  (R5-R7: three tokens took Luminara to 1 HP; Pre Vizsla then took three units). Guard: bot_budgetsetup_test.php.
//   dudheal      — the dud gate counts the HP a removal event heals on MY base (Lost and Forgotten: "…heal 3 damage from your
//                  base"); it was held on a 2-cost Logray however damaged the base (R8 replay). Guard: bot_dudheal_test.php.
//   leaderdraw   — an attack into a pricier unit is worth a draw while a ready, undeployed leader reads "When a friendly unit
//                  attacks a unit that costs more than it: … draw a card" (HMW_014 Wicket; R6 C-3PO into the Commando).
//                  Guard: bot_leaderdraw_test.php.
const SWU_BOT_PART41_FEATURES = ['phaseexpiry', 'observertax', 'uniquerefresh', 'budgetsetup', 'dudheal', 'leaderdraw'];

function SWUBotFeatureList(): array {
    return array_merge(['splits', 'targeting', 'tags2', 'keep', 'stop', 'enablers', 'picks'], SWU_BOT_PART3_FEATURES,
                       SWU_BOT_PART4_FEATURES, SWU_BOT_PART5_FEATURES, SWU_BOT_PART6_FEATURES,
                       SWU_BOT_PART7_FEATURES, SWU_BOT_PART8_FEATURES, SWU_BOT_PART9_FEATURES,
                       SWU_BOT_PART10_FEATURES, SWU_BOT_PART11_FEATURES,
                       SWU_BOT_PART12_FEATURES, SWU_BOT_PART13_FEATURES,
                       SWU_BOT_PART14_FEATURES, SWU_BOT_PART15_FEATURES,
                       SWU_BOT_PART16_FEATURES, SWU_BOT_PART17_FEATURES,
                       SWU_BOT_PART18_FEATURES, SWU_BOT_PART19_FEATURES, SWU_BOT_PART20_FEATURES, SWU_BOT_PART21_FEATURES, SWU_BOT_PART22_FEATURES, SWU_BOT_PART23_FEATURES, SWU_BOT_PART24_FEATURES, SWU_BOT_PART25_FEATURES, SWU_BOT_PART26_FEATURES, SWU_BOT_PART27_FEATURES, SWU_BOT_PART28_FEATURES, SWU_BOT_PART29_FEATURES, SWU_BOT_PART30_FEATURES, SWU_BOT_PART31_FEATURES, SWU_BOT_PART32_FEATURES, SWU_BOT_PART33_FEATURES, SWU_BOT_PART34_FEATURES, SWU_BOT_PART35_FEATURES, SWU_BOT_PART36_FEATURES, SWU_BOT_PART37_FEATURES, SWU_BOT_PART38_FEATURES, SWU_BOT_PART39_FEATURES, SWU_BOT_PART40_FEATURES, SWU_BOT_PART41_FEATURES);   // part 2, then 3-41
}

// Named groups a variant can switch off together: '@no-p3' = the stack as it was after part 2 (run 5);
// '@no-p4' = the stack before the 2026-09-18 anti-control features.
function SWUBotFeatureGroups(): array {
    // 'p3a'-'p3d' bisect part 3: it has only ever been measured as ONE block ('@no-p3' = −608 pooled on the
    // 2026-09-20 panel screen, but +19 for SOFT CONTROL — so one of the 16 may be HURTING control).
    // 'wk' = everything shipped in the week of 2026-09-18/20, for re-measuring the random-play benchmark.
    $p3 = SWU_BOT_PART3_FEATURES;
    return ['p3' => $p3, 'p4' => SWU_BOT_PART4_FEATURES, 'p5' => SWU_BOT_PART5_FEATURES,
            'p6' => SWU_BOT_PART6_FEATURES, 'p7' => SWU_BOT_PART7_FEATURES, 'p8' => SWU_BOT_PART8_FEATURES,
            'p9' => SWU_BOT_PART9_FEATURES, 'p10' => SWU_BOT_PART10_FEATURES, 'p11' => SWU_BOT_PART11_FEATURES,
            'p12' => SWU_BOT_PART12_FEATURES, 'p13' => SWU_BOT_PART13_FEATURES,
            'p14' => SWU_BOT_PART14_FEATURES, 'p15' => SWU_BOT_PART15_FEATURES,
            'p16' => SWU_BOT_PART16_FEATURES, 'p17' => SWU_BOT_PART17_FEATURES, 'p18' => SWU_BOT_PART18_FEATURES, 'p19' => SWU_BOT_PART19_FEATURES, 'p20' => SWU_BOT_PART20_FEATURES, 'p21' => SWU_BOT_PART21_FEATURES, 'p22' => SWU_BOT_PART22_FEATURES, 'p23' => SWU_BOT_PART23_FEATURES, 'p24' => SWU_BOT_PART24_FEATURES, 'p25' => SWU_BOT_PART25_FEATURES, 'p26' => SWU_BOT_PART26_FEATURES, 'p27' => SWU_BOT_PART27_FEATURES, 'p28' => SWU_BOT_PART28_FEATURES, 'p29' => SWU_BOT_PART29_FEATURES, 'p30' => SWU_BOT_PART30_FEATURES, 'p31' => SWU_BOT_PART31_FEATURES, 'p32' => SWU_BOT_PART32_FEATURES, 'p33' => SWU_BOT_PART33_FEATURES, 'p34' => SWU_BOT_PART34_FEATURES, 'p35' => SWU_BOT_PART35_FEATURES, 'p36' => SWU_BOT_PART36_FEATURES, 'p37' => SWU_BOT_PART37_FEATURES, 'p38' => SWU_BOT_PART38_FEATURES, 'p39' => SWU_BOT_PART39_FEATURES, 'p40' => SWU_BOT_PART40_FEATURES, 'p41' => SWU_BOT_PART41_FEATURES,
            'p3a' => array_slice($p3, 0, 4), 'p3b' => array_slice($p3, 4, 4),
            'p3c' => array_slice($p3, 8, 4), 'p3d' => array_slice($p3, 12, 4),
            // p3d bisected one feature at a time (2026-09-21): '@no-p3d' measured +82 for SOFT CONTROL (Maul,
            // p<.0001) while costing hard control −66, so one of these four is hurting a control-piloted deck.
            // Prime suspect 'flavourrank': Maul carries the 'tempo' flavour, whose rank shift moves a deck piloted
            // as SOFT control one step further, i.e. it plays as HARD control.
            'p3d1' => ['plotdeploy'], 'p3d2' => ['wipekeep'], 'p3d3' => ['flavourrank'], 'p3d4' => ['bombtiming'],
            'wk' => array_merge(SWU_BOT_PART4_FEATURES, SWU_BOT_PART5_FEATURES, SWU_BOT_PART6_FEATURES, SWU_BOT_PART7_FEATURES)];
}

// ── DECISION-CLASS RANDOMISATION ("@rand:<class>") ───────────────────────────────────────────────────
// THE DIAGNOSTIC THE PROJECT HAS NEVER RUN. Under RANDOM play control beats aggro 51.5%; under the shipped stack
// ~31% (memory `bot-heuristics-cause-the-anti-control-bias`). Three sessions tried to localise those ~20 points —
// layer-2 rules inert, guides inert, the weight model has no control-side lever — and the core scorer holds the
// residue. Nobody has asked WHICH KIND OF DECISION it is bad at.
// Each class replaces the stack's pick with a UNIFORM choice among the candidates OF THAT SAME CLASS, and nothing
// else. If control plays BETTER with random choices in a class, that heuristic actively mis-serves control — which
// is a bug with an address, not a distributed bias.
//   attacktarget — which enemy unit / base an attack hits      attacker — which of my units attacks
//   play         — which card I play from hand                 resource — which cards I put into resources
//   ability      — which leader/unit/base ability I use        tempo    — pass vs take the initiative
function SWUBotRandomClassList(): array {
    // 'resourceopen' / 'resourceregroup' split 'resource' (2026-09-21): resourcing is random-equivalent for control,
    // and the owner's rulings need to know WHICH resourcing decision — the opening two cards, or the regroup pick.
    return ['attacktarget', 'attacker', 'play', 'resource', 'ability', 'tempo', 'resourceopen', 'resourceregroup'];
}

// The class of a candidate, for the randomiser. Mirrors SWUBotActionKind plus the two decision prompts.
function _SWUBotDecisionClass(array $ctx, array $action): string {
    if (($ctx['kind'] ?? '') === 'decision') {
        $tip = strval($ctx['tooltip'] ?? '');
        if ($tip === 'Choose_an_attack_target') return 'attacktarget';
        // BOTH resourcing prompts. ⚠ Until 2026-09-22 this matched only 'to_resource', i.e. the OPENING
        // "Choose_2_cards_to_resource" — the per-round "Resource_up_to_1_card" (~5x more frequent) was never
        // classified, so every '@rand:resource' arm randomised the opening pick alone. Caught when '@rand:resourceregroup'
        // changed exactly 0 of 6,000 games (bot-sweeps/2026-09-21_softcontrol_prereg.md).
        if (stripos($tip, 'to_resource') !== false || str_starts_with($tip, 'Resource_up_to')) return 'resource';
        return '';
    }
    switch (SWUBotActionKind($action)) {
        case 'attack': return 'attacker';
        case 'play': return 'play';
        case 'leader-ability': case 'unit-action': case 'base-epic': case 'deploy': return 'ability';
        case 'pass': case 'initiative': return 'tempo';
    }
    return '';
}

// The active "@rand:<class>" for this decision, or ''. Keyed "rand:<class>" in the disabled set, like "w:" and "try:".
function SWUBotActiveRandomClass(): string {
    foreach ($GLOBALS['SWUBotDisabledFeatures'] ?? [] as $d) {
        if (is_string($d) && str_starts_with($d, 'rand:')) return substr($d, 5);
    }
    return '';
}

// Replace $pick with a uniform choice among the candidates of the SAME class. Deterministic per game and
// counter-neutral: EngineRandomInt() only (never rand()), with $gRandomCounter restored — the 'random' chooser's
// header in BotHeuristic.php explains why a consuming draw would break the no-op detector.
function SWUBotRandomiseClass(array $ctx, ?array $pick): ?array {
    $class = SWUBotActiveRandomClass();
    if ($class === '' || $pick === null) return $pick;
    // The two resourcing sub-classes: the opening pick (CreateGame's "Choose_2_cards_to_resource") vs every later one.
    $base = $class;
    if ($class === 'resourceopen' || $class === 'resourceregroup') {
        $opening = strval($ctx['tooltip'] ?? '') === 'Choose_2_cards_to_resource';
        if (($class === 'resourceopen') !== $opening) return $pick;
        $base = 'resource';
    }
    if (_SWUBotDecisionClass($ctx, $pick) !== $base) return $pick;
    $same = array_values(array_filter($ctx['actions'], fn($a) => _SWUBotDecisionClass($ctx, $a) === $base));
    if (count($same) < 2 || !function_exists('EngineRandomInt')) return $pick;
    $counter = GetDeterministicRandomCounter();
    try { $i = EngineRandomInt(0, count($same) - 1); } finally { SetDeterministicRandomCounter($counter); }
    return $same[$i] ?? $pick;
}

// ── RULE switches (bisection instrumentation, added 2026-09-18) ──────────────────────────────────────
// The layer-2 rules of BotRules.php, switchable one at a time as "@no-rule:<name>".
//
// Why: measured 2026-09-18, the bots' anti-control bias splits in half — ~12 points live in the named
// features above (reachable with '@base') and ~11 points live in the CORE stack, the layer-2 RULES plus the
// fallback scorer, which had no switch at all and so could not be bisected. Under random play control decks
// beat aggro 51.5%; with every named feature off they are still only 40.4%. See the OTMTCGE memory
// `bot-heuristics-cause-the-anti-control-bias`.
//
// These are deliberately NOT in SWUBotFeatureList(): '@base' means "the stack before the Phase-1b features"
// and must keep meaning exactly that, or every measurement taken against it silently changes meaning.
// Rules are keyed "rule:<name>" so a rule and a feature can never collide in the disabled set.
function SWUBotRuleList(): array {
    if (!function_exists('SWUBotRulesBeforeFilter')) return [];   // BotRules.php loads after this file
    return array_merge(array_keys(SWUBotRulesBeforeFilter()), array_keys(SWUBotRulesAfterFilter()));
}

// ── WEIGHT PROBES (added 2026-09-18) — "@w-<probe>" ──────────────────────────────────────────────────
// Scale named clusters of the fallback weight table (SWUBotWeights, BotArchetypes.php).
//
// Why: the anti-control bias decomposed on 2026-09-18 into components that are each doing their job —
// all 12 layer-2 rules exonerated (max 1.8), both guides exonerated (max 2.5), and 'splits'/'targeting'
// shown by their ONE-SIDED arms to be correctly-played style tools rather than defects. That leaves the raw
// WEIGHT MODEL as the only unprobed part of the ~11-point core residual, and the mechanism points at it:
// the stack halves game length (13 rounds -> 7) and the components that most help aggro are the tempo ones.
// HYPOTHESIS: the value model's horizon is too short — it scores damage-now and kill-now and underweights
// card advantage, development and answers. These probes test exactly that, and nothing else.
//
// Probes are ENUMERATED, not free-form factors, because chooser profiles are pre-registered by name
// (BotHeuristic.php) and a continuous parameter cannot be. Keyed "w:<probe>" in the disabled set so a probe
// can never collide with a feature, rule or guide. Deliberately NOT in SWUBotFeatureList(): '@base' must go
// on meaning "the stack before the Phase-1b features".
const SWU_BOT_WEIGHT_PROBES = [
    // the long game: card advantage, staying alive, building a board, answering threats
    'longgame-up' => ['draw' => 2.0, 'heal' => 2.0, 'develop' => 2.0, 'removal' => 2.0],
    // tempo and reach: damage that closes a game rather than winning a board
    'tempo-down'  => ['base' => 0.5, 'chip' => 0.5, 'damage-enemy-base' => 0.5, 'damage-enemy-unit' => 0.5],   // were 'burn' / 'damage' (retired 2026-10-01)
    // THE EMPIRICAL NULL for a multi-arm screen. A true no-op cannot serve: the bots are deterministic and arms share
    // seeds, so it returns zero discordant games and p=1 by construction (measured 2026-09-20, the 'placebo' arm).
    // These nudge ONE weight by ±3% — enough to flip close calls, far too small to be a strategy — so their paired
    // results sample the NOISE at this sample size, and every other arm is read against that spread. Two of them,
    // because one draw bounds the noise poorly.
    'jitter-up'   => ['develop' => 1.03],
    'jitter-down' => ['develop' => 0.97],
    // Play units EARLIER: the loss-mining signature was a board deficit of 1.4-2.3 units by rounds 3-5 (2026-09-19).
    'develop-up'  => ['develop' => 2.0],
    // both at once — the full horizon shift
    'horizon'     => ['draw' => 2.0, 'heal' => 2.0, 'develop' => 2.0, 'removal' => 2.0,
                      'base' => 0.5, 'chip' => 0.5, 'damage-enemy-base' => 0.5, 'damage-enemy-unit' => 0.5],
    // Is the TRADE PREFERENCE paying for itself? Block 2 of the owner's 100-game human-vs-bot run
    // (2026-09-23): a hardcontrol Luke ASH_005 went 0W/24L into an Ahsoka ASH_009 go-wide deck. Against
    // block 1's midrange arm on the SAME matchup, same human, only the style changed —
    //     attacks at BASE   79% -> 57%      damage DEALT  14.4 -> 8.2 mean (permutation p = 0.0008)
    //     damage TAKEN by round 5, cumulative   28.9 -> 30.2
    // so the extra trading bought NO defence at all while halving the bot's own output. The suspect is
    // this table: hardcontrol prices 'kill' at 1.50 against a FLAT 'base' of 0.60, a 2.5:1 preference for
    // trading applied before any board state is read. 'kill-down' moves the ratio to 1.5:1; 'kill-base'
    // pushes it to ~1.15:1 by paying the swing more as well.
    // ⚠ GLOBAL, like every probe — SWUBotWeights() applies it to all five archetypes, so these also move
    // aggro and midrange. That is intended for a first screen: strength_report.py splits the result per
    // style, so "helps control, hurts aggro" is visible and fails the "raise the weak, never lower the
    // strong" ruling. A style-scoped version is the follow-up if the screen says the direction is right.
    'kill-down'   => ['kill' => 0.6],
    'kill-base'   => ['kill' => 0.6, 'base' => 1.3],
];

// WEIGHT FLOORS ("@w-<probe>", same namespace as the multipliers above). A MULTIPLIER cannot switch on a weight
// that is zero — maxUnits is 0.00 for midrange and both control archetypes — and scaling 0.05 to a meaningful
// initiative value would need a factor of 12. A floor states the value plainly: max(current, floor).
const SWU_BOT_WEIGHT_FLOORS = [
    // The initiative is valued at 0.05 for all five archetypes — below a single point of base damage, so the bot
    // takes it only when a rule tells it to (owner Q16, 2026-09-18). 0.60 = one point of base damage.
    'initiative-up' => ['initiative' => 0.60],
    // Going wide is worth 4.00 to hyper aggro and 3.00 to soft aggro, and exactly 0.00 to midrange, soft and hard
    // control — they never value a second body for its own sake.
    'maxunits-on'   => ['maxUnits' => 2.00],
];

function SWUBotWeightProbeList(): array {
    return array_merge(array_keys(SWU_BOT_WEIGHT_PROBES), array_keys(SWU_BOT_WEIGHT_FLOORS));
}

// The FLOOR map of the probe active for this decision, or null. Read by SWUBotWeights() after the multipliers.
function SWUBotActiveWeightFloor(): ?array {
    foreach ($GLOBALS['SWUBotDisabledFeatures'] ?? [] as $d) {
        if (is_string($d) && str_starts_with($d, 'w:')) return SWU_BOT_WEIGHT_FLOORS[substr($d, 2)] ?? null;
    }
    return null;
}

// ── PROPOSALS (added 2026-09-18) — "@try-<name>", the MIRROR of a feature switch ──────────────────────
// A feature in SWUBotFeatureList() is shipped behaviour and defaults ON ("@no-<x>" turns it off). A PROPOSAL
// is candidate behaviour that defaults OFF and is turned on only by "@try-<name>", so it can be measured
// before anyone decides to ship it. Nothing in the running product enables one.
//
// Why: the anti-control deficit is a SURVIVAL problem — control's win rate conditional on reaching round 8 is
// already 53.4% (the real-world number) and it reaches round 8 in only 32% of games (OTMTCGE memory
// `control-loses-by-not-reaching-round-8`). These encode owner rulings from the 2026-09-18 Q&A aimed at early
// survival. Keyed "try:<name>" in the active-variant set, like "w:" and "rule:".
// ⚠ SHIPPED proposals LEAVE this list and become FEATURES (SWU_BOT_PART4_FEATURES above) — a name in both would
// make SWUBotProposalOn() false forever and silently switch the shipped behaviour off. Shipped 2026-09-18:
// 'sentinelkeep' and 'wipethreat' (2026-09-18), 'threathold' (2026-09-19). Their history stays in the feature comment.
const SWU_BOT_PROPOSALS = [
    // Owner 2026-09-18 (Q7 / 5.7): "control wants to minimize damage to below 50-60% of their base total by the
    // 6R/7R turn. if they keep it below 40% then they are performing really well." While a control seat is OVER
    // that pace it plays defensively: trades and removal up, healing up, base damage down. Control wing only.
    'dmgbudget',
    // Owner 2026-09-18 (Q13/Q14): "use restricted removal for cheap stuff early on … Crushing Blow only works on
    // 2-cost non-leader units. so late game, it's an auto resource … less-restricted removal is typically held
    // … No Glory Only Results is good against bombs. same with Lost and Forgotten. save it for bombs."
    // Restriction read from PRINTED TEXT (owner, 5.5). Control wing only.
    // ⚠ MEASURED −22 (p=0.011): its cost-only hold froze No Glory / Lost and Forgotten against aggro. Kept
    // byte-for-byte so that result stays reproducible; 'threathold' + 'restrictedearly' are its split.
    'earlyremoval',
    // ('threathold' — earlyremoval's threat-aware hold — was CONFIRMED and SHIPPED 2026-09-19 as feature group 'p5'.)
    // earlyremoval's other two halves alone (restricted early + late auto-resource), to learn whether they
    // contributed to its loss.
    'restrictedearly',
    // Owner 2026-09-18 (Q16 / 5.2): take the initiative when it lets control remove a threat BEFORE it swings — worth
    // more than a card play or an attack. Rule 'initiative-for-answer' (BotRules.php). Control wing only.
    'initiative',
    // 2026-09-19 loss mining (540 traced control-vs-aggro games; owner rulings on real lost positions):
    'sentinelpot',   // bug: one Sentinel was modelled as blocking its whole arena (BotEvaluator.php)
                     // 2026-10-03 safety sweep: 29:19 AGAINST (p .19), Krennic 12:5 (p .14) — HELD by the owner ("hold that one").
    'freekill',      // Q1: always take a kill-survive on a READY enemy unit (BotRules.php)
    'holdanswers',   // Q2-D: keep space answers vs a space-heavy board; no value for idle heal/Advantage (Resourcing/Fallback)
    'unitvalue',     // the value algorithm: stats-first, keywords, upgrades, When Defeated in context (BotEvaluator.php)
    // 2026-09-19, second batch — follow-ups to that batch's "no effect" verdicts:
    'unitvalue2',    // 'unitvalue' + the printed ability premium (cost − the fitted price of the body)
    // ('shrinkfirst' was CONFIRMED and SHIPPED 2026-09-20 as feature group 'p6'; its history is in the feature comment.)
    'shrinkfirst2',  // 'shrinkfirst' with the threat bar at 2 power instead of 3 — MEASURED −88 (p .015): the 3-power bar wins
    // ── 2026-09-20 overnight screen (5-archetype panel). Everything shipped so far was measured on CONTROL seats
    // only, because every arm to date was one-sided on a control deck. These ask whether the gates are right.
    'placebo',         // NOTHING reads this: "@try-placebo" plays exactly like the default. It measures the
                       // false-positive floor of a 13-arm screen instead of assuming it.
    'shrinkfirstall',  // 'shrinkfirst' (p6, control-only) for every archetype
    'threatholdall',   // 'threathold' (p5, control-only) for every archetype
    'sentinelkeepall', // 'sentinelkeep' (p4, control-only) for every archetype
    'keepequal',       // control's key-card keep bonus 50 -> 150, the same as every other archetype
    // ── 2026-09-20 behaviour screen. THE BOT HAS NEVER MULLIGANED: BotFallback's YESNO branch scores "keep"
    // above "mulligan" unconditionally (the spec left mulligans to the learned layer, which never learned them),
    // so every game starts from an unexamined opening hand. Three rules for what a keepable hand is:
    'mullnocast',      // fewer than 2 cards castable by round 2 (cost <= 3)
    'mullcurve',       // no card costing <= 2, or 3+ costing >= 6
    'mullstyle',       // per archetype: the aggro wing needs an early drop, control needs an answer
    // Behaviour arms — the family every shipped win came from (sequencing and keeping, not valuation):
    'killfirst',       // take a kill-and-survive attack before a base attack, within the turn
    'blockerfirst',    // behind on units: play a body before attacking
    'doomedtie',       // HELD 2026-10-06 (measured HARMFUL): doomed units sacrificed cheapest-first (0.5 + 0.01 x value) instead of tying
                       // at a flat 0.5, where the first listed went — Krennic's Credit Action sacrificed the Director Krennic unit over a
                       // Spy token (17 of 60 traced games vs Ahsoka Blue); the owner never sacrifices it. With 'wdability', on Krennic
                       // Splash's 15 screen pairs: 34.3% vs 37.0%, paired 1 game won / 9 lost (sign p=.02). Guard: bot_doomedtie_test.php.
    'wdability',       // HELD 2026-10-06 (same measurement): a sacrifice's When Defeated payback is the ABILITY ("When Defeated:"), not the
                       // words — JTL_032's "a unit that has a 'When Defeated' ability" priced it as its own fodder.
    'tradewhenbehind', // behind on units: an even trade is worth taking (owner Q10, made conditional)
    // ('leaderrisk' was SHIPPED 2026-10-03 in feature group 'p28' — see the Part 28 comment.)
    'removalready',    // spend removal on READY enemies; an exhausted one cannot attack this round
    'playsurvivor',    // prefer units that survive the opponent's best attacker
    'sentineltiming',  // play a Sentinel late in the round, so it guards their turn
    // ('flavourcap' — cap the flavour rank shift below the control wing — was DELETED 2026-09-23 with the shift it
    //  capped: SWU_BOT_FLAVOUR_RANK_SHIFT is now empty, so there is nothing left to cap. See BotFlavours.php.)
    // 2026-09-22 — built from the OWNER'S RESOURCING RULINGS (bot-sweeps/2026-09-21_resourcing_rulings.md), first
    // focused block: soft control vs Vader Yellow.
    'resourcing2',     // control-wing resourcing as the owner's ordered tiers (rulings 1-10, confirmed precedence)
    'krennicramp',     // Krennic LAW_008: before the flip, play a cheap unit and sacrifice it to the leader for a Credit
    // ('aurathreat' was SHIPPED 2026-10-03 in feature group 'p28' — see the Part 28 comment.)
    // 2026-09-22 — resourcing2 was CONFIRMED on Thrawn DV vs Vader (+104/2,000) but FAILED safety: Dedra −142, Piett −46.
    // It read "aggressive" off the BOARD, so a hard-control mirror counted, and it dropped the ruling's exceptions.
    // ('resourcing3', its fix, was SHIPPED 2026-09-22 as feature group 'p8' — its history is in the feature comment.)
    'krennicplan',     // owner rulings K1-K3 (Krennic vs Vader): bank Credits for 7+ cards, HSD as soon as it saves
                       // the game, attack before sacrificing, Mercenary sacrificed the round it is played
    // krennicplan LOST its canary (−44 / 1,000; base damage dealt 14.3 → 7.8). Its parts, for the split (BotRules.php
    // SWU_BOT_KRENNIC_PLAN_ARMS): K1 banking only, K2 only, K3 only, and all but the forced ramp.
    'kpbank', 'kphsd', 'kporder', 'kpnoramp',
    // 2026-09-23 — the MIDRANGE rulings (bot-sweeps/2026-09-23_midrange_rulings.md). The ablation found every
    // feature group since p4 changes ZERO games for a midrange seat: they are control-gated or need cards the deck
    // does not have. These are midrange's first rules of its own.
    // ('mgbuff' was SHIPPED 2026-10-03 in feature group 'p28' — see the Part 28 comment.)
    // ⛔ MEASURED HARMFUL 2026-09-24 — DO NOT SHIP, DO NOT RE-SCREEN. Kept only so the result reproduces.
    // Midrange lever screen (8,000 games, FOCUS on the 5 midrange decks): midrange 40.27% against jitter nulls
    // at 41.50 / 41.39 — paired McNemar 97:151 and 110:159, p = 0.0008 and 0.0034, i.e. significantly WORSE
    // than doing nothing. It also LOST head to head to the opposite-direction 'mgkill' 233:327 (p = 0.0001),
    // which settles the direction: midrange wants a LOWER kill weight, not a higher one.
    'mgtrade',         // while BEHIND ON BOARD POWER, a kill also earns the damage it prevents (target power x base
                       // rate), so killing a cheap 3-power body beats swinging at the base. 73% of a midrange bot's
                       // attacks went at the base while it lost the board (BotFallback.php _SWUBotThreatRemoved)
    'mgsentinel',      // ruling 2/4: keep Sentinels against aggro (3+ power against anyone else), and play one
                       // ahead of a bigger body against aggro — the only body midrange plays before attacking
    'mgremoval',       // ruling 3: shrinkfirst's shape for midrange, barred on the target's COST (5+), not its power
    // ('mgcost' was SHIPPED 2026-10-03 in feature group 'p28' — see the Part 28 comment.)
    // ('mgkeep' SHIPPED 2026-09-23 as feature group 'p11' — its measurements are in the feature comment.) Its two
    // clauses stay as arms: the split found the halves are NOT separable (+4.8 body alone, +0.8 duplicate alone,
    // +8.4 together), and the owner intends to re-measure that on the new bot loop.
    'mgkeepbody',      //   the efficient-body keep alone
    'mgkeepdup',       //   the spare-duplicate resource alone (no body exception — that IS the isolation)
    'mgmull',          // ruling 5: the matchup keep test. The bot has NEVER mulliganed; all three traced Luke ASH
                       // openings were mulligans
    'krennicscript',   // The owner's WRITTEN game plan for Krennic Blue Splash vs aggro (2026-09-28): T1 a sac body,
                       // T2 a Sentinel, T3 another sac body, T4 trades/removal (left to the fallback), T5 after the
                       // opponent flips, the mass defeat via 6R+2C / 7R+1C.
                       // ⚠ 'krennicplan' could never do this: _SWUBotKrennicPlanOn gates on the opponent being a
                       // SPACE deck (it finishes on Hyperspace Disaster), so it is inert against ground go-wide —
                       // which is why @try-krennicplan changed the sweep by nothing at all.
                       // Needs 'aspectwaiver' to be useful: LAW_044 is cost 10 here, and 6R+2C is 8.
                       // Guard: measured as the 'krennicline' group.
    'creditvalue',     // A Credit token is worth the card it BRINGS INTO REACH (owner ruling 2026-09-28). Nothing in
                       // the value path read Credits at all: _SWUBotBoardSignature tracks units/bases/hand SIZE/
                       // resource COUNT and never Credits, so "[Exhaust, defeat a friendly unit]: Create a Credit"
                       // scored W['ability'] - sacrifice with the gain at ZERO. Traced on the Krennic vs Ahsoka
                       // board: -2.6, and the ramp Action was taken 12 of 78 times (the 12 being the boards where
                       // LAW_159 Expendable Mercenary was the fodder, already priced at -1.0).
                       // ⚠ A PROPOSAL, NOT SHIPPED, because it FAILS the owner's bar. Measured 24 games vs
                       // ahsoka-tano_ash_blue: win rate 0/24 either way, and base damage dealt FELL 7.9 → 6.4. It raises
                       // Credit-engine use (12/78 → 16/60) but cannot reach the line it exists for — LAW_044 Single
                       // Reactor Ignition costs 10 unwaived against a max capacity of 6, so the missing half is
                       // Daimyo's Palace's aspect waiver (the "save the once-per-game unlock" gap), not valuation.
                       // Measure the two TOGETHER before shipping either.
                       // Guard: SWUSim/DevTools/tests/bot_creditvalue_test.php.
    'landomill',       // owner 2026-09-23, Lando LAW_018: mill MY deck pre-flip (guaranteed Credit → bombs); once the
                       // leader has flipped and come back, mill THEIRS on a spare resource, or skip it
    'piettcheat',      // owner: "cheat out capital ships" — value a leader's discounted play-from-hand Action
                       // (Piett JTL_005) as the best card it can play, and pick the best card at its prompt.
                       // Measured +7 alone (11/1,000 games changed), 0 on top of resourcing3: kept OFF (owner 2026-09-22).
                       // ⚠ contradicts the owner's 2026-09-13 resourcing ruling; run with the owner's OK to
                       // gather data (2026-09-20). Memory `bot-heuristics-cause-the-anti-control-bias` calls this
                       // the most actionable lead: control resources its own answers before filler.
    // ('buffattack' was SHIPPED 2026-09-20 as feature group 'p7' — its history is in the feature comment.)
    // Value a HAND card by what it is worth ON THE CURRENT BOARD, not by its printed stats — resourcing AND
    // play scoring (SWUBotContextSurplus, BotEvaluator.php). Block 3 of the owner's run: the hyperaggro seat
    // buried JTL_115 Clone Combat Squadron 17 times at an average EFFECTIVE power of 5.9 (peak 9) and played
    // it 3 times at 4.3 — it buries the card when it is big and plays it when it is small, because the aggro
    // wing resources by `-$cost` and _SWUBotPlayValue prices a unit by COST. Owner 2026-09-24.
    // ('ctxpower' was SHIPPED 2026-10-03 in feature group 'p28' — see the Part 28 comment.)
    // CARD VALUE (spec docs/superpowers/specs/2026-09-28-swusim-card-value-design.md, owner chose the
    // FULL scope with defence in v1, 2026-09-28). Replaces _SWUBotPlayValue's flat tag sum with
    // Body + Effect - SelfCost in expected-base-damage, read off the BOARD: removal is worth its best
    // legal target instead of a constant, a wipe is worth what it actually kills, healing is worth ~0
    // at full HP, and a unit is finally worth the damage it PREVENTS as well as the damage it deals.
    // ⚠ The `develop x cost` floor is KEPT, not replaced: 'unitvalue' measured -50 doing the opposite.
    // ⚠ ONE flag for the whole model. A half-migrated valuation is two models disagreeing.
    // Gate on ROUND-7 ARRIVAL first (a ~30-50% per-game binary), win rate second — win rate on the
    // canary matchup is ~8% with a +-2/100 noise floor, so it resolves far too slowly to steer on.
    'cardvalue',
    // Spending a BANKED Credit is a cost when those Credits are a component of the deck's win condition.
    // Bug report #1099 (prod, game 1402804): the bot spent its only Credit on a 4-drop whose When Played
    // bonus could not fire, breaking the owner's line — 6 resources + 2 Credits + Daimyo's Palace's aspect
    // waiver casts LAW_044 Single Reactor Ignition (8 printed, +2 Aggression waived) on the 6R turn.
    // ⚠ Needs the WAIVED cost to see the line at all: _SWUBotCreditUnlockValue prices LAW_044 at its
    // unwaived 10 against current capacity, returns 0, and the whole plan stays invisible. That is why
    // this is a separate valuation and not a tweak to that helper.
    // ⚠ Pairs with 'aspectwaiver': banking Credits is pointless if the waiver is burned in round 1, which
    // is bug #1098 in the SAME game. Measure them together.
    'creditbank',
    // ('curveplay', 'curveresource', 'curvemull' were SHIPPED 2026-10-06 as feature group 'p38'.)
    // ('mgkill' was SHIPPED 2026-09-25 as feature group 'p13' — its history is in the feature comment.)
    // ('mgbomb' was SHIPPED 2026-09-24 as feature group 'p12' — its history is in the feature comment.)
    // ('breach' was SHIPPED 2026-10-03 as feature group 'p26' — its history is in the feature comment.)
    // ('popkill' was SHIPPED 2026-10-03 as feature group 'p27' — its history is in the feature comment.)
    // ── 2026-10-07 GAP SCREEN (the 9 real-vs-bot gap cells; .claude/tmp/ovn). Built from the per-cell diagnoses; each is
    // screened against today's stack and confirmed on a fresh seed block before it ships.
    // The Mando Colossus regression (bisect: 7 of 8 Mando wins -> losses vs Vader start at a 'curvemull' mulligan; it throws
    // away 84/100 Mando hands — the rule was sized for 6 cards and the Colossus base deals 5). Guard: bot_mullfix_test.php.
    'mullhandsize',    // curvemull: the castable requirement is max(1, hand size - 4) (6 cards -> 2, a Colossus 5 -> 1)
    'mulleventclamp',  // curvemull: an Event's surplus counts max(0, s) at the mulligan — removal has nothing to price against
    'mullanswer',      // curvemull: control (rank >= 3) keeps an answer (removal / wipe) plus one castable card
    // Krennic vs Boba Fett (JTL) Blue (diagnosis .claude/tmp/diag_boba: Krennic loses the SPACE arena). Guard: bot_bobaspace_test.php.
    'bobaspace',       // Boba Fett JTL_009 reads as a 'space' deck, so Hyperspace Disaster is kept, not resourced
    'cravinganswer',   // resourcing: a power-strike that damages an enemy unit (Craving Power) counts as an answer
    // Vader (JTL) Yellow vs control (diagnosis .claude/tmp/diag_vader: control plays GROUND walls into a SPACE-only Vader).
    'wallarena',       // wall-first: a Sentinel is a wall only where the opponent has units (or vs a deck not space-flavoured). bot_wallarena_test
    'wallkeeparena',   // resourcing vs SPACE aggro: 'wallkeep' skips a Sentinel whose arena the opponent is not in. ⚠ owner ruling
                       // needed ("never resource a Sentinel vs aggro" — off-arena too?). bot_wallkeeparena_test
    'indirectthreat',  // a unit's base threat adds its On Attack damage to the defending player (TIE Bomber: 3). bot_indirectthreat_test
    'indirectsoak',    // a split share that puts my unit within the strongest enemy attacker's reach costs 0.75 x its loss, not chip.
                       // ⚠ owner ruling needed (base or units vs indirect while the base is healthy?). bot_indirectsoak_test
    'pilothost',       // an enemy pilotless Vehicle is worth +4/(hosts) more dead while their pilot leader is one resource from landing.
                       // ⚠ owner ruling needed ("removal on his pilots" = kill the hosts first?). bot_pilothost_test
    'protecteddup',    // resourcing: a SECOND copy of a protected card (space wipe, Chimaera, kept wipe) is a spare, tier 0.
                       // ⚠ owner ruling needed (overturns the confirmed "PROTECTED above duplicates" precedence). bot_protecteddup_test
    'claimlethal',     // initiative-for-wipe never claims when the opponent's ready attackers are lethal this round. bot_claimlethal_test
    'wipecredit',      // a play spending a Credit the next-round wipe needs is held (Lando R4: Anakin over Disaster's Credit).
                       // ⚠ owner ruling needed (Lando's Credits are a tempo engine, exempt from banking). bot_wipecredit_test
];


function SWUBotProposalList(): array {
    return SWU_BOT_PROPOSALS;
}

// Named proposal GROUPS, switched on together by "@try-<group>" — the mirror of SWUBotFeatureGroups(). Used to
// measure proposals TOGETHER before shipping them. The first, 'shipset' (sentinelkeep + wipethreat), was measured
// and SHIPPED 2026-09-18 as feature group 'p4', so it is gone from here. Empty until the next candidate set.
// 'lm3' measured WORSE than shrinkfirst alone (−42, p .061), so the set is retired; shrinkfirst shipped by itself.
// ('piettplan' = resourcing3 + piettcheat was measured 2026-09-22; with resourcing3 shipped it is '@try-piettcheat'.)
// 'krennicline' — the two halves of the owner's Krennic Blue Splash line (2026-09-28): bank Credits toward a bomb
// ('creditvalue') AND spend the base's once-per-game aspect waiver on it rather than on a round-1 2-drop
// ('aspectwaiver'). Measured ALONE, neither moves the 0/24 vs ahsoka-tano_ash_blue and each costs base damage — which is
// expected, because the line needs BOTH: LAW_044 Single Reactor Ignition is cost 10 unwaived against a max capacity
// of 6, so it wants the waiver (10 → 8) and the Credits (6 → 8) at the same time.
// ⚠ 'aspectwaiver' was REMOVED from both groups when it shipped as feature p16 (2026-09-29): it is
// always on now, so naming it here would ask for a proposal that no longer exists. The measured
// history of these groups is therefore PRE-p16 and not reproducible as written.
const SWU_BOT_PROPOSAL_GROUPS = ['krennicline' => ['creditvalue'],
                                 'krennicfull' => ['creditvalue', 'krennicscript'],
                                 'krennicsac'  => ['doomedtie', 'wdability'],   // HELD 2026-10-06: harmful on Krennic Splash (1:9, p=.02)
                                 'mullfix'     => ['mullhandsize', 'mulleventclamp', 'mullanswer'],   // 2026-10-07 gap screen
                                 ];
// ⚠ No 'creditline' group. #1098 + #1099 are one chain, but p16 shipped the waiver half as a FEATURE, so
// plain @try-creditbank already measures "the Credit half ON TOP OF the waiver fix" — a group would just
// be a confusing alias for a single proposal.

// Proposals default OFF: true only when the active variant explicitly enabled it.
function SWUBotProposalOn(string $name): bool {
    return in_array("try:$name", $GLOBALS['SWUBotDisabledFeatures'] ?? [], true);
}

// The multiplier map of the probe active for THIS decision, or null. Read by SWUBotWeights().
function SWUBotActiveWeightProbe(): ?array {
    foreach ($GLOBALS['SWUBotDisabledFeatures'] ?? [] as $d) {
        if (is_string($d) && str_starts_with($d, 'w:')) return SWU_BOT_WEIGHT_PROBES[substr($d, 2)] ?? null;
    }
    return null;
}

// The layer-4 GUIDES (BotGuides.php), switchable one at a time as "@no-guide:<name>". Gated at the single
// chokepoint _SWUBotGuides() in BotFallback.php. The core half of the anti-control bias is rules + guides +
// raw weights; without this switch the guides were the one part with no handle at all.
function SWUBotGuideList(): array {
    return ['attackFirst', 'maxUnits'];
}

// The features $variant turns off; null for a variant that is not recognised.
function SWUBotVariantDisabled(string $variant): ?array {
    if ($variant === '' || $variant === 'rl' || $variant === 'value') return [];   // 'rl' / 'value' = the full stack plus a learned layer
    if ($variant === 'base') return SWUBotFeatureList();
    if (str_starts_with($variant, 'no-rule:')) {
        $r = substr($variant, 8);
        return in_array($r, SWUBotRuleList(), true) ? ["rule:$r"] : null;
    }
    if (str_starts_with($variant, 'no-guide:')) {
        $g = substr($variant, 9);
        return in_array($g, SWUBotGuideList(), true) ? ["guide:$g"] : null;
    }
    if (str_starts_with($variant, 'rand:')) {
        $c = substr($variant, 5);
        return in_array($c, SWUBotRandomClassList(), true) ? ["rand:$c"] : null;
    }
    if (str_starts_with($variant, 'w-')) {
        $p = substr($variant, 2);
        return (isset(SWU_BOT_WEIGHT_PROBES[$p]) || isset(SWU_BOT_WEIGHT_FLOORS[$p])) ? ["w:$p"] : null;
    }
    if (str_starts_with($variant, 'try-')) {
        $p = substr($variant, 4);
        if (isset(SWU_BOT_PROPOSAL_GROUPS[$p])) return array_map(fn($x) => "try:$x", SWU_BOT_PROPOSAL_GROUPS[$p]);
        return in_array($p, SWUBotProposalList(), true) ? ["try:$p"] : null;
    }
    if (str_starts_with($variant, 'no-') && isset(SWUBotFeatureGroups()[substr($variant, 3)])) return SWUBotFeatureGroups()[substr($variant, 3)];
    if (str_starts_with($variant, 'no-') && in_array(substr($variant, 3), SWUBotFeatureList(), true)) return [substr($variant, 3)];
    return null;
}

function SWUBotVariants(): array {
    return array_merge(['base'], array_map(fn($g) => "no-$g", array_keys(SWUBotFeatureGroups())),
                       array_map(fn($f) => "no-$f", SWUBotFeatureList()),
                       array_map(fn($r) => "no-rule:$r", SWUBotRuleList()),
                       array_map(fn($g) => "no-guide:$g", SWUBotGuideList()),
                       array_map(fn($p) => "w-$p", SWUBotWeightProbeList()),
                       array_map(fn($c) => "rand:$c", SWUBotRandomClassList()),
                       array_map(fn($p) => "try-$p", SWUBotProposalList()),
                       array_map(fn($g) => "try-$g", array_keys(SWU_BOT_PROPOSAL_GROUPS)));
}

function SWUBotSetDisabledFeatures(array $features): void {
    $GLOBALS['SWUBotDisabledFeatures'] = array_values($features);
}

function SWUBotFeatureOn(string $feature): bool {
    // TEST-ONLY pin ($GLOBALS['SWUBotPinnedDisabled']): a guard test for one feature pins LATER features off so its numbers
    // keep isolating its own feature (introduced with p38, 2026-10-06). Production never sets it.
    return !in_array($feature, $GLOBALS['SWUBotDisabledFeatures'] ?? [], true)
        && !in_array($feature, $GLOBALS['SWUBotPinnedDisabled'] ?? [], true);
}
