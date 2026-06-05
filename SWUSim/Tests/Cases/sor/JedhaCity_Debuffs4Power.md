# SOR_028 Jedha City (Base) — "Epic Action: Give a non-leader unit -4/-0 for this
# phase." P1's base is Jedha City; P2's only non-leader unit is Consular Security
# Force (SOR_046, 3/7). It's the sole target → auto −4/−0: power 3 → floored at 0,
# HP unchanged at 7.

## GIVEN
P1LeaderBase: SOR_014/SOR_028
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:7
P1BASE:EPICUSED
