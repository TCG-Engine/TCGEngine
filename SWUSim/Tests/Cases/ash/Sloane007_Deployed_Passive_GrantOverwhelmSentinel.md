# ASH_007 Grand Admiral Sloane (deployed) — passive: each other friendly unit gains Overwhelm and
# Sentinel. The Dark Trooper (SEC_080) gains both.

## GIVEN
CommonSetup: ggk/brk/{
  myLeader:ASH_007:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
