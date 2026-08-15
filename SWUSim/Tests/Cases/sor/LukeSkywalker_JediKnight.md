# FriendlyDefeated_Minus6
#// COVERAGE: offer=Offer_AllEnemyUnitsBothArenas (pending SELECTABLEEXACT: every enemy unit, both
#//           arenas, mandatory) · reqboundary=Minus6_ExpiresNextPhase (debuff applied in one
#//           request, expiry across the phase cross) · control=N/A (targets are enemy-only by
#//           text; no section changes control) · boundary pair=FriendlyDefeated_Minus6 (0/1) vs
#//           NoFriendlyDefeated_Minus3 + EnemyDefeatedOnly_StillMinus3 +
#//           FriendlyPilotDefeatedAsUpgrade_StillMinus3 (the friendly-UNIT gate) plus
#//           Minus6_ExpiresNextPhase (duration edge) · decline=N/A (mandatory target pick).
#// SOR_051 Luke Skywalker — the "-6/-6 if a friendly unit was defeated this phase" branch. P1's
#// SOR_210 (4/3) attacks an AT-ST and dies (a FRIENDLY unit defeated this phase). P2 passes, then P1
#// plays Luke and targets the SECOND, undamaged AT-ST → -6/-6 for the phase → 0/1. (Luke can't target
#// the first AT-ST + the -6 there: it already took 4 combat damage, so -6 HP would defeat it.)

## GIVEN
CommonSetup: bbw/bbw/{myResources:7}
WithP1GroundArena: SOR_210:1:0
WithP1Hand: SOR_051
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:POWER:0
P2GROUNDARENAUNIT:1:HP:1

---

# NoFriendlyDefeated_Minus3
#// SOR_051 Luke Skywalker (Unit 6/7, cost 7, Vigilance/Heroism, Restore 3) — "When Played: Give an
#// enemy unit -3/-3 for this phase. If a friendly unit was defeated this phase, give that enemy unit
#// -6/-6 for this phase instead." No friendly unit has been defeated this phase, so the basic -3/-3
#// applies. The single enemy (AT-ST, 6/7) auto-resolves → 3/4 for the phase.

## GIVEN
CommonSetup: bbw/bbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_051
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:4

---

# EnemyDefeatedOnly_StillMinus3
#// SOR_051 Luke Skywalker — the -6/-6 upgrade requires a FRIENDLY unit defeated this phase. An
#// ENEMY unit defeated this phase (P1's Vanquish kills the Marine) does not count: Luke still
#// gives only -3/-3, and the Wampa (4/5) ends the phase at 1/2.

## GIVEN
CommonSetup: bbw/rrk/{myResources:12;myhandCardIds:SOR_078,SOR_051}
P1OnlyActions: true
WithP2GroundArena: [SOR_095:1:0 SOR_164:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:2
P1NODECISION

---

# FriendlyPilotDefeatedAsUpgrade_StillMinus3
#// SOR_051 Luke Skywalker — a friendly PILOT defeated while attached AS AN UPGRADE is not a
#// friendly UNIT defeated. JTL_196 is played with Piloting onto the X-Wing, P2's Confiscate
#// defeats the pilot upgrade, and Luke still gives only -3/-3: the Wampa ends at 1/2.

## GIVEN
CommonSetup: bbw/rrk/{
  myResources:10;
  myhandCardIds:JTL_196,SOR_051;
  theirResources:1;
  theirhandCardIds:SOR_251
}
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:2
P1NODECISION

---

# Offer_AllEnemyUnitsBothArenas
#// SOR_051 Luke Skywalker — the debuff target pool is every ENEMY unit in BOTH arenas (and the
#// pick is mandatory once a friendly unit died this phase). SOR_128 trades with the Marine to
#// arm the -6 branch; Luke's play leaves the target choice PENDING: exactly [AT-ST, A-Wing]
#// (post-trade compacted indexes).

## GIVEN
CommonSetup: bbw/rrk/{myResources:7}
WithP1GroundArena: SOR_128:1:0
WithP1Hand: SOR_051
WithP2GroundArena: [SOR_095:1:0 SOR_232:1:0]
WithP2SpaceArena: SOR_141:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# Minus6_ExpiresNextPhase
#// SOR_051 Luke Skywalker — the -6/-6 is "for this phase": the AT-ST (6/7) is 0/1 after Luke's
#// play, and back to 6/7 once the phase turns over. Decks are seeded for the regroup draw.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7}
WithP1GroundArena: SOR_128:1:0
WithP1Hand: SOR_051
WithP2GroundArena: [SOR_095:1:0 SOR_232:1:0]
WithP1Deck: [SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_051
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:HP:7
PHASE:MAIN

---

# SimulateRequestBoundary_FriendlyDefeatedFlagAndTarget
#// SOR_051 Luke Skywalker — the enemy-unit target pick ends the request in production, so the answer
#// arrives in a fresh process: BOTH the pending debuff amount (-6 vs -3, decided by the
#// friendly-unit-defeated-this-phase flag set two actions earlier) and the pending target decision must
#// survive serialization. Mirrors FriendlyDefeated_Minus6 with the boundary inserted before the answer.

## GIVEN
CommonSetup: bbw/bbw/{myResources:7}
WithP1GroundArena: SOR_210:1:0
WithP1Hand: SOR_051
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:POWER:0
P2GROUNDARENAUNIT:1:HP:1
