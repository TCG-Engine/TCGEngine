# Support (ASH) — keyword lending (single eligible attacker, auto-selected). ASH_154 Honorable Nite Owl
# (Ground, 2/2, Raid 1 + Support) is played. The lone ready Marine gains Raid 1 (lent from ASH_154) and
# attacks the base for 3 + 1 = 4.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_154}
WithP1GroundArena: SOR_095:1:0   # Battlefield Marine (3/3, ready)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:EXHAUSTED
