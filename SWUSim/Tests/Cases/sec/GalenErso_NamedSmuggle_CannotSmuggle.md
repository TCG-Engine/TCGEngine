# SEC_046 Galen Erso — naming a Smuggle card denies the keyword, so the opponent can't play it from
# resources via Smuggle. P1 names "Vigilant Pursuit Craft" (SHD_065, Smuggle). P2 tries to Smuggle it
# from resources, but the play is blocked — the card stays put and never enters the space arena.

## GIVEN
CommonSetup: bbw/bbk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Resources: 1:SHD_065:1,8:SOR_095:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Vigilant Pursuit Craft
- P2>SmuggleResource:0

## EXPECT
P2SPACEARENACOUNT:0
