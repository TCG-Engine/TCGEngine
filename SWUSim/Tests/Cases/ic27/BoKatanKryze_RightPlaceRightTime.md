# WhenPlayed_EachEnemyUnitMinusSeven
#// IC27_163 Bo-Katan Kryze — Right Place, Right Time (7/6 Ground, Cunning/Heroism, Mandalorian, unique)
#// "Shielded. When Played: Give each enemy unit -7/-0 for this phase."
#// Shielded is printed (auto-wired); it and the When Played are two entry triggers, so every play answers the
#// trigger-order pick (EffectStack-0 = the When Played). The When Played snapshots the enemy units in play as it resolves
#// ('their' — team-aware, every opponent seat) and gives each a registered IC27_163 STAT_DEBUFF (-7/-0, phase).
#// Power floors at 0; there is no HP half, so nothing can die from it.
#//
#// COVERAGE: offer=N/A (no choice — "each enemy unit") ·
#//           decline=N/A (no "you may") ·
#//           boundary=N/A (no numeric threshold; the -7 vs an 8-power unit pins the amount: 8 -> 1) ·
#//           control=Control_StolenUnit_FollowsItsController (an enemy-OWNED unit P1 controls is friendly) ·
#//           reqboundary=PlayThenAttack_AcrossRequestBoundary (the phase debuff must survive between actions) ·
#//           modes=2P,TwinSuns,TeamSuns (text says "enemy") · TwinSuns=TwinSuns_EveryOpponentSeat ·
#//           TeamSuns=TeamSuns_TeammateIsNotEnemy
#//
#// P2's Battlefield Marine (3) and Alliance X-Wing (2) drop to 0; P2's Kanan Jarrus (8/7) drops to 1;
#// P1's own Battlefield Marine is untouched. Bo-Katan gets her Shield.

## GIVEN
CommonSetup: yyw/rrk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_163
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: IC27_121:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:1:POWER:1
P2SPACEARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:CARDID:IC27_163
P1GROUNDARENAUNIT:1:POWER:7
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# EnemyLeaderUnit_IsAffected
#// Value class: an enemy deployed LEADER unit is an enemy unit (SOR_010 Darth Vader deployed 5/8 -> 0).

## GIVEN
CommonSetup: yyw/rrk/{myResources:7;theirLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_163

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:POWER:0

---

# DebuffExpires_AtTheEndOfThePhase
#// Duration: "for this phase". After the action phase ends the Marine is back to 3.

## GIVEN
CommonSetup: yyw/rrk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_163
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>Pass

## EXPECT
PHASEISNOT:MAIN
P2GROUNDARENAUNIT:0:POWER:3

---

# UnitEnteringLater_IsNotAffected
#// Snapshot, not an aura: a unit P2 plays AFTER Bo-Katan resolves keeps its full power.

## GIVEN
CommonSetup: yyw/rrk/{myResources:7;theirResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: IC27_163
WithP2Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:3

---

# PlayThenAttack_AcrossRequestBoundary
#// The debuff is real in combat and survives a request boundary: a debuffed 4/7 Industrious Team counters a
#// P1 Consular Security Force (3/7) for 0.

## GIVEN
CommonSetup: yyw/rrk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_163
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Control_StolenUnit_FollowsItsController
#// "enemy" is read by CONTROLLER: a Battlefield Marine OWNED by P2 but controlled by P1 is friendly and keeps
#// its power; P2's own Marine is debuffed.

## GIVEN
CommonSetup: yyw/rrk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_163
WithP1GroundArenaControlled: SOR_095:2
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:POWER:0

---

# TwinSuns_EveryOpponentSeat
#// Twin Suns: every other seat is an opponent — P2, P3 and P4's units all get -7/-0.

## GIVEN
CommonSetup: yyw/rrk/{myResources:7}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: IC27_163
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:POWER:0
P3GROUNDARENAUNIT:0:POWER:0
P4SPACEARENAUNIT:0:POWER:0

---

# TeamSuns_TeammateIsNotEnemy
#// Team Suns: teammate P3's unit is NOT an enemy and keeps its power; P2 and P4 are debuffed.

## GIVEN
CommonSetup: yyw/rrk/{myResources:7}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: IC27_163
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:POWER:0
P3GROUNDARENAUNIT:0:POWER:3
P4GROUNDARENAUNIT:0:POWER:0
