# WhenPlayedTokenThenDealPower
#// LAW_039 Latts Razzi (2/1) — When Played: give a Shield or Experience token to this unit, then she
#// deals damage equal to her power to an enemy ground unit. Choose Experience (2/1 -> 3/2), deal 3 to
#// the enemy SOR_046 (3/7).

## GIVEN
CommonSetup: bgw/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_039

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Experience

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_039
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayedShieldThenDealTwo
#// LAW_039 Latts Razzi (2/1) — When Played: choose the Shield token (power stays 2), then she deals 2
#// (her power) to an enemy ground unit. Shield does NOT raise power, so the damage is 2, not 3.

## GIVEN
CommonSetup: bgw/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_039

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_039
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenPlayedNoEnemyGroundUnitNoDamage
#// LAW_039 — she still gains the chosen token, but with no enemy ground unit there is nothing to damage;
#// the enemy space unit is untouched and Latts ends exhausted in the ground arena.

## GIVEN
CommonSetup: bgw/bgw/{myResources:3}
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_039

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Experience

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_039
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:DAMAGE:0
