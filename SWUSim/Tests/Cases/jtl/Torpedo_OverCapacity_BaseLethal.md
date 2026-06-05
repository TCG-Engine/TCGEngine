# JTL_234 Torpedo Barrage — P1 targets the Opponent, who controls NO units. The only valid
# target is their base, so the assignment AUTO-RESOLVES (no popup). The base is an UNLIMITED
# sink for indirect damage (unlike units, it is NOT capped at remaining HP), so all 5 land and
# go over capacity. Base HP 30, pre-damaged to 27 → 27 + 5 = 32 damage, base defeated, P1 wins.

## GIVEN
CommonSetup: ryk/rrk/{myResources:3;handCardIds:JTL_234;theirBaseDamage:27}
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:32
P1WIN
