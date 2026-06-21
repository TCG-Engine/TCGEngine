# SEC_104 — the aura is conditional on the attached unit being READY. With the host SEC_041 exhausted,
#   the other friendly SEC_042 does NOT gain Overwhelm.

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_041:0:0
WithP1GroundArenaUpgrade: 0:SEC_104
WithP1GroundArena: SEC_042:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:NOTKEYWORD:Overwhelm
