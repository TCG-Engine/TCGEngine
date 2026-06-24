# Support (ASH) — pure keyword. ASH_130 Fang Fighter Squadron (Space, 5/5, Support only) is played.
# The only ready friendly unit (Battlefield Marine, 3 power) is auto-selected and attacks P2's base
# for 3. ASH_130 itself enters the space arena exhausted; the Marine exhausts from attacking.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_130}
WithP1GroundArena: SOR_095:1:0   # Battlefield Marine (3/3, ready)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
