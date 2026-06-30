# LAW_094 Hondo Ohnaka (3/7) — Action: play the top card of your deck (paying its cost). Once each
# round. Top is SOR_063 (Vigilance, cost 2); pay 2 -> it enters play.

## GIVEN
CommonSetup: byk/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: LAW_094:1:0
WithP1Deck: SOR_063

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1DECKCOUNT:0
