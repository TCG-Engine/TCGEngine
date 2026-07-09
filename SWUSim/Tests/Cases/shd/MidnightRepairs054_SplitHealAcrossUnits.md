# SHD_054 Midnight Repairs (2-cost event, Vigilance/Vigilance) — "Heal up to 8 total damage from any
# number of units." MZSPLITASSIGN up-to mode: heal 5 to SOR_046 (7 HP, 5 damage) and 2 to SEC_080 (3 damage
# capped at its 2) — 7 total (≤ 8) → both end at 0 damage. Event lands in discard.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_054
WithP1GroundArena: SOR_046:1:5
WithP1GroundArena: SEC_080:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:5,myGroundArena-1:2

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:DAMAGE:0
P1DISCARDCOUNT:1
