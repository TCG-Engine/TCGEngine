# BounceEnemyGroundUnit
#// SOR_222 Waylay — bounce an enemy ground unit back to its owner's hand
#// COVERAGE: offer=ChoosePool_EveryNonLeaderUnit_LeaderUnitExcluded (4 units on the board, exactly 3
#//           legal targets — both controllers, both arenas, deployed leader unit excluded; decision
#//           left pending) · control=ControlledEnemyOwnedUnit_ReturnsToItsOwnersHand ("its OWNER's
#//           hand" resolves to the owner, not the controller) · decline=N/A (no "you may" anywhere in
#//           the text and no optional cost — once the event is played the return is mandatory) ·
#//           boundary pair=N/A as a quantity (the text has no number or threshold — one unqualified
#//           target); the value-CLASS pair that stands in for it is BounceTokenUnits (token units
#//           cease instead of going to hand) vs BounceEnemyGroundUnit (a real card goes to hand),
#//           plus StripsUpgrade for the CR 9.3 upgrade half · reqboundary=BounceEnemyGroundUnit and
#//           the other resolution sections (the play and the target answer are separate serialized
#//           steps in production; the target choice is what ends the request).

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# BounceOwnUnit
#// SOR_222 Waylay — can also bounce your own unit back to hand

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# BounceSpaceUnit
#// SOR_222 Waylay — can target space arena units

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirSpaceUnit:0

## EXPECT
P2SPACEARENACOUNT:0
P2HANDCOUNT:1

---

# BounceTokenUnits
#// SOR_222 Waylay — bouncing token units sets them aside (not returned to hand or discard)
#// P2 has 3 ground tokens and 2 space tokens. P1 bounces all 5.
#// Expected: all tokens set aside, P2 hand/discard empty, P1 discard has 5 Waylays.

## GIVEN
CommonSetup: ybk/grw/{myResources:15;handCardIds:SOR_222,SOR_222,SOR_222,SOR_222,SOR_222}
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T02:1:0
WithP2GroundArena: SEC_T01:1:0
WithP2SpaceArena: JTL_T01:1:0
WithP2SpaceArena: JTL_T02:1:0
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>PlayHand:0
- P1>ChooseTheirSpaceUnit:0
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:5

---

# StripsUpgrade
#// SOR_222 Waylay — upgrades on a bounced unit are defeated (CR 9.3)
#// Non-token upgrade (LOF_215) goes to the upgrade owner's discard

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2DISCARDCOUNT:1

---

# ChoosePool_EveryNonLeaderUnit_LeaderUnitExcluded
#// SOR_222 Waylay — the OFFER axis. "Return a NON-LEADER unit to its owner's hand" names no
#// controller and no arena, so the pool is every non-leader unit on BOTH sides in BOTH arenas, and
#// the one printed exclusion is a deployed LEADER unit. Board: P1's deployed Iden Versio (leader
#// unit — must NOT be offered), P1's Battlefield Marine, P2's Wampa (ground) and P2's Alliance
#// X-Wing (space). Four units on the board, exactly three legal targets. The decision is left
#// PENDING — the offer itself is the assertion, so nothing has bounced yet.

## GIVEN
CommonSetup: ybk/grw/{myLeader:SOR_002:1:1;myResources:3;handCardIds:SOR_222}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0

---

# ControlledEnemyOwnedUnit_ReturnsToItsOwnersHand
#// SOR_222 Waylay — "Return a non-leader unit to ITS OWNER'S hand": the destination follows the
#// unit's OWNER, never its current controller. P1 controls a Battlefield Marine that P2 OWNS (the
#// end state after a take-control effect). P1 Waylays it from its own arena; the Marine must land in
#// P2's hand, and P1's hand must stay empty (P1 played its only card). Without the owner lookup the
#// card would come back to P1 — the controller's hand — which is the failure this section pins.

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_095:2

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:1
P2HANDCARD:0:SOR_095
P1DISCARDCOUNT:1
