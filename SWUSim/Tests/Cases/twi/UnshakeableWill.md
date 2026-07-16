# GrantsSentinel
#// TWI_071 Unshakeable Will (Upgrade +2/+2, cost 4, Vigilance) — "Attached unit gains Sentinel." Played on
#// SOR_095 (3/3 → 5/5), it grants Sentinel.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:TWI_071}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
