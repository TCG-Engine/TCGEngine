# WhenPlayedHealTwo
#// LAW_035 Ezra Bridger (4/5, Raid 1) — When Played: heal 2 from a unit (4 if you control an Aggression
#// or Cunning unit). Here P1 controls neither -> heal 2 from the damaged SOR_046 (4 -> 2).

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

---

# WhenPlayedHealFourWithAggression
#// LAW_035 Ezra Bridger — heals 4 (instead of 2) while controlling an Aggression unit. P1 controls SOR_128
#// (Aggression) alongside the damaged SOR_046 (5 damage) -> heal 4 -> 1 damage remains.

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
WithP1GroundArena: SOR_046:1:5
WithP1GroundArena: SOR_128:1:0
WithP1Hand: LAW_035

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# WhenPlayedHealFourWithCunning
#// LAW_035 Ezra Bridger — heals 4 while controlling a Cunning unit. P1 controls SOR_213 (Cunning) alongside
#// the damaged SOR_046 (5 damage) -> heal 4 -> 1 damage remains.

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
WithP1GroundArena: SOR_046:1:5
WithP1GroundArena: SOR_213:1:0
WithP1Hand: LAW_035

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:1
