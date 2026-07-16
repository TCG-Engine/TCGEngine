# DealOneToTwoSpace
#// LAW_183 B-Wing Skirmisher (Aggression,Heroism, cost 4, space) — When Played: deal 1 damage to each of
#// up to 2 space units. Hit both enemy SOR_237s (2/3 each) for 1.

## GIVEN
CommonSetup: rrw/bgw/{myResources:4}
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_183

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0&theirSpaceArena-1

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:1:DAMAGE:1
