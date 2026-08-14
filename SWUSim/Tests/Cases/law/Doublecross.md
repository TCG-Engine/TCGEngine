# ExchangeControlCredits
#// LAW_170 Double-Cross (Command event, cost 6) — "Choose a friendly non-leader unit and an enemy
#// non-leader unit. Exchange control of those units. The player who takes control of the lower-cost unit
#// creates Credit tokens equal to the difference between costs." Friendly SOR_046 (cost 4) swaps with
#// enemy SEC_080 (cost 2); caster takes the cheaper SEC_080 -> caster creates 2 Credits.
#// COVERAGE: offer=never left pending — every fixture has a single legal friendly/enemy so the picks
#//           auto-resolve; the non-leader requirement is covered only via the NoEnemy/NoFriendly no-op
#//           pair · decline=N/A (no optional clause; the no-target no-ops are the closest analogue) ·
#//           control=the card IS a control exchange (all exchange sections); blocked-transfer edge =
#//           the two Rey sections · boundary=EqualCost_NoCredits (zero difference) vs
#//           FriendlyCostsLess_OpponentGetsCredits / CrossArena_EnemyCostsLess_PlayerGetsCredits (each
#//           credit direction) · reqboundary=N/A (auto-resolved picks — no mid-effect answer splits in
#//           these fixtures)

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P1CREDITCOUNT:2

---

# NoEnemyNonLeaderUnit_NoOp
#// LAW_170 Double-Cross — with NO enemy non-leader unit in play there is nothing to exchange control
#// with, so the event does nothing: the friendly Battlefield Marine stays under P1 control and no
#// Credits are created.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# NoFriendlyNonLeaderUnit_NoOp
#// LAW_170 Double-Cross — with NO friendly non-leader unit to give away, the event does nothing: the
#// enemy Battlefield Marine stays under P2 control and no Credits are created.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# FriendlyCostsLess_OpponentGetsCredits
#// LAW_170 Double-Cross — exchange a friendly Battlefield Marine (cost 2) for an enemy AT-ST (cost 6).
#// P2 takes control of the lower-cost unit (the Marine), so P2 creates Credits equal to the difference
#// (6-2 = 4). P1 takes the AT-ST.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_232:1:0
WithP1Hand: LAW_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:0
P2CREDITCOUNT:4

---

# CrossArena_EnemyCostsLess_PlayerGetsCredits
#// LAW_170 Double-Cross — units from different arenas may be exchanged: friendly ground Wampa (cost 4)
#// swaps with enemy space Cartel Spacer (cost 2). P1 takes the lower-cost Cartel Spacer, so P1 creates
#// Credits equal to the difference (4-2 = 2).

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_178:1:0
WithP1Hand: LAW_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_178
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P1CREDITCOUNT:2
P2CREDITCOUNT:0

---

# EqualCost_NoCredits
#// LAW_170 Double-Cross — when the two exchanged units cost the same (Wampa cost 4 vs Loan Shark cost 4)
#// the control swap still happens but NO Credits are created for either player.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SEC_222:1:0
WithP1Hand: LAW_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_222
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# ReyIsFriendly_LowerCostTaken_OnlyOneExchanged_PlayerGetsCredits
#// LAW_170 Double-Cross with LAW_149 Rey — Rey is chosen as the friendly unit but her control cannot be
#// taken, so she stays with P1. P1 takes the lower-cost Cartel Spacer (cost 2 vs Rey cost 8), so P1
#// creates Credits equal to the difference (8-2 = 6).

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1GroundArena: LAW_149:1:0
WithP2SpaceArena: SOR_178:1:0
WithP1Hand: LAW_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_149
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_178
P2SPACEARENACOUNT:0
P1CREDITCOUNT:6
P2CREDITCOUNT:0

---

# ReyIsFriendly_HigherCostEnemyTaken_NoCredits
#// LAW_170 Double-Cross with LAW_149 Rey — Rey (cost 8) is the friendly unit and P1 takes the HIGHER-cost
#// enemy (Krayt Dragon, cost 9). The player who would take the LOWER-cost unit (Rey) is P2, but Rey's
#// control can't be taken, so P2 never receives her → NO Credits are created for either player. P1 keeps
#// Rey and gains the Krayt Dragon.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1GroundArena: LAW_149:1:0
WithP2GroundArena: SHD_172:1:0
WithP1Hand: LAW_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# ExchangeGivesOpponentADuplicateUnique_TheyMustDefeatOne
#// CR 8.19.1.b applies to EVERY player, not just the acting one: the exchange hands P2 a second Jyn
#// Erso (unique) while P2 already controls their own copy. P2 must choose and defeat copies down to
#// one before the action ends. P2 defeats the received copy (it goes to owner P1's discard, joining
#// the event); the credit math is unaffected (P2 took the cheaper unit: 5-2 = 3 Credits).

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1GroundArena: LAW_067:1:0
WithP2GroundArena: SOR_067:1:0
WithP2GroundArena: LAW_067:1:0
WithP1Hand: LAW_170

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_067
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_067
P2CREDITCOUNT:3
P1DISCARDCOUNT:2
