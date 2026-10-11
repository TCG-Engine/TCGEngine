# Offer_DamagedUnitsOnBothSides_UndamagedExcluded
#// COVERAGE: offer=this section · decline=Decline_NoToken
#//           boundary=this section (the undamaged SOR_046 is out, every unit with 1+ damage is in)
#//           control=N/A (no owner-scoped zone or "your" wording; the token lands on the chosen unit)
#//           reqboundary=AcrossTheRequestBoundary · no-target=NoDamagedUnit_NoPrompt
#//           cross-player=DefeatedOnTheOpponentsTurn_StillOffers
#//           modes=2P only (no player reference; "a damaged unit" has no friendly/enemy wording)
#//
#// HMW_087 Venomous Wyyyshokk — Unit (Ground) 3/4, cost 3, [Vigilance], Creature.
#// "When Defeated: You may give a Weakness token to a damaged unit."
#//
#// The Wyyyshokk (3/4) attacks LAW_124 Industrious Team (4/7): it takes 4 and dies, LAW_124 takes 3.
#// P1's SOR_237 Alliance X-Wing (space) already has 1 damage; P2's SOR_046 (ground 1) has none.

## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_087:1:0
WithP1SpaceArena: SOR_237:1:1
WithP2GroundArena: [LAW_124:1:0 SOR_046:1:0]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1SELECTABLEEXACT:mySpaceArena-0&theirGroundArena-0

---

# GivesWeaknessToTheDamagedAttacker

## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_087:1:0
WithP1SpaceArena: SOR_237:1:1
WithP2GroundArena: [LAW_124:1:0 SOR_046:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# Decline_NoToken

## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_087:1:0
WithP1SpaceArena: SOR_237:1:1
WithP2GroundArena: [LAW_124:1:0 SOR_046:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# NoDamagedUnit_NoPrompt
#// A Shield on LAW_124 absorbs the Wyyyshokk's 3, so no unit on the board is damaged when it dies.

## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_087:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# WeaknessCanDefeatADamagedUnit
#// SOR_095 3/3 with 2 damage takes -1/-1: 2 HP with 2 damage — defeated by the Weakness itself.

## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_087:1:0
WithP2GroundArena: [LAW_124:1:0 SOR_095:1:2]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2DISCARDCOUNT:1

---

# DefeatedOnTheOpponentsTurn_StillOffers
#// P2's LAW_124 attacks and kills the Wyyyshokk. The When Defeated belongs to P1, whose queue is not
#// drained inside P2's action — P1>Drain surfaces the offer.

## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: HMW_087:1:0
WithP2GroundArena: LAW_124:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_087:1:0
WithP1SpaceArena: SOR_237:1:1
WithP2GroundArena: [LAW_124:1:0 SOR_046:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
