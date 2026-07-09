# SHD_197 L3-37 — with NO captured cards in play the rescue is impossible: the "If you don't"
# branch auto-resolves (no decision) and she shields herself.

## GIVEN
CommonSetup: gyw/gyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_197

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1NODECISION
