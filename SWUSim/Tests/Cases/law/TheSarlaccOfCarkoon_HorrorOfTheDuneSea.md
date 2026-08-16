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

---

# OfferPool_OwnDiscardUnitsOnly
#// LAW_163 The Sarlacc of Carkoon — offer assertion for the FIRST pool, "Put a UNIT from YOUR DISCARD
#// pile on the bottom of your deck". Two restrictions: card type and pile ownership. Discriminating
#// board — P1's discard holds two units (SOR_046, SOR_128, both IN), an EVENT (SOR_078 Vanquish, OUT)
#// and an UPGRADE (SOR_120 Academy Training, OUT), while the OPPONENT's discard holds a unit (SOR_095,
#// OUT — "your discard pile"). Two legal picks keep the MZMAYCHOOSE pending; it is left UNANSWERED so
#// the pool can be read. The discard counts are asserted alongside so the two "out" piles are proven to
#// actually exist rather than to have failed to seed.

## GIVEN
CommonSetup: grk/bgw/{discardCardIds:SOR_046,SOR_128,SOR_078,SOR_120; theirDiscardCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: LAW_163:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1
P1DISCARDCOUNT:4
P2DISCARDCOUNT:1

---

# OfferPool_DamageHitsEnemyGroundUnitsOnly
#// LAW_163 The Sarlacc of Carkoon — offer assertion for the SECOND pool, "deal damage equal to that
#// unit's power to an ENEMY GROUND unit". Both a controller and an arena restriction, so the board
#// carries a violator for each: a friendly GROUND unit (SOR_095) and a friendly SPACE unit (SOR_178)
#// must be OUT, an enemy SPACE unit (SEC_213) must be OUT, and the two enemy GROUND units must be IN
#// (the Sarlacc itself is friendly, so it is out too). The discard pick is answered so the second
#// decision is reached; the damage pick is then left UNANSWERED so its pool can be read.
#// COVERAGE: offer=OfferPool_OwnDiscardUnitsOnly (discard pool: non-units and the opponent's pile out) +
#//           OfferPool_DamageHitsEnemyGroundUnitsOnly (damage pool: friendly and space out) ·
#//           reqboundary=NOT COVERED (the moved unit's power is read into the continuation string before
#//           the damage pick, which is the state that would have to survive; no section forces a
#//           SimulateRequestBoundary across it) · control=N/A (one-shot damage; the discard move is
#//           owner-scoped) · boundary pair=OnAttackDiscardUnitDealPower (power 3 lands) vs
#//           ZeroPowerUnitDealsZero (power 0 → no damage) and NoUnitsInDiscard (empty pool → no-op) ·
#//           decline=NOT COVERED (the discard pick is an MZMAYCHOOSE and can be declined; no decline
#//           section exists yet — NoEnemyGroundUnit_StillMoves is an empty-second-pool case, not a
#//           decline)

## GIVEN
CommonSetup: grk/bgw/{discardCardIds:SOR_046}
P1OnlyActions: true
WithP1GroundArena: LAW_163:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SEC_213:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1SPACEARENAUNIT:0:CARDID:SOR_178
P2SPACEARENAUNIT:0:CARDID:SEC_213
