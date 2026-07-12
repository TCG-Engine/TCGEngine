# TWI_238 Merciless Contest (Event, cost 3, Villainy, Tactic) — "Each player chooses a non-leader unit
# they control. Defeat those units." Each player has one unit (SOR_095), so both auto-resolve and both are
# defeated.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_238}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
