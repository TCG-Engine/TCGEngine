# SHD_009 Hunter (front Action [1 resource, Exhaust]) — "Reveal a resource you control. If it shares a
# name with a friendly unique unit, return the resource to its owner's hand and put the top card of your
# deck into play as a resource." P1 controls the unique SOR_179 (Boba Fett) and a SOR_179 resource;
# revealing it returns the resource to hand and ramps the top card (SOR_095) into a new resource. Net
# resource count unchanged (2 → SOR_179 returned → SOR_095 ramped = 2); SOR_179 now in hand; deck empty.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Resources: 1:SOR_046:1,1:SOR_179:1
WithP1Deck: SOR_095

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-1

## EXPECT
P1HANDCOUNT:1
P1RESCOUNT:2
P1DECKCOUNT:0
P1GROUNDARENACOUNT:1
