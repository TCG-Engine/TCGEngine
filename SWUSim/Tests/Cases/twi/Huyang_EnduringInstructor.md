# WhenPlayed_BuffsChosenUnit
#// TWI_110 Huyang (Unit 2/4, Ground, cost 3) — "When Played: Choose another friendly unit. While this
#// unit is in play, the chosen unit gets +2/+2." Playing Huyang with a friendly SOR_095 (3/3) → choose
#// it → 5/5.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:TWI_110}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# Control_OpponentTakesHuyang_TheBuffStays
#// OFFICIAL RULING (10/31/2024): "Until Huyang leaves play, the friendly unit gets +2/+2. This effect is not changed if
#// an opponent takes control of Huyang." P2's SOR_224 Change of Heart (Cunning, 6) takes Huyang; the Marine stays 5/5.
#// The old reader looked for Huyang only on the TARGET's controller's board, so this dropped the buff.

## GIVEN
CommonSetup: ggw/yyk/{myResources:3;handCardIds:TWI_110;theirResources:6;theirhandCardIds:SOR_224}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:TWI_110
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# Control_TheChosenUnitChangesSides_TheBuffStays
#// "the CHOSEN unit gets +2/+2" follows the unit: P2's Change of Heart takes the buffed Marine, which is still 5/5 while
#// Huyang is in play. The old reader counted the link only in the target's NEW controller's effects, so it was lost.

## GIVEN
CommonSetup: ggw/yyk/{myResources:3;handCardIds:TWI_110;theirResources:6;theirhandCardIds:SOR_224}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:HP:5
