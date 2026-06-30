# Support (ASH) — declining the optional bonus attack. ASH_130 is played; player answers NO. No attack
# happens: the Marine stays ready, P2's base is undamaged. ASH_130 still entered the space arena.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_130}
WithP1GroundArena: SOR_095:1:0   # Battlefield Marine (3/3, ready)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:READY
