# SOR_008 Hera Syndulla (leader) — "Ignore the aspect penalty on SPECTRE cards you play." P1's leader is
# Hera (Command/Heroism). SOR_146 Zeb (Spectre, Aggression/Heroism, cost 5) would normally cost 7 (the
# Aggression pip is off-aspect, +2), but Hera waives it — so with exactly 5 resources Zeb enters play.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_146
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
