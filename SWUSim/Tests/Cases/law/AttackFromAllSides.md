# DealThree
#// LAW_207 Attack From All Sides (Aggression event, cost 3) — "Deal 3 damage to a unit. If there are 4
#// or more different aspects among friendly units, you may deal 5 instead." With <4 aspects among
#// friendly units (P1 controls none), the deal is just 3.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_207

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# FourAspectsDealFive
#// LAW_207 Attack From All Sides — with 4+ different aspects among friendly units (SOR_046 Vigilance/
#// Heroism + SEC_080 Command/Villainy = 4 distinct), opt to deal 5 instead. Target the enemy SOR_046.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
