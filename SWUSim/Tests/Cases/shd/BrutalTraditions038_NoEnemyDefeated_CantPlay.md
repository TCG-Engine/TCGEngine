# SHD_038 Brutal Traditions — the play-from-discard is gated on "an enemy unit was defeated this phase."
# With no enemy defeated, the SWU_ENEMY_DEFEATED flag is unset, so the discard copy is NOT playable:
# PlayFromDiscard is a full no-op (upgrade stays in discard, no host gets it, no resources spent).

## GIVEN
CommonSetup: brk/rrk/{myResources:4;discardCardIds:SHD_038}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayFromDiscard:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:4
P1NODECISION
