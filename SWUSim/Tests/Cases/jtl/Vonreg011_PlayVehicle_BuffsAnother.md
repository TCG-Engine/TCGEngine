# JTL_011 Major Vonreg (leader) — Action [Exhaust]: Play a Vehicle unit from your hand (paying its
# cost). If you do, give another unit +1/+0 for this phase. P1 plays SOR_225 (TIE/ln, Villainy Vehicle,
# cost 1) and then buffs the OTHER unit SEC_080 (3/3 → 4/3); the just-played TIE is excluded.

## GIVEN
P1LeaderBase: JTL_011/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_225
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED
