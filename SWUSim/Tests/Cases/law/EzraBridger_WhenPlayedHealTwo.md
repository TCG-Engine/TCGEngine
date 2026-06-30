# LAW_035 Ezra Bridger (4/5, Raid 1) — When Played: heal 2 from a unit (4 if you control an Aggression
# or Cunning unit). Here P1 controls neither -> heal 2 from the damaged SOR_046 (4 -> 2).

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
WithP1GroundArena: SOR_046:1:4
WithP1Hand: LAW_035

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:2
