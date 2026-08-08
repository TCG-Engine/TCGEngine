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

---

# LeavesPlay_ControlRevertsOnBounce
#// SEC_192 Grand Moff Tarkin — the "when this unit leaves play" revert fires no matter HOW he leaves, not
#//   only on defeat. P1 plays Tarkin and takes control of P2's SOR_237 (into P1's space arena). P2 then
#//   returns Tarkin to P1's hand with Waylay (SOR_222). With Tarkin gone, the revert sweep hands SOR_237
#//   back to its owner P2, and Tarkin sits in P1's hand rather than the discard.

## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SEC_192
WithP2Hand: SOR_222
WithP2Resources: 3
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237

---

# CannotTargetAVehicleThatIsALeaderUnit
#// SEC_192 Grand Moff Tarkin — "take control of an enemy NON-LEADER Vehicle unit". P2's JTL_001 Asajj
#// Ventress is deployed as a PILOT onto their Vehicle, which makes that Vehicle a LEADER UNIT. It is the
#// only enemy Vehicle on the board, so Tarkin's When Played finds no legal target: nothing is stolen,
#// P1's space arena stays empty and the Vehicle remains P2's.

## GIVEN
CommonSetup: yyk/bbk/{theirLeader:JTL_001;theirLeaderDeployedPilot:true}
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SEC_192
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P1GROUNDARENAUNIT:0:CARDID:SEC_192
P1NODECISION

---

# StolenUnitTakenBackByTheOwnerFirst_TarkinsRevertIsANoOp
#// SEC_192 Grand Moff Tarkin — the "when this unit leaves play, that unit's owner takes control of it"
#// link must not misfire when something ELSE has already returned the unit to its owner.
#// P1's Tarkin steals P2's SOR_232 AT-ST. P2 then plays SOR_224 Change of Heart to take the AT-ST back,
#// so P2 (its owner) controls it again while the Tarkin link is still armed. P2 then bounces Tarkin with
#// SOR_222 Waylay: the link fires, finds the AT-ST already under its owner, and correctly does nothing.
#// End state: P1's arena is empty, Tarkin sits in P1's HAND (bounced, not defeated), and the AT-ST is
#// still P2's — no double-transfer, no dangling steal flag.

## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 1
WithP1Resources: 8
WithP2Resources: 14
WithP1Hand: SEC_192
WithP2Hand: SOR_224
WithP2Hand: SOR_222
WithP2GroundArena: SOR_232:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1HANDCOUNT:1
