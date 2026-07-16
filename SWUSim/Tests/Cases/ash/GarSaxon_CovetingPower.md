# UpgradePlayed_CreatesToken
#// ASH_047 Gar Saxon (Ground, 3/4) — "When you play an upgrade on this unit: you may create a Mandalorian
#// token (once each round)." P1 plays Academy Training (SOR_120) which auto-attaches to the lone friendly
#// unit (ASH_047); the reaction offers a YESNO → YES → a Mandalorian token is created.

## GIVEN
CommonSetup: brk/rrk/{myResources:6;handCardIds:SOR_120}
WithP1GroundArena: ASH_047:1:0
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_047
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
