# SHD_009 Hunter (deployed On Attack) — "You may reveal a resource you control. If it shares a name with
# a friendly unique unit, return it to hand and put the top card of your deck into play as a resource."
# Deployed (7 resources, incl. a SOR_179 resource), Hunter attacks the base; his On Attack reveals the
# SOR_179 resource (matches the unique SOR_179 unit) → returned to hand + top card (SOR_095) ramped.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Resources: 6:SOR_046:1,1:SOR_179:1
WithP1Deck: SOR_095

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myResources-6

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
