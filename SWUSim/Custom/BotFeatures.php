<?php
// Per-seat FEATURE SWITCHES for the heuristic stack (RL bots spec, Section 7). Each change is named here and checked with
// SWUBotFeatureOn() where its behaviour lives, so a chooser profile can switch some of them off.
// ⚠ SHIP/HOLD RULE (owner, 2026-10-03 — replaces 2026-09-14's "every change must beat the stack head-to-head", which the
// 2026-09-16 fidelity reframe had already retired): a lever that CORRECTS something demonstrably wrong (shown on a test
// board), measures with no deck significantly hurt (a NULL passes), and is shown to FIRE in a smoke run ships ON as a feature
// group. A proposal stays a "@try-" only when it measures HARMFUL or is a judgement call rather than a fix.
// Owner: "i fear this outdated rule might be hurting potential explorations." The measurement is a SAFETY check.
// ⚠ NO PER-FEATURE UNIT TESTS (owner, 2026-10-12): bot features are verified by RUNS — a smoke run whose coverage shows the
// branch fired ("proposal:<name>", _SWUBotFired in BotFallback.php) and the ASH meta run. The ~156 per-feature pick/score tests
// were retired that day; the "Guard: bot_<x>_test.php" notes below name files that now live only in git history. Kept: the
// infrastructure tests (lookahead, legal actions, stack, tags, labels, traces, Bot Practice gates) and bot_invariants_test.php.
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
// ⚠ THIRD HALF, added 2026-10-12 from Bug Report game 1647080: "bot did not attack with Neel before playing Tarpals".
// Neel's grant is "When Played/ON ATTACK", and both halves above read When Played only — a Neel already in play
// scored his swing as a 1-power poke while the go-wide guide named Tarpals. An attack whose On Attack arms the grant
// for an affordable payoff now carries the same price (_SWUBotAttackEnablerBonus), and the guide starts its line with
// that swing when the payoff is in its subset. Same switch.
// Guard: SWUSim/DevTools/tests/bot_enablerfirst_test.php (A-E the scorer half, F-J the guide half, K-R the attack half).
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
// human Krennic games (the human sent their own deployed Krennic back with Chimaera):
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

// Part 20 (2026-10-02): owner rulings from Ninin's Ahsoka Yellow (ASH_009) human games.
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
// Part 42 (2026-10-08): LEADER UNDERSTANDING — the leader audit of the ash-meta + Force Fam fixtures (owner: "make sure the bot
// understands how each leader works", front side and unit side). Rules-reading fixes, owner ruling: shipped ON, no sweep.
//   tuck — LOF_016 Qui-Gon's "return a friendly unit, play a cheaper non-Villainy unit free" is priced by its best (returned ->
//          played) pair: the returned unit's When Played re-bought, the free unit's cost, less the body that leaves. 61% of 120 traced
//          uses bounced a 1-2 drop for nothing; the deployed "you may" was declined 106/107. Guard: bot_tuck_test.php.
//   doubleplay — HMW_008 Grievous's "Play 2 units from your hand" is worth the two plays it makes, each scored exactly as a direct play
//          (every hold included); his "Choose_a_unit_to_play" pick is that same score. Was a flat 0.40 (used 22 of 803) and a first-in-hand
//          pick (7 of 22 played a duplicate unique). Guard: bot_doubleplay_test.php.
//   unitedge — a unit play that switches on "While you control more units than an opponent, this unit gets +N/+0" (deployed Grievous)
//          goes before his attack (7 attacks at an even count). Guard: bot_unitedge_test.php.
//   playdefeat — HMW_016 Maul's "Play a unit from your hand. It costs 1 less. Then, defeat it." is worth the unit's EFFECTS (its When
//          Played / When Defeated tags), never its body; a unit with neither is not played. Was priced as a full "unlocked" play (25 of
//          128 uses played an effect-less unit, Mae x12) and its pick took the biggest body. Guard: bot_playdefeat_test.php.
//   deployreplay — Maul's "When Deployed: play a unit defeated this phase from your discard, 5 less": the deploy adds the best replay
//          the resources cover, waits behind his own Action when that Action is worth using, and the Action counts the replay it sets
//          up (Maul the card, then deploy it back). 21 of 31 deploys replayed nothing. Guard: bot_playdefeat_test.php.
//   villainpitch — HMW_010 Tarfful's discard-cost Beast Action is the PITCH LINE only (owner ruling 2026-10-08): an off-aspect Villainy
//          card pitched to half-activate Anakin (LOF_070), none in the discard yet, before 6 resources — round 1 at once, later with the
//          last 2 resources after the unit plays. Its discard pick takes the Villainy card (the 'heropitch' mirror). Was an "unlock"-priced
//          Action fired in round 1 in 48/48 games, pitching Chimaera 1 of 76 times. Guard: bot_villainpitch_test.php.
//   attackreserve — while a ready unit reads "On Attack: You may pay N resource(s). If you do, create a <X> token" (deployed Tarfful's
//          Beast), a play leaving fewer than N resources is charged that token (priced as a unit play its size). Owner: "on 7R, Anakin
//          plus Tarfful swing + 1R for beast token … evaluate whether spending or saving 1 is worth more". 27 of 37 deployed Tarfful attacks
//          had 0 resources. Guard: bot_attackreserve_test.php.
//   shipdiscount — JTL_005 Piett's "Play a Capital Ship unit from your hand. It costs 1 resource less" is the ship plus the card the
//          saved resource pays for (nothing extra: just under the direct play). Was priced by what it unlocks only — ships hard-cast with
//          the Action ready 367/300 traced games. ('piettcheat', +0 on 2026-09-22, stays a proposal.) Guard: bot_shipdiscount_test.php.
//   deployfirst — a unit a deploy available NOW would discount ("Each Capital Ship unit you play costs 2 resources less") waits for it;
//          'blockerfirst' hard-cast a 5-cost ship right before deploying Piett in ~25% of games. Guard: bot_shipdiscount_test.php.
//   pilotchoice — the pilot leaders (JTL_006 Vader, JTL_009 Boba, JTL_012 Luke): "Deploy_as_Unit_or_Pilot?" is Pilot whenever a usable
//          Vehicle exists — any host for a "When deployed as an upgrade" pilot (Boba's split, Vader's TIEs), a READY one otherwise
//          (Vonreg's / Luke's value is the host's attack; a leader unit deploys ready); a pilot naming a trait ("If it's a Fighter",
//          Luke) uses only such a host. Else Unit. "Choose_a_Vehicle_to_deploy_onto" takes a ready, sturdy host. 'planned-answer' defers to
//          it on both prompts (it chose Unit 140 times with a ready Vehicle out). Was the first-listed host (a token, a 1-HP ship).
//          Guard: bot_pilotchoice_test.php.
//   plantruncate — a lookahead plan step that left answers UNTRIED (budget share / branch cap; the bridge lists my own units first) is
//          marked 'truncated', and the planned-answer rule lets the fallback choose among all answers there. Talzin's -1/-1 on her own
//          units (120), Maul LOF's pings (~340), Chewbacca's deal-2 (43) came from such plans. Guard: bot_plantruncate_test.php.
//   twoping — LOF_009 Maul's "Deal 1 damage to a unit and 1 damage to a different unit" (both mandatory) is priced as its best pair of
//          targets (the second on MY unit when one enemy is there; 1,807 forced self-pings), and a ping that kills is a kill for the
//          Force gate (it read only "-N/-N"). Guard: bot_twoping_test.php.
//   forcebuff — the 'force' gate (no empty enemy board; held when it kills nothing and another card needs the Force) judges HOSTILE
//          use-the-Force Actions only: LOF_008 Obi-Wan's Experience was used 0 of 2,065 times on an empty enemy board. Guard:
//          bot_forcebuff_test.php.
//   dedra — SEC_010 Dedra's "Choose an enemy unit. Its controller may deal 2 damage to it. If they don't, draw a card": her pick is a
//          2-damage hostile target (was the first listed, 3,636/3,636); as the opponent the bot takes the 2 only when the unit survives it
//          or is a token (it answered YES every time — 32% of targets died to it). Guard: bot_dedra_test.php.
//   thrawnwd — JTL_002 Thrawn: a reuse of a When Defeated that helps an opponent is declined (SEC_215's "each opponent may ready a
//          resource" reused 95 times); while the reuse is available, a When Defeated pays back twice in what losing its unit costs.
//          Guard: bot_thrawnwd_test.php.
//   armorerpicks — ASH_001 The Armorer's "play an upgrade from your resources": the upgrade worth most to play, on the attach scorer's
//          host (its host policy sends Preparation to an exhausted unit). Was the first-listed resource (163/163) and first-listed unit.
//          Guard: bot_armorer_test.php.
//   freekill — LAW_004 Aurra's "Defeat a non-leader unit with 1 or less remaining HP" is worth its best kill and goes before any attack
//          (it costs no attack); nothing to kill, never used. Was a flat 0.40 (60-68% use with a target; 39 attacks into its targets).
//          Guard: bot_freekill_test.php.
//   advready — an Advantage token goes to a READY friendly unit first (ASH_013 Ezra gave it to exhausted units 14-20% of the time), and
//          Ezra's exhaust is declined with no friendly unit but the attacker (the give then went to an ENEMY). Guard: bot_advready_test.php.
//   tokenfirst — a trigger that gives the played unit a token ("…an Advantage token to that unit", ASH_017 Greef) resolves before that
//          unit's own triggers (ASH_171 Pegasus Tri-Wing resolved first 645/645 and never saw the token). Guard: bot_greef_test.php.
//   deploybuff — a unit play waits while a leader whose deployed side reads "When you play or create a unit: …" can deploy now (41% of
//          Greef's deploys came after 2+ plays). Guard: bot_greef_test.php.
//   creditdeploy — a "create a Credit token" option (LAW_019 Alliance Outpost) is taken when the Credit brings the leader's resource-cost
//          deploy (LAW_013 Chewbacca, "Epic Action [4 resources]") within reach this round or next — the owner's R1 Credit, R2 deploy on
//          3 + 1 (14 of 513 traced uses took the Credit). Guard: bot_chewbacca_test.php.
//   aspectpick — LAW_018 Lando's "Choose an aspect" names the aspect most of my remaining deck has (was the first listed, 7,059/7,059);
//          milling THEIR deck ('landomill'), their leader's color — never Heroism/Villainy (owner 2026-10-08, Lake Country).
//          Guard: bot_lando_test.php.
//   actionfirst — in the deploy round the same leader's front Action, while worth using, goes before the deploy (a leader deploys exhausted
//          or not and enters ready, CR 4.329): Lando 0/1,092, Obi-Wan 0/1,011, Talzin 35/1,965 did. Not for a Support leader (its flip
//          turn is planned), an Action that defeats a friendly unit (Krennic's strike needs it), Maul ('deployreplay') or Piett. Guard:
//          bot_lando_test.php (+ the stacklethal / deploystrike / playdefeat / enablers tests for the exceptions).
//   healamount — "Heal N damage from <a unit> or from your base" (ASH_005 Luke, deployed) is scored by the HP each actually restores (a base
//          point 0.9, 1.5 at 15 HP or less, a unit point 1.0); it was the unit by value 2,202/2,202. Guard: bot_healamount_test.php.
//   deployswing — a deploy that puts a ready leader unit on the board is worth that unit's best attack this turn (HMW_007 Vader's free
//          deploy, scored a flat 1.5, was never taken in 24 of 47 probe games that reached 6 resources). Guard: bot_deployswing_test.php.
//   landomill — SHIPPED 2026-10-08 (owner: "turn it on"; the 2026-09-23 proposal): Lando mills MY deck for the guaranteed Credit, and THEIRS
//          after the flip or at 10+ resources ("Lando aims for 9 … 9 + 1 for the mill ability"), naming their leader's color. Guards:
//          bot_landomill_test.php, bot_landoflip_test.php.
//   landoflip — Lando never deploys with 0 Credits; with one banked the flip-round Action is skipped (6R + 3C = 9); the last banked Credit is
//          not spent before the flip. Owner 2026-10-08. Guard: bot_landoflip_test.php.
//   splitzero — an "up to N" damage split also offers assigning nothing ('-'): with no enemy target JTL_009 Boba's pilot split hit my own
//          units (127 traced); and the all-enemy splits are enumerated first, so the cap cannot cut "kill all four 1-HP enemies".
//          Guard: bot_splitzero_test.php.
//   yodaloop — the Qui-Gon (LOF_016) Yoda loop, owner rulings 2026-10-08: the tuck waits while a castable Yoda in hand wants the Force for his
//          heal; a Force-heal unit (Yoda) is the first one the tuck returns; a tuck deck never resources its chain pieces (Kelleran, Depa,
//          Amidala…). Guard: bot_yodaloop_test.php.
//   healwaste — owner 2026-10-08, "don't waste on a heal of 4 or less … that goes for other restores too": "use the Force to heal N" is declined
//          below N damage on my base; a Restore N attack waits (by the restore it would waste) while an enemy can hit my base first.
//          Guard: bot_healwaste_test.php.
//   searchvalue — "Search the top N cards of your deck…" (tag 'search-top-deck', 58 cards, weight 0 before): to hand = a draw per card; into
//          play = a draw per card + the resources saved; anything else = half a draw (owner 2026-10-08). Guard: bot_searchvalue_test.php.
//   mandoclaim — ASH_014 The Mandalorian's claim-and-draw (owner plan 2026-10-08: "2R claim and draw · 3R a 2-drop then claim and draw…"): the
//          claim is worth the draw, after the plays that leave its resource; a play that spends that resource is charged the draw.
//          Guard: bot_mandoclaim_test.php.
//   baseheal — a base hit by a "When Attack Ends: If this unit dealt combat damage to a base, heal that much damage from your base" unit
//          (ASH_031 Hera) is also worth what it heals (owner, Mando: "Hera + Aggressive Negotiations — swing big and heal big"). AN went on
//          the bigger Bith Brute. Guard: bot_baseheal_test.php.
//   lciwplan — SEC_180 Let's Call It War: holding the initiative, the 3 goes where the follow-up 2 still kills another unit in that arena
//          (owner, Mando: "clear two low-health space units"). Guard: bot_lciwplan_test.php.
//   supportfirst — a Support leader's deploy carries the planned Support attacker's attack (+ Mando's lent initiative draw), so it goes
//          before that unit's own attack (owner, Mando: "6R deploy and use Mando's support on any unit that stuck"). Guard: bot_supportfirst_test.php.
//   cleanup — hard control vs an aggro leader whose board still reaches my base: an attack defeats a unit before it hits the base (owner,
//          Mando: "prefer to clean up the board until you've stabilized against aggro's gameplan"). Guard: bot_cleanup_test.php.
//   stabkeep — a "Give each enemy unit -N/-N" mass debuff (SEC_051 Bo-Katan) is kept vs aggro like the wipes (owner, Mando: "play leading
//          up to Bo-Katan and SRI … to stabilize"). Guard: bot_stabkeep_test.php.
//   pitchtarget — ASH_163 Reckless Sacrifice's discard is the unit that keeps the best 5-damage target in reach (owner, Mando: "the best
//          Turn 2 play is Reckless Sacrifice when you have any of the Villainy 1-drops in hand"). Guard: bot_recklessturn2_test.php.
//   anakinpitch — a "Discard a unit from your hand" play (ASH_163) is worth each LOF_070 Anakin half (Heroism / Villainy in discard) it
//          switches on, and is not a dud with an Anakin in hand (owner: "Reckless Sacrifice + the villainy card … instantly activate
//          Anakin"). Guard: bot_anakinpitch_test.php.
//   bokatanres — hard control's Bo-Katan (SEC_051): resourced from a non-aggro opening hand, otherwise only when nothing weaker is left,
//          never a second in a game (owner: "this deck thrives in the late game"). Guard: bot_bokatanres_test.php.
//   forceregen — the Force is one token: holding it, a spender goes before a refilling Force-unit attack (LOF_023 Jedi Temple); without it,
//          a "When Played: You may use the Force" play (Yoda) waits for that attack (leader audit: Qui-Gon). Guard: bot_forceregen_test.php.
//   chewieattack — deployed LAW_013 Chewbacca's "On Attack: You may defeat a friendly resource …deal 2": yes only to kill, to set up a Red
//          Five finish, or to pop a Sentinel's Shield (owner 2026-10-08). Guard: bot_chewieattack_test.php.
//   sentinelsac — never sacrifice an ACTIVE Sentinel (printed or gained) while another body exists; a doomed one is fair game (owner:
//          "generally, do not sac active Sentinels"). Guard: bot_krennicfodder_test.php.
//   selectivewipe — 'wipeinit' no longer holds unit plays for a SELECTIVE wipe ("Defeat any number of …", ASH_053 Pre Vizsla): it never takes
//          mine (user report 2026-10-09, Krennic Blue idle at 7 resources). Guard: bot_selectivewipe_test.php.
//   mitigation — a planned wipe no longer holds this round's damage mitigation: a Sentinel where the opponent can still hit my base (a ready
//          attacker, or a leader able to deploy), an Ambush kill, Krennic fodder (owner 2026-10-09). Removal was never held (shrink-first
//          plays it before the wipe's initiative claim). Guard: bot_mitigation_test.php.
//   wallstack — hard control vs an aggro leader keeps putting Sentinels down in rounds 1-4 even with one already up ('wallfirst' stopped at
//          one per arena) — owner, Mando vs aggro 2026-10-09: "sentinel up most turns". Guard: bot_wallstack_test.php.
const SWU_BOT_PART42_FEATURES = ['tuck', 'doubleplay', 'unitedge', 'playdefeat', 'deployreplay', 'villainpitch', 'attackreserve', 'shipdiscount', 'deployfirst', 'pilotchoice', 'plantruncate', 'twoping', 'forcebuff', 'dedra', 'thrawnwd', 'armorerpicks', 'freekill', 'advready', 'tokenfirst', 'deploybuff', 'creditdeploy', 'aspectpick', 'actionfirst', 'healamount', 'deployswing', 'landomill', 'landoflip', 'splitzero', 'yodaloop', 'healwaste', 'searchvalue', 'mandoclaim', 'baseheal', 'lciwplan', 'supportfirst', 'cleanup', 'stabkeep', 'pitchtarget', 'anakinpitch', 'bokatanres', 'forceregen', 'chewieattack', 'sentinelsac', 'selectivewipe', 'mitigation', 'wallstack'];
// Part 43 (2026-10-12, owner "let's ship gapstack") — the 2026-10-07 gap screen's '@try-gapstack' group (control vs SPACE aggro),
// shipped after the ASH meta screen (.claude/tmp/ash-meta-runs/gapstack-20261012, 19,515 games paired with today's plain stack):
// hyper aggro 176:222 newly won:lost (p .024), hard control 90:67 (p .08), soft control 240:217; archetype Pearson +0.03 → +0.06, MAE
// 0.100 → 0.097, style bias +9.6/−12.5 → +9.0/−12.0 — the first lever to move the anti-control gradient the right way.
//   wallkeeparena — resourcing vs SPACE aggro: 'wallkeep' (p39) no longer keeps a Sentinel in an arena the opponent has no unit in; it is
//          the card that goes, ahead of a space answer (Aurra s012 resourced Pirate Snub Fighter, kept a ground Moff Gideon vs four Vader
//          ships). Owner ruling 2026-10-12 by shipping: "never resource a Sentinel vs aggro" means a Sentinel that can wall. Guard: bot_wallkeeparena_test.php.
//   cravinganswer — resourcing: a power-strike that damages an enemy unit (Craving Power LOF_091) counts as an answer — Krennic Blue's
//          only spot answer to a piloted ship, resourced in 30 of 82 losses to Boba Blue. Guard: bot_bobaspace_test.php.
//   wallarena — rule 'wall-first': against a SPACE-flavoured deck a wall stands only where the opponent has units, so a ground Sentinel
//          is not the first play into an all-space board. Guard: bot_wallarena_test.php.
//   bobaspace — Boba Fett (JTL_009) reads as a 'space' deck (owner: "space midrange, 27 of 41 units space"), so Hyperspace Disaster is
//          kept against him, not resourced. ⚠ keyed on the leader alone, every base (the 10-07 canary suspected Lake Country). Guard: bot_bobaspace_test.php.
const SWU_BOT_PART43_FEATURES = ['wallkeeparena', 'cravinganswer', 'wallarena', 'bobaspace'];
// Part 44 'controlvalue' (2026-10-13, owner "let's ship it") — ten value / answer rules for the midrange and control seats, built from the
// owner's rulings on Ninin's Maul Blue v2, Grievous Blue and Krennic Blue Splash games (human-game read, 2026-10-12).
// ASH meta screen (2026-10-13, paired with the plain stack on the same code): hard control 234:169
// newly won:lost (p .0014), midrange 290:345 (p .032) — lost mostly TO control, moving hard control vs midrange 36.9% → 40.4% (real 56.4%);
// archetype Pearson +0.04 → +0.08, matchup-cell Pearson +0.20 → +0.22, hard control bias −12.2 → −10.7. '@no-p44' / '@no-controlvalue'.
//   handhate — a look-and-discard play (tag discard-opponent-hand) is worth the card it takes (seen cards in full, else the meta model);
//          "If you do, they draw" (tag opponent-draw) hands one back (owner: Bazine Netal SHD gets the same treatment).
//   leaderkill — a midrange/control deck's kill of an enemy leader unit passes the racing target filter and adds 2 rounds of that leader's damage.
//   freshsac — rounds 1-5: a non-fodder unit played this phase (not yet attacked) is charged like a ready one when sacrificed.
//   maulpitch — Maul's play-then-defeat Action on an off-aspect Heroism unit sets up Anakin (in hand, or still in the deck); a hard cast of
//          that unit waits for the Action (owner: always via Maul's ability).
//   condetb — a gated When Played (Disposable B1, Lifetree Caravan) waits for the unit that switches it on (owner: "B1 should always come
//          after other units").
//   gloom — "give each enemy unit -N/-N" joins the control wing's wipe plan (rule 5), judged by the lookahead's actual kills (owner: these
//          are gloom effects, not guaranteed wipes).
//   wdattack — an attacker that dies also fires its When Defeated: its damage kills count toward the attack.
//   spenttrade — midrange: a cheap spent body (When Played fired / When Defeated pays back) trades into an equal unit.
//   enginekill — a kill of an engine (Restore, base burn, Credit maker, Sentinel strip, ally buff) adds its per-round value.
//   cleanupsoft — feature 'cleanup' extends to SOFT control vs an aggro leader in rounds 1-5.
const SWU_BOT_PART44_FEATURES = ['handhate', 'leaderkill', 'freshsac', 'maulpitch', 'condetb', 'gloom', 'wdattack', 'spenttrade', 'enginekill', 'cleanupsoft'];
// Part 45 (2026-10-13, owner bug report, game 1647080).
//   playdefeatwd — Maul's "play it, then defeat it" fires the unit's DAMAGE When Defeated at once, so it is priced by what it kills on the
//          board as it stands (_SWUBotWdKillValue), not by flat damage tags. The bot played Snub Fighter Squadron on 4 resources with The
//          Legacy Run in hand (6 divided = Disposable B1 + Supreme Council Aide), then hard-cast The Legacy Run for 5 a round later; owner:
//          "should have used Maul's ability to use Legacy Run as almost a board wipe".
//   uniquewd — Maul's Action on a second copy of a unique I control: the uniqueness rule defeats the old copy too, so its body is lost and
//          its damage When Defeated fires as well (Ninin vs Poggle: The Legacy Run twice, 6 + 6); the uniqueness pick defeats the copy
//          Maul is NOT about to defeat.
//   wdshieldpop — that When Defeated's value counts a point spent popping an enemy Shield (Ninin vs Ahsoka: 4 to Neel, 2 into Han's Shield),
//          priced as the split picker already prices it ('splitpop', p32).
const SWU_BOT_PART45_FEATURES = ['playdefeatwd', 'uniquewd', 'wdshieldpop'];
// Part 46 'tempolines' (2026-10-13, owner "drop only softenkill and ship the rest") — from the deep read of Ninin's 18 Maul Blue v2 games.
// Maul v3 single-rule screen (.claude/tmp/maul-v3-single-screen, Maul's seat only, 9 opponents x 128 paired games each): nightpick −4
// (20:24), wdbait +3 (5:2), ambushsetup −2 (0:2) — all near neutral; 'softenkill' (−16, p .06) was dropped. '@no-p46' / '@no-tempolines'.
//   nightpick — HMW_204 Nightbrother's replay from the discard is the unit worth its EFFECTS (When Played now; a damage When Defeated at
//          the regroup, priced by its kills) plus the one attack it makes now — not the first unit in the discard.
//   wdbait — a damage When Defeated unit (The Legacy Run) played while a SEEN card in an opponent's hand would defeat it is bait: + half
//          of what its When Defeated kills now. A take-control card (No Glory, Only Results) is no bait.
//   ambushsetup — a ping counts the just-played unit's pending Ambush as a finisher (Snub Fighter Squadron pings what its Ambush then kills).
const SWU_BOT_PART46_FEATURES = ['nightpick', 'wdbait', 'ambushsetup'];
// Part 47 'neelcombo' (2026-10-13, owner: "this list should now try to get the 'Neel combo' on Turn 1 … play Neel first, then Tarpals, and
// buff Tarpals for a free 4 damage to base") — Ahsoka Yellow w Neel (force-fam-HMW-predictions/ahsoka-tano_ash_yellow_v-with-neel). Round 1 under
// ASH_009: a hard rule plays ASH_248 Neel, then the payoff with the most power + Raid (HMW_254 Tarpals); the opening resourcer never banks
// either. Logic: _SWUBotNeelComboPlan / SWUBotRuleNeelCombo (BotRules.php). SHIPPED ON THE OWNER'S RULING, like p7/p9. '@no-p47'.
const SWU_BOT_PART47_FEATURES = ['neelcombo'];
// Part 48 'sentinelclear' (2026-10-13, owner on Bug Report game 1647080: "an aggro bot should not ignore attacks into a Sentinel if it means
// it can clear it this round especially if the opponent has no resources left"). An aggro hit softening the lone unshielded Sentinel that
// this round's ready attackers (+ a ready leader buff Action) can clear is worth its share of the kill — in full when the opponent has no
// ready resource. Logic: _SWUBotSentinelClearShare (BotFallback.php). SHIPPED ON THE OWNER'S RULING, like p47. '@no-p48'.
const SWU_BOT_PART48_FEATURES = ['sentinelclear'];
// Part 49 'creditengine' (2026-10-13, owner: "do a deep, thorough analysis of these games. look for at least 10 bot features we can ship
// immediately and turn them on") — Ninin's 5 human games on Krennic Blue Naboo vs 420 bot games on the list. Logic + evidence per
// feature: BotCreditEngine.php. SHIPPED ON at the owner's request (each corrects a measured gap); '@no-p49' / '@no-creditengine' is the stack before.
//   powerdamage   — "deal damage equal to its power to a unit" (ASH_102 Ravager) is hostile, its amount the dealer's power (self-hits 55/90).
//   fodderkeep    — the opening/early resource pick keeps the Credit engine's first 2 fodder bodies (Ant Droid banked 26/30).
//   mercres       — …and its resource-loop unit (LAW_159 Expendable Mercenary, banked 12/26).
//   mercplay      — Mercenary's play carries the resource it becomes while the Credit Action is ready (never cast in 88/120 games).
//   sachealwait   — the Credit Action yields while the unit it takes would heal an undamaged base (43% of heal sacs healed nothing).
//   healfodder    — a heal-on-defeat counts toward sacrifice pricing only up to my base's damage.
//   condetbvalue  — an unmet conditional When Played (Disposable B1, Lifetree Caravan) carries none of its value (B1 61%, Lifetree 43%).
//   krennicengine — JTL_032's "first When Defeated unit each round costs 1 less" is priced as an engine (sacrificed 46x, Chimaera'd 22x).
//   strikeleader  — a deploy/Strike True strike takes a killable enemy leader unit (missed 3 of 14).
//   foddermull    — a Credit-sac hand with no unit costing 2 or less is mulliganed (owner's keep: Krennic unit + a 1-drop; bot 0/120).
const SWU_BOT_PART49_FEATURES = ['powerdamage', 'fodderkeep', 'mercres', 'mercplay', 'sachealwait', 'healfodder', 'condetbvalue', 'krennicengine', 'strikeleader', 'foddermull'];
// Part 50 'bugfix1' (2026-10-15, owner: "go" on shipping step 1 of the ASH lever hunt, .claude/tmp/ash-levers-20261015/LEVERS.md). The bug class:
// prompts the bot answered by the index tiebreak, and self-harm it never guarded. Measured as '@try-bugfix1' on the full ASH set vs baseline v2.2:
// soft control +66 (p .002), hard control +52 (p .0005), soft aggro −75 (p .0001); archetype Pearson +0.08 → +0.11; every style but midrange
// moved toward real. Helpers at the end of BotFallback.php; the self-burn filter is in BotHeuristic.php. '@no-p50' / '@no-bugfix1'.
//   discloseanswer  — a free disclose is answered with the smallest covering set (Condemn's -6/-0 defence was declined 2,228/2,228).
//   selfburnguard   — a card whose own-base damage defeats my base is never played (79 games lost to Operation Cinder / Rancor, 54 Dedra).
//   plotpick        — the Plot pick by play value (the first candidate in 99% of 10,750 picks).
//   hostileexhaust  — Shin Hati never exhausts my own unit; the front trigger waits for a ready enemy to exhaust.
//   nohostiletarget — an unqualified hostile Action ("Give a unit -1/-1") waits while no enemy unit is on the board (Talzin self-hits 115).
//   downgradehost   — the Armorer never plays a downgrade from its resources onto my own unit (Grav Charge 13/13 on its own).
const SWU_BOT_PART50_FEATURES = ['discloseanswer', 'selfburnguard', 'plotpick', 'hostileexhaust', 'nohostiletarget', 'downgradehost'];
// Part 51 'step2' (2026-10-16, owner: "sounds good go" — including his OK on the mulligan rule). Step 2 of the ASH lever hunt: the two of six
// gradient levers that moved toward real. Measured TOGETHER as '@try-step2' vs baseline v2.3: hyper aggro −109 (p .0002), soft aggro −128
// (p .0004), soft control +206 (p <.0001); archetype Pearson +0.11 → +0.16, Spearman +0.08 → +0.17, MAE 0.094 → 0.087; style bias hyper +5.4,
// soft aggro +4.0, midrange −0.3, soft control −3.2, hard control −8.6. '@no-p51' / '@no-step2'.
//   mullfloor          — a hand with 3+ cards castable by round 3 including a unit (2+ in a 5-card hand) is kept, whatever the summed curve
//                        surplus (Dedra mulliganed 64%, Lando Colossus 65%, Greef Red 54% of hands with 3+ cheap cards). _SWUBotCurveMulligan.
//   actionbeforeclaim  — the Force token is in the ability signature (Avar's "The Force is with you" scored -0.5 as a no-op); a worthwhile
//                        Action goes before the claim; control passes rather than claims while its front Action waits for an enemy target.
const SWU_BOT_PART51_FEATURES = ['mullfloor', 'actionbeforeclaim'];
// Part 52 'engines3' (2026-10-16, owner: "ship and freeze plox"). Step 3 of the ASH lever hunt: each deck's leader/engine played as built.
// Measured TOGETHER as '@try-engines3' vs baseline v2.4: archetype Pearson +0.16 → +0.36, Spearman +0.17 → +0.30, MAE 0.087 → 0.070
// (spread SD 10.6 → 8.7pp, real 5.8); Kylo 14% → 52% (real 57), Leia SEC 18% → 55% (53), Asajj 37% → 48% (48), Talzin Green 36% → 48% (56),
// Chewbacca Outpost 24% → 33% (53); Armorer and Krennic (Koska) unchanged. Helpers at the end of BotFallback.php. '@no-p52' / '@no-engines3'.
//   kyloengine    — LOF_001 Kylo: the Action waits without an upgrade in hand; the discard is an upgrade he can wear (downgrades kept);
//                   the replay never puts a downgrade on him; the deploy goes first while an affordable upgrade waits in the discard.
//   leiadisclose  — SEC_004 Leia: the disclosed card is the one that leaves a FRIENDLY Experience recipient; the Action waits without one.
//   talzinforce   — LOF_002 Talzin: a non-killing -1/-1 waits while other Force cards (hand, or 2+ in deck) need the token; a "use the
//                   Force" unit without it waits while a ready Force-unit attack can refill it.
//   armorerengine — ASH_230 Improvised Identity is the host's attack (worthless once exhausted; the plain attack waits for it); JTL_028
//                   Nabat Village bottoms the 3 cards worth least to keep.
//   chewieline    — LAW_013 Chewbacca: the front Action only kills a 3+ cost unit before the deploy; the 4-resource deploy goes first.
//   koskaorder    — ASH_079 Koska Reeves waits for the round's Credit sacrifice (her token and Sentinel need a friendly defeat this phase).
//   asajjping     — JTL_001 Asajj: the ping waits without a contested arena; the friendly pick is in one and survives.
const SWU_BOT_PART52_FEATURES = ['kyloengine', 'leiadisclose', 'talzinforce', 'armorerengine', 'chewieline', 'koskaorder', 'asajjping'];
// Part 53 'hardctl4' (2026-10-17, owner: "1 then 2" — ship and freeze, then fix the trigger-order engine bug). Step 4a of the ASH lever hunt:
// hard control + the biggest remaining deck gaps. Measured TOGETHER as '@try-hardctl4' vs baseline v2.5: archetype Spearman +0.30 → +0.32,
// MAE 0.070 → 0.067, cells MAE 0.132 → 0.130; hard control bias −9.1 → −8.2 (first lever to move it); Mando Colossus 49% → 53% (real 54),
// Mando Yellow 26% → 29% (39), Chewbacca Outpost 33% → 35% (53); Lando and Dedra unchanged. Helpers at the end of BotFallback.php. '@no-p53'.
//   removalfirst    — the removal 'shrink-first' would cast on a ready 3+ power enemy goes before 'blocker-first's body (BotRules.php).
//   mandoclaimplay  — Mando's claim-and-draw loses to a castable play when the claim would leave 2+ resources idle.
//   mandodrawattack — deployed Mando's attack carries his "if you have the initiative, draw" while I hold it.
//   landoflipbomb   — LAW_018 Lando's flip round with a 9-drop castable after it: the deploy first, other plays wait; Bo-Katan +2 vs 2+ kills.
//   homeonewall     — ASH_065 Home One is the space Sentinel wall vs a space deck with ships out.
//   dedratarget     — SEC_010 Dedra picks a unit her 2 damage defeats (both answers good for me), not a healthy chip target.
//   outpostepic     — LAW_019 Alliance Outpost's once-per-game Epic waits unless a token unit or a doomed unit's token can be spent.
const SWU_BOT_PART53_FEATURES = ['removalfirst', 'mandoclaimplay', 'mandodrawattack', 'landoflipbomb', 'homeonewall', 'dedratarget', 'outpostepic'];
// Part 54 'finalists' (2026-10-19, owner: "ship the 8 and freeze"). The ASH lever hunt's step 4b, screened one lever at a time: 107 registry levers
// (Custom/levers/) → a targeted 1,000-game canary each → 26 to a 5,000-game fresh-seed stage → the 8 that moved toward real in BOTH
// (.claude/tmp/ash-levers-20261015/{canary,stage2}/RESULTS.md). Measured TOGETHER as '@try-finalists' vs baseline v2.6: archetype MAE 0.067 → 0.066,
// spread SD 8.5 → 8.0pp (real 5.8); Thrawn DV 43% → 50% (real 52), Thrawn Yellow 34% → 41% (42), Rio 21% → 29% (44), Leia LAW 31% → 36% (50),
// Vane 42% → 46% (55); Armorer Yellow −3.7, Sloane Blue −3.5, Client −3.3 the other way; no style moved at p < .14. These are REGISTRY levers: each
// lives in Custom/levers/<theme>_<name>.php and runs while its feature is on (BotLevers.php, SWU_BOT_SHIPPED_LEVERS). '@no-p54' / '@no-<name>'.
//   wdpaybackkeep (Thrawn keeps his When Defeated payback bodies) · hostkeep (upgrade decks keep unique hosts) · forcexpfirst (a ready unit waits
//   for Obi-Wan's Experience) · disclosecover (a disclose-gated card waits while the rest of the hand cannot cover it) · riosaboteur (Rio's Action
//   priced as the +1/Saboteur swing) · pilotdeployfirst (a pilot leader deploys before the host Vehicle swings) · vanecheapupgrade (Vane pays with
//   tokens first) · tempcapturevalue (Arrest priced by the damage it removes this phase).
const SWU_BOT_PART54_FEATURES = ['wdpaybackkeep', 'hostkeep', 'forcexpfirst', 'disclosecover', 'riosaboteur', 'pilotdeployfirst', 'vanecheapupgrade', 'tempcapturevalue'];
// Part 55 'selfweakfix' (2026-10-19, owner: "let's fix those issues" — the Hemlock DV lever build's review). A bug fix, shipped ON:
//   noweaknesstarget — HMW_003 Doctor Hemlock's mandatory "Give a Weakness token to a unit without a Weakness token on it" Action waits while no
//                      ENEMY unit can take the token ('nohostiletarget' only knew "-N/-N", damage and exhaust). 20 self-gives in 40 plain games
//                      vs Mando Colossus (R1-R2 on my own Mandalorian token). '@no-p55' / '@no-noweaknesstarget' turn it off.
//   sentinelclearfloor — 'sentinelclear' (p48) took max(Action value, Sentinel-clear buff value) for racing seats; with no buff to clear for that
//                      value is 0, so a WAITING (negative) leader Action was lifted to 0, tied PASS and won on index. That, not the missing
//                      pattern alone, is what fired Hemlock's target-less Action (nb002 R15). Only a positive clear value raises the Action now.
const SWU_BOT_PART55_FEATURES = ['noweaknesstarget', 'sentinelclearfloor'];
// Part 56 'controlkit' (2026-10-19, owner: "sure ship those as suggested"). The Hemlock DV levers (plans 2026-10-19-hemlock-dv-levers{,-plan2}),
// measured one at a time on the HMW meta baseline (390 games/arm) and Hemlock DV vs the hmw-meta field (440): nothing significant either way,
// so under the ship/hold rule every null-or-better one ships. HELD (trended harmful on Hemlock DV): 'handfinisher' (10:18) and 'upgradedtext'
// (3:7) stay off-by-default proposals. Report: .claude/tmp/lever-runs-20261019/m3/REPORT.md. 19 are registry levers (Custom/levers/), and
// 'handpick' was a proposal (BotFallback.php). '@no-p56' / '@no-controlkit' / '@no-<name>' turn them off.
const SWU_BOT_PART56_FEATURES = ['healobserver', 'enginewatch', 'koskawait', 'leadergone', 'nebulashield', 'fatiguefeed', 'widewipe',
    'clockdefence', 'wipecap', 'finisherexposure', 'finisherhold', 'curvesink', 'threatshape', 'blockerdeny', 'xpsink', 'shuttlemove',
    'doomedsoak', 'removalbeforewall', 'plotfirst', 'handpick'];
// Part 57 'readytie' (owner 2026-10-20, Bug Report game 1647080): "when two threats of the same stats can create a value kill situation,
// then take the one that is ready. that is more valuable to kill in a unit to unit combat strategy." Koska Reeves (4/4) chose between two
// 3/3 Beast tokens — one had just attacked (exhausted), one was ready — and they scored the same (3.9), so the first-listed, exhausted one
// died. An attack-target tie-break: a READY enemy unit the attack kills while the attacker survives gets +0.001 — far below any real
// difference, so it decides only ties. Every style. '@no-p57' / '@no-readytie' turn it off.
// readythreat (same day, owner): for a CONTROL seat a READY enemy kill is also worth its attack power in kill currency when no friendly
// Sentinel guards its arena — a ready 3/3 then ties an exhausted 5/4 (readytie breaks it). See _SWUBotReadyThreatPremium.
// readykill (registry lever Custom/levers/pricing_readykill.php, owner 2026-10-19, built default-OFF) SHIPPED here 2026-10-20: the owner asked
// for it a second time on the same board (game 1647080 — the Chimaera took the exhausted, Chewbacca-piloted N-1 4/6 over the READY,
// buffed Emissary's Sheathipede 4/4 against Ahsoka; readythreat lifted the Sheathipede to 7.8, still under the N-1's 9.1).
const SWU_BOT_PART57_FEATURES = ['readytie', 'readythreat', 'readykill'];
// Part 58 'tempo' (2026-10-21, owner: "design them all now before running the ASH baseline"). The GENERIC TEMPO LAYER (spec
// docs/superpowers/specs/2026-10-20-swusim-tempo-flavour-design.md §2): a deck DETECTED as tempo (BotTempo.php, > 28% tempo pieces) spends its
// tempo pieces by its style and the opponent's. Registry levers, Custom/levers/tempo_*.php (helpers: tempo_util.php). Inert for non-tempo
// decks and in Twin Suns. '@no-p58' / '@no-tempo' / '@no-<name>' turn them off.
//   bigattacker — aggro tempo vs aggro: a blunting piece (exhaust/bounce/debuff/Weakness/capture/lock) takes the opponent's BIGGEST ready attacker.
//   debufffirst — every tempo deck: "debuffs before trades" — an attack waits while a castable shrink turns its trade into a clean kill.
//   stallpivot  — midrange/control tempo vs aggro or midrange: blunt their threats until the pivot (midrange: my leader deployed; control:
//                 "I can now play my big stuff"), then lean on the base.
//   pressure    — midrange tempo vs control, soft control vs soft/hard control: lean on the base; a blunting piece clears a Sentinel.
//   hardhandhate — hard control vs hard control: discard their big threats and the cards that refill their deck (p44 owns 'handhate').
const SWU_BOT_PART58_FEATURES = ['bigattacker', 'debufffirst', 'stallpivot', 'pressure', 'hardhandhate'];
// Part 59 'tempoplans' (2026-10-21). The tempo DECK PLANS (spec docs/superpowers/specs/2026-10-20-swusim-tempo-deck-plans-design.md). Registry
// levers, Custom/levers/plan_*.php (helpers: plan_util.php), plus the Lake Country bar inside _SWUBotLandoBombFlip. '@no-p59' / '@no-tempoplans';
// '@no-tempoall' turns p58 and p59 off together (the measurement's off arm).
//   talzinflip — LOF_002 Talzin deploys in her 5-resource round ("deploy on 5"), timed within it: after the opponent's big attackers have swung
//                and their resources are spent, or before the round would be wasted; −1/−1 first, then deploy, upgrades on her, attack.
//   talzinend  — Talzin RED after the flip: burn when ahead or even with my base above 50%, else defend ("even" = remaining HP within 3).
//   talzinpivot — Talzin YELLOW's stall pivot is "I can play my big stuff", not the deploy (tempo_util.php _SWUBotTempoPivoted).
//   nuvochain  — while Doctor Hemlock is on his leader side, an optional Weakness never goes on an un-Weakened enemy (his Action's targets)
//                unless it kills or sets up a clean kill this turn.
//   landolc    — Lando on Lake Country (JTL_031): the 1-resource Action before the deploy, then 5R + 3C = 8 for The Mandalorian / Liberty.
const SWU_BOT_PART59_FEATURES = ['talzinflip', 'talzinend', 'talzinpivot', 'nuvochain', 'landolc'];
// Part 60 'stealwd' (2026-10-21, owner: "yes" to shipping the 2026-10-10 proposal). Measured null in its screen (0 changed games, no Qui-Gon
// in that field), shipped under the null-ships-ON rule after the Lando Blue vs Hemlock DV human games: No Glory took Qui-Gon in R6 of 2 of 4
// games and his When Defeated tucked a unit of the Lando player's. '@no-p60' / '@no-stealwd' / '@no-noglorywd' turn it off.
//   noglorywd — "take control of a non-leader unit, then defeat it" (JTL_043 No Glory, Only Results) adds the stolen unit's When Defeated, which
//               fires for ME, to the target's value (_SWUBotStolenWdValue, BotFallback.php).
const SWU_BOT_PART60_FEATURES = ['noglorywd'];
// Part 61 'budgetwipe' (2026-10-22, bug report P-1, Petranaki game 308: "the bot claimed thinking Pre Vizsla was a wipe. but it did nothing
// when it played it"). A budget wipe (ASH_053 Pre Vizsla: "…non-leader units with a total of 6 or less remaining HP") is valued — for the
// initiative claim ('wipeinit'/'wipedraw') and its losses — by what it can actually defeat (_SWUBotBudgetWipeKillSet, BotResourcing.php),
// not the whole arena. Shown on the game's own undo stack (R6 claim valued at 13 threat; real kill set empty → plays Koska instead).
// HMW meta 2026-10-22 vs a plain arm on the same code: 2 of 526 games changed (1:1, null), fired in 185 games; shipped under the
// null-ships-ON rule. Report: .claude/tmp/ash-meta-runs/budgetwipe-hmw-20261022/REPORT.md. '@no-p61' / '@no-budgetwipe' turn it off.
const SWU_BOT_PART61_FEATURES = ['budgetwipe'];
// Part 62 'copypick' (2026-10-22, Discord: "a bot play Fives, copying TS C3P0s when played to give away the sentinel? When there was a TS R2
// on board?"). TS26_34 Fives' copy pick was first-listed; each copy is now resolved in the lookahead and scored by the board change
// (_SWUBotCopyPickValue, BotFallback.php). '@no-p62' / '@no-copypick' turn it off.
const SWU_BOT_PART62_FEATURES = ['copypick'];
// Part 63 'tsfocus' (2026-10-22, Discord: "the bots will never attack into units they cant kill outright regardless of if that player's
// winning … they're ignoring everything else to kick you while you're down"; owner chose both rules). Twin Suns free-for-all only:
//   tslowseat      — trailing the healthiest opponent, a hit on a LOWER seat's base is worth a chip (it only brings on an elimination I lose).
//   tssentinelchip — a surviving hit on the lone Sentinel walling the leader's base is its share of the clear, and passes every style filter.
// (BotFallback.php _SWUBotTSLowSeatBaseCap / _SWUBotTSSentinelChipValue.) '@no-p63' / '@no-tsfocus' turn it off.
const SWU_BOT_PART63_FEATURES = ['tslowseat', 'tssentinelchip'];
// Part 64 'chipfirst' (2026-10-23, bug report P-5, Petranaki game 512: "bot kept claiming when it should have chipped damage"; owner chose
// "chip first, then claim"). Rule 3 'initiative-for-lethal' claimed the moment it saw lethal next round, passing away every ready attacker:
// the game's own undo stack replays it at R4 (5 attackers left) and R5 (7 attackers + 2 plays). The rule now waits while a ready unit can
// hit their base and claims once the attacks are spent (SWUBotRuleInitiativeForLethal, BotRules.php). '@no-p64' / '@no-chipfirst'.
const SWU_BOT_PART64_FEATURES = ['chipfirst'];
// Part 65 'curvekeep' (2026-10-24, HMW meta gap read; owner: "i like your plan. go"). Midrange's resourcer scored a castable-soon card
// 100 + cost, so it banked the CHEAPEST castable card first — even the only play for rounds 1–2. Ezra Blue Splash banked Arvel Skeen,
// its lone 3-drop, in 7/7 seat-1 games and only took the initiative in R1–R2; Talzin Red Force banked a ≤3-cost unit while keeping a
// 6+ drop in 20 of 66 traced R1–R4 picks. With ≤ 4 resources after the pick, the most expensive unit each of the next two rounds can
// cast stays in hand (_SWUBotCurveKeepIndices, BotResourcing.php). Control keeps the owner's 2026-09-13 castable-soon + one-bomb rule;
// the aggro wing already resources its most expensive cards. '@no-p65' / '@no-curvekeep'.
const SWU_BOT_PART65_FEATURES = ['curvekeep'];
// Part 66 'hpimmune' (2026-10-25, Discord: "Bots seem to have trouble with SEC Cassian leader. I just watched 3 different units get
// sacked into a Cassian that already had lethal damage on him, but the Cassian had initiative so it didn't matter"). Every "this
// damage kills it" check compared damage with remaining HP, so a lethal-damaged SEC_012 (remaining ≤ 0) read as killed by ANY attack
// and a unit dying to his counter-damage scored as a trade. Damage now kills only a unit that can be defeated by having no remaining
// HP (SWUImmuneToHpDefeat: Cassian with the initiative, Chirrut outside regroup, LOF_043) — SWUBotDamageKills, BotEvaluator.php,
// at the 16 combat/ping/shrink/loss sites. The same goes for the bot's OWN such unit, which now knows it survives. '@no-p66' / '@no-hpimmune'.
const SWU_BOT_PART66_FEATURES = ['hpimmune'];
// Part 67 'attrition' (2026-10-26, owner "ship the 4"): four registry levers from Ninin's Hemlock Red win over Ahsoka Yellow (Nabat Village),
// each reproduced on a replay board first, then measured on the HMW meta (538 games each vs a plain control: 0-1 winners changed, 0 failed).
// Custom/levers/attrition_*.php: observerping (my HK-47's ping is part of a kill), wipefirst (a good castable wipe goes before the bomb it
// would kill, and before attacks while they have a ready unit), finisherfirst (the 1-HP leader finisher before an indirect/divided play),
// bounceetb (a bounce does not hand back a When Played). Their sibling 'gangkill' stays a proposal. '@no-p67' / '@no-<name>'.
const SWU_BOT_PART67_FEATURES = ['observerping', 'wipefirst', 'finisherfirst', 'bounceetb'];
// Part 68 'attrition2' (2026-10-27, owner "ship them"): the other six attrition levers, from Ninin's three Hemlock Red games (vs Ahsoka Yellow,
// vs Boba Fett indirect burn, the mirror vs Axium's hand-hate list), each reproduced on a replay board, then measured on the HMW meta (538
// games each vs a fresh control: 0-5 winners changed, all six together 10, nothing significant, 0 failed). Custom/levers/attrition_*.php:
// gangkill (two attacks share one kill, only when the opponent can't reply in between), onattackshrink (an On Attack Weakness/-P/-H shrinks
// the defender before damage), deadremoval (a removal body's When Played with no enemy non-leader unit to hit), leaderwipe (a wipe that hits
// enemy leader units), healblocked (no base-heal value under Grievous / Tri-Fighter), removalbody (shrink-first hands back to an indirect-damage
// play it cannot see). '@no-p68' / '@no-<name>'.
const SWU_BOT_PART68_FEATURES = ['gangkill', 'onattackshrink', 'deadremoval', 'leaderwipe', 'healblocked', 'removalbody'];
// Part 69 'teamread1' (2026-10-30, owner "yes plox"): twelve team-read levers from 335 human team games, measured on the HMW meta (per-lever
// match subsets + full arms vs a fresh control, 0 failed; all on together 46 of 664 winners changed, null per style). Custom/levers/teamread_*.php:
// anfix (Aggressive Negotiations' swing goes through the play levers, so finisherhold holds it — soft aggro +8, p 0.02), replayfirst (a deploy
// replay When Played resolves before a Plot window), xpgift (no Experience Action while only enemy units can take it), weaknessgift (a Weakness
// on a unit the opponent can sacrifice or recycle is worth little), condemn0 (no attack by a 0-power Condemn carrier), doomedhost (no upgrade on a
// host that dies this round), pairdraw (Grievous' pair keeps a gated When Played), hammerheadhand (no Hammerhead value with nothing in reach),
// finisherres (keep resourcing short of the hand-size finisher), pingx1 (X-HP units into a ping leader cost less), ahsokaorder (power spread
// before Ahsoka's even-power Action), deploywait (a leader deploys once safe or forced). Held as proposals: hemlockr1, maulemptydeploy,
// krennicwipehold, leaderanswerhold, sweeperpace, stealbait. '@no-p69' / '@no-<name>'.
const SWU_BOT_PART69_FEATURES = ['anfix', 'replayfirst', 'xpgift', 'weaknessgift', 'condemn0', 'doomedhost', 'pairdraw', 'hammerheadhand',
                                 'finisherres', 'pingx1', 'ahsokaorder', 'deploywait'];

function SWUBotFeatureList(): array {
    return array_merge(['splits', 'targeting', 'tags2', 'keep', 'stop', 'enablers', 'picks'], SWU_BOT_PART3_FEATURES,
                       SWU_BOT_PART4_FEATURES, SWU_BOT_PART5_FEATURES, SWU_BOT_PART6_FEATURES,
                       SWU_BOT_PART7_FEATURES, SWU_BOT_PART8_FEATURES, SWU_BOT_PART9_FEATURES,
                       SWU_BOT_PART10_FEATURES, SWU_BOT_PART11_FEATURES,
                       SWU_BOT_PART12_FEATURES, SWU_BOT_PART13_FEATURES,
                       SWU_BOT_PART14_FEATURES, SWU_BOT_PART15_FEATURES,
                       SWU_BOT_PART16_FEATURES, SWU_BOT_PART17_FEATURES,
                       SWU_BOT_PART18_FEATURES, SWU_BOT_PART19_FEATURES, SWU_BOT_PART20_FEATURES, SWU_BOT_PART21_FEATURES, SWU_BOT_PART22_FEATURES, SWU_BOT_PART23_FEATURES, SWU_BOT_PART24_FEATURES, SWU_BOT_PART25_FEATURES, SWU_BOT_PART26_FEATURES, SWU_BOT_PART27_FEATURES, SWU_BOT_PART28_FEATURES, SWU_BOT_PART29_FEATURES, SWU_BOT_PART30_FEATURES, SWU_BOT_PART31_FEATURES, SWU_BOT_PART32_FEATURES, SWU_BOT_PART33_FEATURES, SWU_BOT_PART34_FEATURES, SWU_BOT_PART35_FEATURES, SWU_BOT_PART36_FEATURES, SWU_BOT_PART37_FEATURES, SWU_BOT_PART38_FEATURES, SWU_BOT_PART39_FEATURES, SWU_BOT_PART40_FEATURES, SWU_BOT_PART41_FEATURES, SWU_BOT_PART42_FEATURES, SWU_BOT_PART43_FEATURES, SWU_BOT_PART44_FEATURES, SWU_BOT_PART45_FEATURES, SWU_BOT_PART46_FEATURES, SWU_BOT_PART47_FEATURES, SWU_BOT_PART48_FEATURES, SWU_BOT_PART49_FEATURES, SWU_BOT_PART50_FEATURES, SWU_BOT_PART51_FEATURES, SWU_BOT_PART52_FEATURES, SWU_BOT_PART53_FEATURES, SWU_BOT_PART54_FEATURES, SWU_BOT_PART55_FEATURES, SWU_BOT_PART56_FEATURES, SWU_BOT_PART57_FEATURES, SWU_BOT_PART58_FEATURES, SWU_BOT_PART59_FEATURES, SWU_BOT_PART60_FEATURES, SWU_BOT_PART61_FEATURES, SWU_BOT_PART62_FEATURES, SWU_BOT_PART63_FEATURES, SWU_BOT_PART64_FEATURES, SWU_BOT_PART65_FEATURES, SWU_BOT_PART66_FEATURES, SWU_BOT_PART67_FEATURES, SWU_BOT_PART68_FEATURES, SWU_BOT_PART69_FEATURES);   // part 2, then 3-49
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
            'p16' => SWU_BOT_PART16_FEATURES, 'p17' => SWU_BOT_PART17_FEATURES, 'p18' => SWU_BOT_PART18_FEATURES, 'p19' => SWU_BOT_PART19_FEATURES, 'p20' => SWU_BOT_PART20_FEATURES, 'p21' => SWU_BOT_PART21_FEATURES, 'p22' => SWU_BOT_PART22_FEATURES, 'p23' => SWU_BOT_PART23_FEATURES, 'p24' => SWU_BOT_PART24_FEATURES, 'p25' => SWU_BOT_PART25_FEATURES, 'p26' => SWU_BOT_PART26_FEATURES, 'p27' => SWU_BOT_PART27_FEATURES, 'p28' => SWU_BOT_PART28_FEATURES, 'p29' => SWU_BOT_PART29_FEATURES, 'p30' => SWU_BOT_PART30_FEATURES, 'p31' => SWU_BOT_PART31_FEATURES, 'p32' => SWU_BOT_PART32_FEATURES, 'p33' => SWU_BOT_PART33_FEATURES, 'p34' => SWU_BOT_PART34_FEATURES, 'p35' => SWU_BOT_PART35_FEATURES, 'p36' => SWU_BOT_PART36_FEATURES, 'p37' => SWU_BOT_PART37_FEATURES, 'p38' => SWU_BOT_PART38_FEATURES, 'p39' => SWU_BOT_PART39_FEATURES, 'p40' => SWU_BOT_PART40_FEATURES, 'p41' => SWU_BOT_PART41_FEATURES, 'p42' => SWU_BOT_PART42_FEATURES, 'p43' => SWU_BOT_PART43_FEATURES, 'p44' => SWU_BOT_PART44_FEATURES, 'p45' => SWU_BOT_PART45_FEATURES, 'p46' => SWU_BOT_PART46_FEATURES, 'tempolines' => SWU_BOT_PART46_FEATURES, 'p47' => SWU_BOT_PART47_FEATURES, 'p48' => SWU_BOT_PART48_FEATURES, 'p49' => SWU_BOT_PART49_FEATURES, 'creditengine' => SWU_BOT_PART49_FEATURES, 'p50' => SWU_BOT_PART50_FEATURES, 'bugfix1' => SWU_BOT_PART50_FEATURES, 'p51' => SWU_BOT_PART51_FEATURES, 'step2' => SWU_BOT_PART51_FEATURES, 'p52' => SWU_BOT_PART52_FEATURES, 'engines3' => SWU_BOT_PART52_FEATURES, 'p53' => SWU_BOT_PART53_FEATURES, 'hardctl4' => SWU_BOT_PART53_FEATURES, 'p54' => SWU_BOT_PART54_FEATURES, 'finalists' => SWU_BOT_PART54_FEATURES, 'p55' => SWU_BOT_PART55_FEATURES, 'selfweakfix' => SWU_BOT_PART55_FEATURES, 'p56' => SWU_BOT_PART56_FEATURES, 'controlkit' => SWU_BOT_PART56_FEATURES, 'p57' => SWU_BOT_PART57_FEATURES, 'p58' => SWU_BOT_PART58_FEATURES, 'tempo' => SWU_BOT_PART58_FEATURES, 'p59' => SWU_BOT_PART59_FEATURES, 'tempoplans' => SWU_BOT_PART59_FEATURES, 'tempoall' => array_merge(SWU_BOT_PART58_FEATURES, SWU_BOT_PART59_FEATURES), 'p60' => SWU_BOT_PART60_FEATURES, 'stealwd' => SWU_BOT_PART60_FEATURES, 'p61' => SWU_BOT_PART61_FEATURES, 'p62' => SWU_BOT_PART62_FEATURES, 'p63' => SWU_BOT_PART63_FEATURES, 'tsfocus' => SWU_BOT_PART63_FEATURES, 'p64' => SWU_BOT_PART64_FEATURES, 'p65' => SWU_BOT_PART65_FEATURES, 'p66' => SWU_BOT_PART66_FEATURES, 'p67' => SWU_BOT_PART67_FEATURES, 'p68' => SWU_BOT_PART68_FEATURES, 'attrition2' => SWU_BOT_PART68_FEATURES, 'p69' => SWU_BOT_PART69_FEATURES, 'teamread1' => SWU_BOT_PART69_FEATURES,
            'controlvalue' => SWU_BOT_PART44_FEATURES,
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
    // ('bobaspace', 'cravinganswer', 'wallarena', 'wallkeeparena' — the 'gapstack' four — SHIPPED 2026-10-12 as feature group 'p43'.)
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
    // ── 2026-10-08 OVERNIGHT LEVER SCREEN (owner: "50 brand new levers … based on the stuff we worked on today") — alternatives to
    // the p42 features; each is screened against the full stack on its focus deck (.claude/tmp/lever-screen-20261008/run_all.sh, gitignored).
    // 2026-10-09: the tuck re-buy share SHIPPED at 0.25 (0.1 / 0 probed better but switch off the owner's loops); 16 dead / harmful /
    // faded levers DELETED (results: memory lever-screen-2026-10-09).
    // Numeric levers (an alternative value of a p42 knob — SWU_BOT_LEVER_VALUES below):
    // 2026-10-11: 'mandoclaimx2' (CLAIM_DRAW_MULT 2.0) DELETED — the 2026-10-10 overnight screen measured it −8.8pp on Mando Colossus
    // (5:28 paired, p .0001); the shipped multiplier 1.0 stands.
    'mandoclaimhalf', 'supporthalf', 'basehealx2', 'basehealhalf', 'anakinpitchx2', 'anakinpitchhalf', 'sentsac2', 'barefodder1', 'yodaloop4', 'yodaloop6', 'pitchline15', 'pitchline7', 'attackreservehalf', 'race2', 'race4', 'healpress20', 'healpress10', 'healunit075', 'searchhalf', 'searchx2',
    // Behaviour levers (a p42 rule read another way — gated at their site with SWUBotProposalOn):
    'cleanupkillonly', 'cleanupall', 'cleanupclock', 'bokatan4r', 'pitchdeck', 'stabkeepdup', 'sentsacdoomed', 'forcerefillonly', 'forcespendonly', 'yodapartial', 'landoflipaction', 'deployswingall', 'doubleplaycost',
    // ── 2026-10-10 HUMAN-GAME LEVER SCREEN. New levers derived from Ninin's human-vs-human logs (private + public games), NOT
    // from bot traces (those games never hit BotData). Each is screened against the full stack on its focus deck; default OFF;
    // each has a mutation-checked guard test. See memory `swusim-human-game-levers-2026-10-10`.
    // Fatigue / deck-out (CR §6.1, 3 damage per undrawable card, 2 drawn per regroup). Maul vs Hemlock was decided by a
    // deck-out finish — nothing modelled the opponent's deck-out as a clock. The three arms each enable the behaviour AND set
    // the horizon (opp deck size at which it activates): fatiguewin = 5, fatiguehorizon3 = 3, fatiguehorizon8 = 8.
    'fatiguewin', 'fatiguehorizon3', 'fatiguehorizon8',
    'fatigueself',      // my deck about to deck-out + I have a board: stop drawing, close (SWUBotDrawMultiplier)
    'millcredit',       // milling THEIR deck is worth its deck-out damage (3/card) when their deck is low
    // Heal as race equity (Chimaera/Owen Lars Restore out-races Boba burn; the bot "can't close vs a healing deck"). A seat's
    // recurring base-heal (Restore N) raises ITS effective base HP in the race math — symmetric, so it covers both "don't count
    // damage the opponent heals back as progress" (attacking a healer) and "my heal is survival equity" (racing while I heal).
    'healrace',
    // Tempo layer (owner-wanted, deferred): value an exhaust effect by the enemy attack it denies this round (Vane/Hemlock tempo).
    'exhaustlock',      // scale the 'exhaust' tag by the opponent's best ready attacker — big threat = worth more, empty board = ~0
    // "Take control of a unit, then defeat it" (JTL_043 No Glory, Only Results): the stolen unit dies under MY control, so its When
    // Defeated fires for me. Kyzzy (Hemlock) stole Ninin's Annihilator and its When Defeated killed Anakin and stripped the other Anakin
    // from hand. The take-control picker ranked targets by unit value alone.
    // ('noglorywd' — SHIPPED 2026-10-21 as feature group 'p60' / 'stealwd'.)
    // ── 2026-10-11 KRENNIC WORKSHOP — owner rulings answering memory `krennic-underperformance-2026-10-11` (bot Krennic 25% vs the exact
    // bot Hemlock list the owner beat 4/5; it deploys R6 vs the owner's R11 and claims initiative ~2x as often per round).
    'krennicdeploy', 'krennicdeploy4', 'krennicdeploy6',   // Q1: deploy LAW_008 only for a BIG strike from a host that sticks (power
                                                           // >= 5 / 4 / 6) or once stabilised — "saving for a big unit in play that i
                                                           // know would stick … or if i knew i was already stabilized i deployed to apply pressure"
    'claimspent',       // Q2: control wing PASSES instead of claiming initiative while the opponent can still act (claiming forfeits the rest
                        // of the round; 239 of 279 traced Krennic claims came from the fallback's flat +0.05 over pass)
    'claimbomb',        // Q2: claim early when I hold next-round removal for their bomb (cost >= 5) and they are spent — "they just played a
                        // bomb and i know they won't play anything else or attack with anything else"
    'nogideon',         // Q3: ASH_097 Moff Gideon is never sacrificed (Credit Action, Chimaera, any sacrifice pick)
    'chimaeraweakest',  // Q3: Chimaera's friendly pick is the WEAKEST unit (lowest unit value), not the cheapest after When Defeated payback
    // ── 2026-10-11 HUMAN-GAME RESOURCING — Ninin's 22 human games on Krennic Blue Splash v2 vs 600 bot games on the same list
    // (human-game read, 2026-10-11): the bot's OPENING resource pick banks the cards she wins with.
    'stylemap',         // "is the opponent aggro" read from the meta's leader + BASE map (BotOpponentStyles.php), not SWU_BOT_AGGRO_LEADERS. bot_stylemap_test
    'rampres',          // credit-ramp control seat: keep answers + ramp pieces, Lawbringer vs aggro, HSD resourced unless space. bot_rampres_test
    // ── 2026-10-12 HUMAN-GAME READ — the owner's 31 games on Maul Blue (2-8), Grievous Blue (9-1) and Krennic Splash (6-5); memory
    // the 2026-10-12 human-game read. General rules for the midrange / control seats; group '@try-leaderwatch'.
    // ASH meta screen 2026-10-13: neutral (2.4% of games changed vs the plain stack, no style significant).
    'deploysafe',       // midrange/control (racing rank >= 2) holds a deploy whose leader unit dies at the opponent's next action (a ready
                        // enemy that can attack and kill it, or an enemy deploy strike that can go now) — unless the deploy itself defeats
                        // an enemy unit. ~12 opponent leaders died within one action of deploying. bot_deploysafe_test
    'leaderhunt',       // a kill of a deployed ENEMY LEADER unit carries a premium (it cannot deploy again): x W['kill'], so control most,
                        // aggro least. Grievous killed 9/9 deployed leaders, Krennic answered 9/10. bot_leaderhunt_test
    // ('handpick' — SHIPPED 2026-10-19 as part of feature group 'p56' / 'controlkit'.)
    // ('handhate', 'leaderkill', 'freshsac', 'maulpitch', 'condetb', 'gloom', 'wdattack', 'spenttrade', 'enginekill', 'cleanupsoft' — the
    // 2026-10-12 re-read's ten owner-ruled rules — SHIPPED 2026-10-13 as feature group 'p44' / 'controlvalue'.)
    // ('nightpick', 'wdbait', 'ambushsetup' — the 2026-10-13 deep read of the Maul Blue v2 games — SHIPPED 2026-10-13 as feature group 'p46'.)
    // Levers considered and DROPPED after grounding against the real scorer (kept as notes so they are not re-proposed):
    //   legacysplitkill — _SWUBotSplitScore already scores W['kill'] x value + 0.5 per killed target, plus 'splitlethal'.
    //   debuffkill      — _SWUBotWeaknessScore already returns a KILL when a -X/-X's loss >= the target's remaining HP (~line 1159).
    //   graveyardtheft  — _SWUBotPlayValue already prices a play-from-discard ('discard' route); opponent-discard theft is a ~2-card niche.
    //   (handhate was dropped here for want of a tag, then built on the discard-opponent-hand tag and shipped in p44.)
    //   softenkill      — 'popkill' for plain damage (an attack that leaves its target alive, credited the kill a follow-up then makes).
    //                     Maul v3 single-rule screen 2026-10-13: −16 (24:40, p .06) — DROPPED by the owner.
    //   recursionwipe   — no "overcommit into the opponent's wipe" penalty exists to discount; would need a new penalty built first.
    //   saboteurgrant   — needs a dedicated board computation (does the grant give a specific attacker lethal through a Sentinel).
    // 2026-10-14 Krennic Blue vs Ahsoka Blue canary (10k games, Krennic 32.6%): LAW_037 Han Solo ("On Attack: Give an Experience token to
    // this unit") survived the game in 50% of Krennic's losses vs 28% of its wins, and dealt 5.1 base damage a game in losses vs 2.9. A kill
    // of a unit that GROWS on attack earns the base damage its next rounds would deal. _SWUBotGrowKillPremium (BotFallback.php).
    'growkill',
    // Same canary, owner 2026-10-14 ("let's test those 2 and then also test this line … build those 4 features and run 10k games each"):
    'popfirst',      // the Credit Action's pick pops an enemy Shield first when it survives (BotCreditEngine.php)
    'rewall',        // vs aggro: never sacrifice the last Sentinel in a contested arena; 'wall-first' through round 6
    'mercramp',      // R1 weenie + sacrifice → R2 Expendable Mercenary on 3 resources + the Credit, sacrificed the same round (rule 'merc-line')
    'krennicmerc',   // R1 Krennic unit (JTL_032) → R2 Mercenary at the discount, sacrificed the same round (rule 'merc-line')
    // ('discloseanswer', 'selfburnguard', 'plotpick', 'hostileexhaust', 'nohostiletarget', 'downgradehost' — the 2026-10-15 lever hunt's bug
    // class — SHIPPED 2026-10-15 as feature group 'p50' / 'bugfix1'.)
    // 2026-10-15 ASH lever hunt, STEP 2 — the style-gradient levers, one ASH arm each (owner: "proceed with your suggested order").
    'lukeaggro',          // G4-01: Luke JTL on Data Vault reads as an aggro leader (SWUBotOpponentIsAggroLeader)
    'indirectprice',      // G1-05/G2-18: the 'indirect-damage' tag takes the base-damage weight (it priced 0 after the tags v3 split)
    'pilotcost',          // G2-24/G3-09: a Piloting card costs its Piloting cost while a Vehicle can carry it (resourcing, curve, mulligan)
    'enginedeploy',       // G3-05/G3-06: a control leader with a repeatable front Action stays home until round 7 unless under pressure
    // ('kyloengine', 'leiadisclose', 'talzinforce', 'armorerengine', 'chewieline', 'koskaorder', 'asajjping' — the lever hunt's step 3 deck
    // engines — SHIPPED 2026-10-16 as feature group 'p52' / 'engines3'.)
    // ('removalfirst', 'mandoclaimplay', 'mandodrawattack', 'landoflipbomb', 'homeonewall', 'dedratarget', 'outpostepic' — the lever hunt's
    // step 4a — SHIPPED 2026-10-17 as feature group 'p53' / 'hardctl4'.)
    // Step 2 measured 2026-10-16 vs baseline v2.3 (archetype Pearson +0.11 / MAE 0.094): lukeaggro null (+0.12 / 0.095, Luke DV rose);
    // indirectprice aggro +29 / control −39 (+0.10 / 0.096) — HOLD, pair with control-side levers; pilotcost soft aggro +109 / soft control −62
    // (+0.09 / 0.100); enginedeploy Piett Colossus −27pp, control −216 (+0.01 / 0.100). ('mullfloor', 'actionbeforeclaim' SHIPPED as p51.)
    // Bug report game 1647080 (2026-10-19): the bot played a second Blue Leader while the copy in play was READY at 2 damage — the
    // uniqueness rule defeated the old copy and its attack was thrown away. A second copy of a unique unit I control now waits while
    // the copy in play is ready with an attack that scores above zero; once it is spent the replacement is priced as before.
    'uniqueattackfirst',
    // Owner 2026-10-19 (Ahsoka flip turn; "split it" for every Plot deck). 'flippackage': the resourcer banks the deck's FLIP PACKAGE first (owner
    // table — Ahsoka: Naboo Royal Starship + Jar Jar, else 2 Jar Jars — or the most valuable Plot set that fits the deploy threshold), every
    // copy of a missing card; the Plot window plays that package first. 'flipholdhand': flip-dependent Plot cards (a leader-unit grant, an
    // on-play phase buff) wait in hand for the resources while the leader is undeployed; a banked buff is cast only to clear a Sentinel or
    // set up lethal next round.
    'flippackage', 'flipholdhand',
    // 'exhausteddiscount' (owner 2026-10-20, after readykill/readythreat on game 1647080: "we may need to devalue exhausted units a bit more
    // to ensure this kind of pricing"): an EXHAUSTED enemy cannot attack this round, so the body share of an attack-kill on it is worth
    // EXHAUSTED_KILL_SHARE (0.75) of a ready one's — in _SWUBotUnitTargetValue, so every style and every caller of that pricing sees it.
    'exhausteddiscount',
    // Owner rulings 2026-10-21 (Lando Blue vs Hemlock DV human games, docs/superpowers/swuforge-research/2026-10-21_lando-blue_bot-feature-report.md):
    // 'millclock': "after flip, if Lando blue has 10+ resources or Lando LC has 8+ resources, then mill the opponent" — the post-flip Action is a
    // real play (above the initiative claim) instead of a Credit worth nothing once the hand is castable. Bot 28% of spare post-flip rounds vs
    // softcontrol, the human 75%. 'rebuildafterwipe': "rebuild is best especially if their wipe cost them all their resources" — the
    // 'wipeaware' / 'wipecap' holds lift while an opponent who has SHOWN a wipe cannot pay for another this round.
    'millclock', 'rebuildafterwipe',
    // ('budgetwipe' — SHIPPED 2026-10-22 as feature group 'p61'.)
];


// The numeric levers of the 2026-10-08 screen: proposal => [knob, value]. Code reads a knob with SWUBotLeverNum(knob, default): the
// default (the shipped p42 value) unless one of that knob's proposals is on.
const SWU_BOT_LEVER_VALUES = [
    'mandoclaimhalf' => ['CLAIM_DRAW_MULT', 0.5],
    'supporthalf' => ['SUPPORT_ATTACK_SHARE', 0.5],
    'basehealx2' => ['BASEHEAL_MULT', 2.0],
    'basehealhalf' => ['BASEHEAL_MULT', 0.5],
    'anakinpitchx2' => ['ANAKIN_PITCH_MULT', 2.0],
    'anakinpitchhalf' => ['ANAKIN_PITCH_MULT', 0.5],
    'sentsac2' => ['SENTINEL_SAC_PREMIUM', 2.0],
    'barefodder1' => ['BARE_FODDER_DISCOUNT', 1.0],
    'yodaloop4' => ['YODALOOP_FROM', 4.0],
    'yodaloop6' => ['YODALOOP_FROM', 6.0],
    'pitchline15' => ['PITCH_LINE', 1.5],
    'pitchline7' => ['PITCH_BEFORE_RESOURCES', 7.0],
    'attackreservehalf' => ['ATTACK_RESERVE_MULT', 0.5],
    'race2' => ['DEPLOYSWING_RACE', 2.0],
    'race4' => ['DEPLOYSWING_RACE', 4.0],
    'healpress20' => ['HEAL_BASE_PRESSURE', 20.0],
    'healpress10' => ['HEAL_BASE_PRESSURE', 10.0],
    'healunit075' => ['HEAL_UNIT_RATE', 0.75],
    'searchhalf' => ['SEARCH_MULT', 0.5],
    'searchx2' => ['SEARCH_MULT', 2.0],
];
function SWUBotLeverNum(string $knob, float $default): float {
    foreach (SWU_BOT_LEVER_VALUES as $p => [$k, $v]) if ($k === $knob && SWUBotProposalOn($p)) return $v;
    return $default;
}

// 2026-10-10 human-game screen. The fatigue (deck-out) behaviour is enabled by any of its three arms, each carrying its own
// horizon (the opponent deck size at which deck-out damage starts counting as a clock). Kept as one on/off helper so the hook
// sites read the same gate.
function SWUBotFatigueOn(): bool {
    return SWUBotProposalOn('fatiguewin') || SWUBotProposalOn('fatiguehorizon3') || SWUBotProposalOn('fatiguehorizon8');
}
// 2026-10-11 Krennic workshop: the deploy rule is enabled by any of its three arms; each carries its "big host" power bar.
function SWUBotKrennicDeployOn(): bool {
    return SWUBotProposalOn('krennicdeploy') || SWUBotProposalOn('krennicdeploy4') || SWUBotProposalOn('krennicdeploy6');
}
function SWUBotKrennicDeployBig(): int {
    if (SWUBotProposalOn('krennicdeploy4')) return 4;
    if (SWUBotProposalOn('krennicdeploy6')) return 6;
    return 5;
}
function SWUBotFatigueHorizon(): int {
    if (SWUBotProposalOn('fatiguehorizon3')) return 3;
    if (SWUBotProposalOn('fatiguehorizon8')) return 8;
    return 5;
}

function SWUBotProposalList(): array {
    return array_merge(SWU_BOT_PROPOSALS, function_exists('SWUBotLeverProposals') ? SWUBotLeverProposals() : []);   // + the lever registry (BotLevers.php)
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
                                 'mullfix'     => ['mullhandsize', 'mulleventclamp', 'mullanswer'],   // 2026-10-07 gap screen
                                 // ('gapstack' SHIPPED 2026-10-12 as feature group 'p43' — '@no-p43' switches the four off.)
                                 // 2026-10-11 Krennic workshop: all five owner rulings together.
                                 'krennicws'   => ['krennicdeploy', 'claimspent', 'claimbomb', 'nogideon', 'chimaeraweakest'],
                                 // 2026-10-12 human-game read: the three general rules together.
                                 'leaderwatch' => ['deploysafe', 'leaderhunt'],   // ('handpick' shipped 2026-10-19, p56)
                                 // 2026-10-19 owner's Plot flip plan (Ahsoka; split for every Plot deck): both halves together.
                                 'flipplan'    => ['flippackage', 'flipholdhand'],
                                 // ('tempolines' SHIPPED 2026-10-13 as feature group 'p46', without 'softenkill', which was dropped.)
                                 // (the re-read's ten SHIPPED 2026-10-13 as feature group 'p44' / 'controlvalue'.)
                                 // 2026-10-15 Krennic vs Ahsoka canary: the two winners together (popfirst +63, krennicmerc +94 alone).
                                 'creditpop'   => ['popfirst', 'krennicmerc'],
                                 // ('finalists' SHIPPED 2026-10-19 as feature group 'p54'.)
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
        $lg = function_exists('SWUBotLeverGroups') ? SWUBotLeverGroups() : [];   // a lever THEME switches its levers on together
        if (isset($lg[$p])) return array_map(fn($x) => "try:$x", $lg[$p]);
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
                       array_map(fn($g) => "try-$g", array_keys(SWU_BOT_PROPOSAL_GROUPS)),
                       array_map(fn($g) => "try-$g", array_keys(function_exists('SWUBotLeverGroups') ? SWUBotLeverGroups() : [])));
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

// The lever registry (2026-10-17): one file per lever under Custom/levers/, each an off-by-default proposal.
require_once __DIR__ . '/BotLevers.php';
