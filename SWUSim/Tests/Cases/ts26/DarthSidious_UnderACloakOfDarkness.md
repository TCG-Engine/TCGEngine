# BuffsOtherSeparatists
#// TS26_13 Darth Sidious (Unit 4/6, cost 6) — Hidden. Each OTHER friendly Separatist unit gets +1/+0.
#// The friendly Battle Droid (TS26_T01, Separatist) gets +1 power; the Imperial SEC_080 is unaffected;
#// Sidious himself is not buffed (the grant is to OTHER units).
## GIVEN
CommonSetup: ggk/rrk
WithP1GroundArena: [TS26_13:1:0 TS26_T01:1:0 SEC_080:1:0]
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:2:POWER:3

---

# DroidOnNonTokenDefeat
#// TS26_13 Darth Sidious — "When a non-token unit is defeated: create a Battle Droid token." LAW_124
#// attacks and defeats the enemy SOR_128 (a non-token unit); Sidious's controller creates a Battle Droid,
#// so P1's ground goes from 2 units (Sidious + LAW_124) to 3.
## GIVEN
CommonSetup: ggk/rrk
WithP1GroundArena: [TS26_13:1:0 LAW_124:1:0]
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:TS26_T01

---

# TokenDefeat_MakesNoDroid
#// TS26_13 Darth Sidious — the trigger is "when a NON-TOKEN unit is defeated". Super Battle Droid
#// (TWI_230, buffed to 5 power by Sidious) defeats the enemy Clone Trooper TOKEN, so no Battle Droid is
#// created and P1's arena stays at 2 units.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [TS26_13:1:0 TWI_230:1:0]
WithP2GroundArena: TS26_T02:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2

---

# SidiousOwnDefeat_StillMakesADroid
#// TS26_13 Darth Sidious — he was in play when the unit was defeated even when that unit is HIMSELF, so
#// his own death makes a Battle Droid. Sidious (4/6) attacks Army of the Dead (7/6): he deals 4 and takes
#// 7, dying. P1's arena ends holding exactly one unit — the token he left behind.
#// Before the batch-aware count, the collector asked "how many Sidious are in play NOW?" after he had
#// already been marked removed, so a lone Sidious dying produced nothing at all.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_13:1:0
WithP2GroundArena: LOF_236:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TS26_T01

---

# BoardWipe_OneDroidPerNonTokenUnitDefeated
#// TS26_13 Darth Sidious under Superlaser Blast (SOR_043, "Defeat all units"). Five units die at once:
#// Sidious, SOR_095, SOR_128 and TWI_230 are non-token (4 droids) while the Clone Trooper TOKEN is not.
#// The droids arrive after the wipe's target list was snapshotted, so all 4 survive in P1's arena and P2
#// is emptied.
#// Discriminating on TWO axes: the token must not add a 5th, and Sidious must keep counting for every
#// unit that died WITH him — the wipe resolves as one simultaneous defeat, so the board he is judged
#// against is the one that existed before any of it (SWUSimulDefeatBegin/End).

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_043
WithP1GroundArena: [TS26_13:1:0 SOR_095:1:0 TS26_T02:1:0]
WithP2GroundArena: [SOR_128:1:0 TWI_230:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:4
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TS26_T01
P1GROUNDARENAUNIT:3:CARDID:TS26_T01

---

# AbilityDamageTokenDefeat_MakesNoDroid
#// TS26_13 Darth Sidious — the token exclusion on the ABILITY-DAMAGE funnel. Every other token section
#// in this file kills the token by COMBAT or by a direct "defeat a unit" effect; those route through
#// CollectCombatStep3Triggers / SWUDefeatUnit-on-a-healthy-unit. Damage that reduces a token to 0
#// remaining HP is a third funnel (SWUDealDamageToUnit -> SWUDefeatUnit). LAW_206 That's a Rock kills
#// the enemy Battle Droid token; no droid is created, so P1's ground stays at 1 (Sidious alone).

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: LAW_206
WithP1GroundArena: TS26_13:1:0
WithP2GroundArena: TS26_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1

---

# ShrinkDefeatsToken_MakesNoDroid
#// TS26_13 Darth Sidious — the token exclusion on the STATE-CHECK funnel. SHD_037 Supreme Leader Snoke
#// gives each enemy non-leader unit -2/-2, which kills P1's 2/2 Clone Trooper token outright via
#// SWUCheckShrinkDefeats (no damage, no combat, no targeted effect). Sidious sits on P2's side next to
#// Snoke, so P2's ground must stay at exactly 2 — a spurious droid would make it 3.

## GIVEN
CommonSetup: rrk/bbk/{theirResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_T02:1:0
WithP2GroundArena: [SHD_037:1:0 TS26_13:1:0]

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2

---

# TokenAttackerDies_MakesNoDroid
#// TS26_13 Darth Sidious — the token exclusion when the token dies as the ATTACKER. Every other token
#// section kills it as the defender or by an effect; an attacker dying to counter-damage is collected
#// from the other side of CollectCombatStep3Triggers. P2's Clone Trooper token (2/2) attacks P1's
#// SOR_128 (3/1) and dies to the 3 counter-damage; P1's ground stays at 2 (Sidious + a damaged 128).

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P2OnlyActions: true
WithP1GroundArena: [TS26_13:1:0 SOR_128:1:0]
WithP2GroundArena: TS26_T02:1:0

## WHEN
- P2>AttackGroundArena:0:1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2

---

# AbilityDamageNonTokenDefeat_MakesExactlyOneDroid
#// ⚠ RED — REAL BUG (found 2026-09-18, NOT the reported token issue; left failing per the never-delete
#// -a-failing-test rule). A NON-token unit killed by ABILITY DAMAGE creates TWO Battle Droids, not one.
#// LAW_206 That's a Rock kills SOR_128 (3/1, vanilla) with one Sidious in play, so P1's ground must go
#// 1 -> 2. It goes to 3.
#//
#// ROOT CAUSE (traced): on the effect-defeat path SWUDefeatUnit collects the leave-play reactions
#// (CombatLogic.php:781) BEFORE it marks the unit removed — the ordering is deliberate and documented
#// at GameLogic.php:11796. Sidious's droid is created inside that collection, and SWUCreateUnitToken
#// runs _SWUAfterTokensCreated -> SWUCheckShrinkDefeats, whose sweep still sees the dying unit sitting
#// in the arena at 0 remaining HP and DEFEATS IT A SECOND TIME -> a second collection -> a second droid.
#// The observed trace:
#//   SWUDealDamageToUnit > SWUDefeatUnit > CollectWhenDefeatedTriggers > SWUCollectLeavePlayReactions
#//     > SWUCreateUnitToken > _SWUAfterTokensCreated > SWUCheckShrinkDefeats > SWUDefeatUnit(again)
#// It stops at two because the outer SWUDefeatUnit's re-read (CombatLogic.php:784) then sees `removed`.
#//
#// WHY ONLY THIS FUNNEL: combat marks its dead removed before collecting, and a direct "defeat a unit"
#// effect (SHD_079 Rival's Fall) leaves the unit at full HP so the shrink sweep does not re-defeat it.
#// Only DAMAGE leaves a still-in-arena unit at <= 0 remaining HP during the collection. Guarded both
#// ways by DirectDefeatEffect... and the combat sections above.
#// The shrink sweep's re-entrancy guard (gSWUInShrinkSweep, GameLogic.php:2912) does not help: the
#// outer call came from SWUDealDamageToUnit, not from a sweep, so no sweep is in progress.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: LAW_206
WithP1GroundArena: TS26_13:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TS26_T01

---

# DirectDefeatEffectNonToken_MakesExactlyOneDroid
#// TS26_13 Darth Sidious — the NEGATIVE partner of the RED section above, and the one that pins the
#// root cause to DAMAGE rather than to effect-defeats in general. SHD_079 Rival's Fall ("Defeat a
#// unit") defeats SOR_128 while it is still at full HP, so the shrink sweep triggered by the droid's
#// creation finds nothing to re-defeat and exactly one droid is created.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_079
WithP1GroundArena: TS26_13:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TS26_T01

---

# PilotedTokenDefeat_MakesNoDroid
#// TS26_13 Darth Sidious — attaching a Pilot does not stop a token being a token. P2's TIE Fighter token
#// (JTL_T01) carries Clone Pilot (JTL_108) as an upgrade; Rival's Fall defeats the whole unit and no
#// Battle Droid appears — P1's arena still holds only Sidious.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_079
WithP1GroundArena: TS26_13:1:0
WithP2SpaceArena: JTL_T01:1:0
WithP2SpaceArenaPilot: 0:JTL_108

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1

---

# PilotUpgradeDefeat_MakesNoDroid
#// TS26_13 Darth Sidious — a defeated UPGRADE is not a defeated unit. Confiscate (SOR_251) defeats the
#// Clone Pilot riding P2's TIE Fighter token; the TIE itself survives (space arena still 1) and no droid
#// is created. This is the "unit" half of the gate, as opposed to the "non-token" half above.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_251
WithP1GroundArena: TS26_13:1:0
WithP2SpaceArena: JTL_T01:1:0
WithP2SpaceArenaPilot: 0:JTL_108

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0.u0

## EXPECT
P2SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1

---

# TokenPilotedByALeaderDefeat_MakesNoDroid
#// TS26_13 Darth Sidious — a leader riding a token as a Pilot does not launder the token into a real
#// unit. P2's leader JTL_001 is deployed as a Pilot onto their TIE Fighter token; Rival's Fall defeats
#// that unit and still no droid is created.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8;theirLeader:JTL_001;theirLeaderDeployedPilot:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_079
WithP1GroundArena: TS26_13:1:0
WithP2SpaceArena: JTL_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1

---

# PilotLeaderUpgradeDefeat_MakesNoDroid
#// TS26_13 Darth Sidious — defeating the deployed PILOT LEADER itself (as an upgrade, via Confiscate)
#// is an upgrade defeat, not a unit defeat: the TIE Fighter token it was riding survives and no droid is
#// created. Pairs with TokenPilotedByALeaderDefeat, which removes the unit instead of the leader.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8;theirLeader:JTL_001;theirLeaderDeployedPilot:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_251
WithP1GroundArena: TS26_13:1:0
WithP2SpaceArena: JTL_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0.u0

## EXPECT
P2SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
