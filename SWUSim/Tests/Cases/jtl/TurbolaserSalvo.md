# DealPowerToEachEnemyInArena
#// JTL_131 Turbolaser Salvo — "Choose an arena. A friendly space unit deals damage equal to its power to
#// each enemy unit in that arena." P1 chooses the Ground arena; the only friendly space unit SOR_237
#// (power 2) deals 2 to each P2 ground unit: SEC_080 (3/3) survives at 2 damage, SOR_128 (3/1) dies. The
#// P2 space unit SOR_225 is in the OTHER arena and is untouched (proves the arena selection).

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_131}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0

---

# ChooseSpaceArena_HitsSpaceEnemies
#// JTL_131 Turbolaser Salvo — choosing the SPACE arena makes the friendly space unit deal to each enemy
#// SPACE unit. SOR_237 (power 2) hits both P2 space units: SOR_225 (2/1) dies, SOR_044 (2/3) survives at 2.
#// The P2 ground unit SEC_080 is in the other arena and untouched.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_131}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_044:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_044
P2SPACEARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# MultipleFriendlySpace_ChooseWhichDeals
#// JTL_131 Turbolaser Salvo — with more than one friendly space unit, P1 chooses WHICH one deals. Two
#// friendly space units (SOR_237 power 2, SOR_052 power 6); P1 picks SOR_052 and the Space arena, so each
#// enemy space unit takes 6: SOR_044 (2/3) is defeated.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_131}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_052:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P2SPACEARENACOUNT:0
