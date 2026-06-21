# IBH_060 Admiral Piett (Ground, 2/5, Vigilance/Villainy) — On Attack: if you control an Aggression unit,
#   draw a card. P1 controls SOR_128 (Aggression/Villainy). Piett attacks the base → draws 1.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: IBH_060:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:theirBase-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P2BASEDMG:2
P1NODECISION
