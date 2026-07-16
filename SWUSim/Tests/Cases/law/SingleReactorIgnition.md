# DefeatAllDamageBase
#// LAW_044 Single Reactor Ignition (Vigilance,Aggression,Villainy event, cost 8) — "Defeat all units.
#// For each enemy unit defeated this way, deal 1 damage to its controller's base." P1 has 1 own unit,
#// P2 has 2 -> all 3 defeated; 2 enemy units => 2 damage to P2's base.

## GIVEN
CommonSetup: rrk/bgw/{myResources:10}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_044

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P2BASEDMG:2
P1DISCARDCOUNT:2
P2DISCARDCOUNT:2
