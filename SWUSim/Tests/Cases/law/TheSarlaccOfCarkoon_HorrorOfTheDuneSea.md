# OnAttackDiscardUnitDealPower
#// LAW_163 The Sarlacc of Carkoon (8/9) — On Attack: put a unit from your discard on the bottom of your
#// deck; deal damage equal to that unit's power to an enemy ground unit. SOR_046 (power 3) from discard
#// -> deal 3 to the enemy SOR_046 in play.

## GIVEN
CommonSetup: grk/bgw/{discardCardIds:SOR_046}
P1OnlyActions: true
WithP1GroundArena: LAW_163:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1DECKCOUNT:1
P1DISCARDCOUNT:0

---

# NoUnitsInDiscard
#// LAW_163 The Sarlacc of Carkoon — On Attack: if there are NO units in your discard pile, the ability
#// does nothing (only an event, Vanquish SOR_078, is in discard). Enemy AT-ST (SOR_232) takes 0 damage.

## GIVEN
CommonSetup: grk/bgw/{discardCardIds:SOR_078}
P1OnlyActions: true
WithP1GroundArena: LAW_163:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1

---

# NoEnemyGroundUnit_StillMoves
#// LAW_163 The Sarlacc of Carkoon — On Attack: even with no enemy GROUND unit to damage, you still move a
#// unit from discard to the bottom of your deck. Enemy only has a space unit (A-Wing SEC_213); move Wampa
#// (SOR_164) from discard -> bottom of deck; no damage dealt.

## GIVEN
CommonSetup: grk/bgw/{discardCardIds:SOR_164}
P1OnlyActions: true
WithP1GroundArena: LAW_163:1:0
WithP2SpaceArena: SEC_213:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DISCARDCOUNT:0
P1DECKCOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0

---

# ZeroPowerUnitDealsZero
#// LAW_163 The Sarlacc of Carkoon — On Attack: moving a 0-power unit (Moisture Farmer SHD_055) deals 0
#// damage to the enemy ground unit. Moisture Farmer -> bottom of deck; AT-ST (SOR_232) takes 0.

## GIVEN
CommonSetup: grk/bgw/{discardCardIds:SHD_055}
P1OnlyActions: true
WithP1GroundArena: LAW_163:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:0
P1DECKCOUNT:1
