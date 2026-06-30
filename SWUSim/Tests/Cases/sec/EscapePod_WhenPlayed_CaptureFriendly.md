# SEC_056 Escape Pod (Space, 0/3, Vigilance, cost 1) — When Played: you may have THIS unit capture a
#   friendly non-Vehicle, non-leader unit. Captures the ground SOR_095 → it becomes a captive under
#   SEC_056 (capture is not arena-restricted, CR 8.34).

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_056

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SEC_056
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
