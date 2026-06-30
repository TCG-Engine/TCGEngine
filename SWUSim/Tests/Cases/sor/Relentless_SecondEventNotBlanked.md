# SOR_089 Relentless — only the FIRST event each round is blanked. P2 plays Confiscate (1st event,
# blanked → upgrade survives), P1 passes, then P2 plays a second Confiscate (NOT blanked) which defeats
# the upgrade. The end state (upgrade gone) plus Relentless_FirstEventBlanked together prove "first only."

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_089:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Hand: SOR_251
WithP2Hand: SOR_251
WithP2Resources: 2

## WHEN
- P2>PlayHand:0
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:2
