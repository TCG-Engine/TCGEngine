# LeavesPlay_ControlReverts
#// SEC_192 Grand Moff Tarkin — the stolen Vehicle's control REVERTS to its owner when Tarkin leaves play.
#// P1 plays Tarkin and takes control of P2's SOR_237 (now in P1's space arena). The turn passes to P2,
#// who attacks Tarkin (2/6) with an 8/8 (SOR_039) and defeats him. With Tarkin gone, the lazy revert sweep
#// (run in SWUAfterAction after P2's attack) returns SOR_237 to P2's space arena. SOR_237 was never in
#// combat, so it survives; SOR_039 takes only 2 and survives.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SEC_192
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2GROUNDARENACOUNT:1

---

# NoEnemyVehicle_NoSteal
#// SEC_192 Grand Moff Tarkin — fizzle guard: with no enemy non-leader Vehicle, the When Played takes
#// nothing. P2's only unit is SEC_080 (Imperial, NOT a Vehicle), so Tarkin just enters play and SEC_080
#// stays under P2's control.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SEC_192
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_192
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080

---

# WhenPlayed_TakeControlVehicle
#// SEC_192 Grand Moff Tarkin (Unit, 2/6, cost 6, Cunning/Villainy, Imperial/Official)
#//   "When Played: Take control of an enemy non-leader Vehicle unit. When this unit leaves play, that
#//    unit's owner takes control of that unit."
#// This test: the take-control on play. P1 plays Tarkin (yyk covers Cunning/Villainy → cost 6). P2's only
#// Vehicle is SOR_237 (space) — the sole legal target, so the choose auto-resolves. SOR_237 moves into
#// P1's space arena (controller P1, still owned by P2), and leaves P2's.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SEC_192
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_192
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENACOUNT:0
