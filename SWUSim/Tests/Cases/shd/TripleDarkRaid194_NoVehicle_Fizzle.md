# SHD_194 Triple Dark Raid — no Vehicle in the top 7 → clean fizzle (nothing played, no decision left).
# The search still peeks (private) and returns the cards to the bottom, but with no match the player picks
# nothing and the space arena stays empty.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_194
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046 LAW_180 SOR_063 SOR_207]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P1NODECISION
