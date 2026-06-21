# LAW_010 Leia Organa (leader front) — "Action [2 resources, Exhaust]: For this phase, give a unit
# +1/+1 for each different aspect it has." SEC_080 (Command/Villainy = 2 aspects) gets +2/+2 → 5/5.

## GIVEN
P1LeaderBase: LAW_010/SOR_028
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1RESAVAILABLE:0
