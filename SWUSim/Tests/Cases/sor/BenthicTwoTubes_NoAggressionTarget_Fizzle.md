# SOR_156 Benthic "Two Tubes" — "Another friendly [Aggression] unit". With only a non-Aggression
# friendly unit (SOR_095, Heroism) present, Benthic's On Attack has no eligible recipient and fizzles:
# no decision is offered and the bystander gains no Raid. (Self is excluded — Benthic can't pick itself.)

## GIVEN
CommonSetup: rrw/rrk/{}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_156:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P2BASEDMG:2
