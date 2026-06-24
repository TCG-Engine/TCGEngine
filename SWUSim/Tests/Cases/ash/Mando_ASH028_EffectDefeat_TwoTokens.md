# ASH_028 Paz Vizsla — When Defeated NOT by combat damage → create 2 Mandalorian tokens. P1 plays
# Vanquish (SOR_078, "defeat a non-leader unit") on its OWN ASH_028, so the defeat is an effect (not
# combat) and resolves inline on P1's action → 2 Mandalorian tokens replace ASH_028.

## GIVEN
CommonSetup: brk/rrk/{myResources:5;handCardIds:SOR_078}
WithP1GroundArena: ASH_028:1:0
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
