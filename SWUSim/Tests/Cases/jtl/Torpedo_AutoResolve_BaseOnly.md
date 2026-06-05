# JTL_234 Torpedo Barrage — P1 chooses "You" with no friendly units in play. The only eligible
# target is P1's own base, so the assignment auto-resolves (single target → no MZSPLITASSIGN
# popup) and all 5 land on the base. Confirms the auto-resolve branch leaves no dangling decision.

## GIVEN
CommonSetup: ryk/rrk/{myResources:3;handCardIds:JTL_234}
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You

## EXPECT
P1BASEDMG:5
P1NODECISION
