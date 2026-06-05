# SOR_145 K-2SO (4/4, Overwhelm) — "When Defeated: For each opponent, choose one: either deal 3 damage
# to that player's base, or that player discards a card from their hand." K-2SO attacks a 4/7 wall and
# dies to the 4 counter-damage; its controller (P1) chooses Base → 3 damage to P2's base.

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Base

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:4
