# LosesAbilitiesGainsGrit
#// SEC_054 Exiled from the Force (upgrade) — Attached unit loses the Force trait and all abilities except
#//   for Grit; attached unit gains Grit. Host SOR_049 (Force/Jedi, innate Sentinel) loses Sentinel and
#//   gains Grit. (Force-trait loss is wired via _SWUUnitHasTrait, mirroring the NO_TRAIT path.)

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SOR_049:1:0
WithP1GroundArenaUpgrade: 0:SEC_054

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
