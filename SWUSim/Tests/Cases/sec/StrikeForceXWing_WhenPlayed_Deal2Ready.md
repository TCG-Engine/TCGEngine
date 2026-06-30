# SEC_152 Strike Force X-Wing (Unit, Aggression/Heroism, cost 4) — When Played: may deal 2 to a READY
#   unit. (Plot dormant from hand.) Hits the ready enemy SOR_046.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_152

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
