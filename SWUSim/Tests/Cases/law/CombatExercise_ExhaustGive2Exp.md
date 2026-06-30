# LAW_165 Combat Exercise (Command event, cost 1) — "Exhaust a friendly unit. If you do, give 2
# Experience tokens to it." Single ready friendly (SOR_095 3/3) -> auto-target -> exhausted, +2/+2.

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_165

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
