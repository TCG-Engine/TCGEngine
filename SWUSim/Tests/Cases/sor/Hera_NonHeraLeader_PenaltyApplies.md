# SOR_008 Hera — control: with a non-Hera leader that has the SAME aspects (SOR_009, Command/Heroism),
# Zeb's off-aspect Aggression pip still adds +2 → cost 7. With only 5 resources the play is a silent
# no-op (Zeb stays in hand), proving the waiver is Hera-specific, not just the shared Heroism aspect.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_146
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
