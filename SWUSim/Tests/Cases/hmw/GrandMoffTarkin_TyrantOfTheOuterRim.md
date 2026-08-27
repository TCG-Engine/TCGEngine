# DeployedLeaderWithSpaceOverrideEntersSpaceArena
#// HMW_004's deployed side is The Death Star, a SPACE unit — the first leader whose deployed
#// arena differs from the default. Deploy must consult the leaderUnitArena override
#// (LeaderDeployArena), not the plain CardTargetArena default.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004:0;myResources:9}

## WHEN
- P1>DeployLeader

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_004
P1GROUNDARENACOUNT:0
P1LEADER:DEPLOYED
P1LEADER:EPICUSED

---

# DeployedLeaderFixtureSeedsTheOverrideArena
#// The myLeaderDeployed FIXTURE must agree with a real deploy: it seeds the arena
#// LeaderDeployArena picks, not a hardcoded ground zone. Without this, any test that seeds a
#// deployed Tarkin would contradict the engine it is testing.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9}
P1OnlyActions: true

## WHEN

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_004
P1SPACEARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENACOUNT:0
P1LEADER:DEPLOYED

---

# DeployedLeaderHasDeployedSideTraits
#// The deployed side is a different printed face: The Death Star is an Imperial Vehicle Capital
#// Ship, NOT the leader side's Imperial Official. The leaderUnitTrait override REPLACES the
#// leader row's traits rather than adding to them.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9}
P1OnlyActions: true

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:HASTRAIT:Vehicle
P1SPACEARENAUNIT:0:HASTRAIT:Capital Ship
P1SPACEARENAUNIT:0:HASTRAIT:Imperial
P1SPACEARENAUNIT:0:NOTTRAIT:Official

---

# FortifyUpgradeIgnoresTheAspectPenalty
#// "Ignore the aspect penalties on upgrades with Fortify you play." Tarkin provides Vigilance + Villainy
#// and the `g` base provides Command, so HMW_171 Trap Field (cost 2, Aggression + Heroism) has BOTH pips
#// uncovered: 2 + 4 = 6 normally. On exactly 2 resources it can only attach if the waiver drops the whole
#// penalty, so the attach IS the assertion. (Baseline for the unwaived case:
#// keywords/Fortify.md::AFortifyUpgradePaysTheOffAspectPenalty.)

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myResources:2;myhandCardIds:HMW_171}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASE:UPGRADE:0:CARDID:HMW_171
P1RESAVAILABLE:0

---

# NonFortifyUpgradeStillPaysTheAspectPenalty
#// The waiver is scoped to upgrades WITH Fortify. SOR_166 Infiltrator's Skill is a cost-1 Aggression
#// upgrade with no Fortify, so Aggression stays uncovered: 1 + 2 = 3, unaffordable on 2 resources, which
#// makes PlayHand a silent no-op. Without the Fortify scoping it would attach for 1.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myResources:2;myhandCardIds:SOR_166}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:2

---

# DeployedRegroupDefeatsAnEnemyBaseAtTenOrLess
#// Deployed side: "When the regroup phase starts: You may defeat a base with 10 or less remaining HP."
#// P2's base is a 30-HP colour base at 25 damage = 5 remaining, so it qualifies; P1's own base is
#// undamaged (30 remaining) and must NOT be offered. Defeating a base means its controller loses, so
#// this ends the game — there is no separate "defeated base" state (CR 3.2.5).
#// Both decks are stocked so the regroup DRAW deals no deck-out damage (that alone would end the game
#// from 25 and hand a false pass).

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;theirBaseDamage:25}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>AnswerDecision:theirBase-0

## EXPECT
P1WIN

---

# DeployedRegroupMayDefeatYourOwnBase
#// The printed text says "a base" with no friendly/enemy qualifier, so YOUR OWN base is a legal target
#// when it is at 10 or less remaining HP — and defeating it makes YOU lose. Legal, not advisable.
#// Here only P1's base qualifies (25 damage = 5 remaining), so P2 wins.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;myBaseDamage:25}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>AnswerDecision:myBase-0

## EXPECT
P2WIN

---

# DeployedRegroupDeclineLeavesTheBaseAlive
#// It is a "may" — declining must leave the base exactly as it was and the game running.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;theirBaseDamage:25}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:25

---

# DeployedRegroupElevenRemainingIsNotOffered
#// The threshold is "10 or less remaining HP", so 11 remaining (30 - 19) must not qualify. Proven by
#// driving the regroup all the way through to the next action phase: an unoffered prompt lets the two
#// ResourcePasses reach PHASE:MAIN, while a wrongly-offered base-defeat would sit in front of them and
#// strand the game in the regroup. Guards a > / >= slip on the boundary.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;theirBaseDamage:19}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:19
PHASE:MAIN

---

# DeployedRegroupExactlyTenRemainingIsOffered
#// The inclusive edge: 10 remaining (30 - 20) qualifies.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;theirBaseDamage:20}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>AnswerDecision:theirBase-0

## EXPECT
P1WIN

---

# UndeployedTarkinHasNoRegroupBaseDefeat
#// The base-defeat clause is printed only on the DEPLOYED side (The Death Star). An undeployed Tarkin
#// keeps the aspect waiver but must offer nothing at the regroup phase — so the enemy base survives even
#// though it sits at 5 remaining HP; the regroup runs straight through to the next action phase.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myResources:9;theirBaseDamage:25}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:25
PHASE:MAIN

---

# FourSeats_RegroupDefeatsTheCHOSENSeatsBase
#// HMW_004 deployed — "You may defeat a base with 10 or less remaining HP." Unqualified, so every base at
#// the table that meets the threshold is offered. Here BOTH P2 (25 damage on a 30-HP base) and P4 (20 on
#// a 25-HP base) qualify, and P1 names P4. Two legacy shapes die on this: the offer used to be the literal
#// pair ['myBase-0','theirBase-0'], so p4Base-0 was not even a candidate; and the applier used to collapse
#// any non-"my" pick onto OtherPlayer(), which would eliminate P2 instead. For an ability whose entire
#// effect is "that player is out of the game", guessing the seat is the worst possible failure.
#// Only P4 leaves, so its TEAM (2 and 4) still has a live seat — the game must not be over.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9;theirBaseDamage:25}
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:20
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP4Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>AnswerDecision:p4Base-0

## EXPECT
SEATCOUNT:4
SEATLIVE:4:false
SEATLIVE:2:true
SEATLIVE:1:true
NOGAMEWINNER
