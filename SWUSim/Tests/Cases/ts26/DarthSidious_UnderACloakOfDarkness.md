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
- P1>AnswerDecision:theirSpaceArena-0

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
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
