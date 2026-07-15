# TS26_022 The Darksaber — with fewer than 4 different keywords among friendlies (only Sentinel from the
# Darksaber), the host is NOT readied (stays exhausted), but still gains Sentinel.
## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_022}
WithP1GroundArena: SEC_080:0:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
