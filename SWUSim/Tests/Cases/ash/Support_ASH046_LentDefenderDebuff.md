# Support lending of a CONTINUOUS ability (ASH_046 Scion Shuttle's "-1/-1 to the defender"). ASH_046 is
# played; the player supports with SOR_237 (the other ready space unit), which attacks SOR_225 (2/1). The
# lent -1/-1 reduces SOR_225's counter from 2 to 1, so SOR_237 takes only 1 (proves the SUPPORT_GRANT
# graft of the passive). SOR_225 is defeated by SOR_237's 2 damage.
## GIVEN
CommonSetup: bbk/bbk/{myResources:4;handCardIds:ASH_046}
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:1
