# Support (ASH) — Restore lending. ASH_095 Remnant Interceptor (Space, 2/2, Restore 1 + Support) is
# played. The Marine gains Restore 1 (lent) and attacks P2's base for 3; Restore heals 1 from P1's own
# base (3 damage → 2).

## GIVEN
CommonSetup: yrw/grw/{myResources:9;myBaseDamage:3;handCardIds:ASH_095}
WithP1GroundArena: SOR_095:1:0   # Battlefield Marine (3/3, ready)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
P1BASEDMG:2
