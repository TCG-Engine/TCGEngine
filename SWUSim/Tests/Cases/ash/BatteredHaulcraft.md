# WhenPlayedSelfAndEnemySpace
#// ASH_071 Battered Haulcraft (Space, 2/3, cost 2) — When Played: deal 1 damage to this unit and 1 damage
#// to an enemy space unit. Played, it takes 1 self-damage and deals 1 to SOR_225 (2/1), defeating it.
## GIVEN
CommonSetup: bbk/bbk/{myResources:2;handCardIds:ASH_071}
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_071
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENACOUNT:0
