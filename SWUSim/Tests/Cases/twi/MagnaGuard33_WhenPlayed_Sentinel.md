# TWI_033 Calculating MagnaGuard (Unit, Ground, Vigilance/Villainy) — "When Played: This unit gains
# Sentinel for this phase."
## GIVEN
CommonSetup: bbk/rrk/{myResources:3;handCardIds:TWI_033}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_033
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
