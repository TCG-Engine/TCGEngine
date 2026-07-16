# DealsFourToEachEnemyGround
#// LAW_179 Fear and Dead Men (Aggression,Villainy event, cost 7) — cost reduction (1 less per card
#// discarded from hand this phase) handled by the play-cost modifier; effect: "Deal 4 damage to each
#// enemy ground unit." SOR_046 (3/7) survives at DAMAGE:4; SOR_095 (3/3) dies.

## GIVEN
CommonSetup: rrk/bgw/{myResources:7}
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_179

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
