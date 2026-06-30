# LAW_144 Phantom (Command,Heroism, cost 2) — When Played: you may play a Heroism unit from your hand
# (paying its cost) and give an Experience token to it. Play SOR_095 (Command,Heroism, 3/3) -> enters
# with 1 Experience (4/4).

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP1Hand: SOR_095
WithP1Hand: LAW_144

## WHEN
- P1>PlayHand:1
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
