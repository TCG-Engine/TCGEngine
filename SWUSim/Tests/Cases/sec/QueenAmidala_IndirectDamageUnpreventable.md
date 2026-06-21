# SEC_101 Queen Amidala — INDIRECT damage is unpreventable (it writes Damage directly, never through
#   SWUDealDamageToUnit / combat), so her prevention does NOT trigger. P1 plays JTL_116 (indirect damage
#   to a player = its Vehicles) at P2; P2 simply gets the indirect MZSPLITASSIGN (NO prevention offer) and
#   assigns it onto Amidala. She takes the damage and P2's Official SEC_118 is NOT sacrificed (count stays
#   2). Confirms indirect ignores Amidala's effect.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5;handCardIds:JTL_116}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_118:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:2

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:2
