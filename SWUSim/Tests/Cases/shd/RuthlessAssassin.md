# WhenPlayed_Deal2Friendly
#// SHD_235 Ruthless Assassin (2-cost 3/3 ground) — Overwhelm + "When Played: Deal 2 damage to a friendly
#// unit." P1 directs the 2 damage onto the friendly SOR_046 (7 HP → 2 damage).

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_235
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:CARDID:SHD_235
