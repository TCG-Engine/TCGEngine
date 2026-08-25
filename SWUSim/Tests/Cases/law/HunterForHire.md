# NoCredit_Unaffordable_NoOp
#// LAW_156 Hunter For Hire — the cost is "defeat a friendly Credit token." With NO Credit token, the
#// opponent can't pay, so the action is a full no-op: control does not change. P2 has no Credit, so its
#// attempt to use the action on the enemy Hunter For Hire does nothing (P1 keeps control).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: LAW_156:1:0

## WHEN
- P2>UseUnitAbility:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_156
P2GROUNDARENACOUNT:0

---

# OpponentTakesControl
#// LAW_156 Hunter For Hire (4/4) — "Action [defeat a friendly Credit token]: Take control of this unit.
#// Any player may use this ability." P1 controls Hunter For Hire; on P2's turn, P2 (the opponent) uses the
#// action — defeating one of P2's OWN Credit tokens — to take control of it. The unit moves to P2's arena.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: LAW_156:1:0
WithP2Credits: 1

## WHEN
- P2>UseUnitAbility:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_156
P2CREDITCOUNT:0

---

# ControllingPlayerMayUseAbility
#// LAW_156 Hunter For Hire (4/4) — "Any player may use this ability", including the CONTROLLING player. P1
#// controls Hunter For Hire and has 1 Credit; P1 uses the take-control action on its own unit. Taking
#// control of an already-controlled unit is a no-op for the board, but the Credit cost is still paid.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1GroundArena: LAW_156:1:0
WithP1Credits: 1

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_156
P1CREDITCOUNT:0

---

# TakeControlOfEnemyHunterForHire
#// LAW_156 Hunter For Hire (4/4) — the take-control action works in either direction: P1 controls a Credit
#// and takes control of a P2-controlled Hunter For Hire by defeating that Credit. The unit moves to P1's
#// arena and P1's Credit is defeated. (Mirrors an opponent later taking it back.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP2GroundArena: LAW_156:1:0
WithP1Credits: 1

## WHEN
- P1>UseUnitAbility:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_156
P1CREDITCOUNT:0

---

# TwoPlayer_TheOfferListActuallyContainsIt
#// ⚠ THE OFFER-PATH CELL — added 2026-08-24 with the new P#UNITACTIONS assertion.
#// Every other section on this card drives `UseUnitAbility` DIRECTLY, which bypasses the offer list
#// entirely — so a card could be perfectly gated and still be invisible and unclickable in a real game
#// with every test green. This section asserts the list the CLIENT actually uses
#// ($data['unitActions'] from SWUComputeActionsData).
#// "Any player may use this ability" ⇒ it must be surfaced on a board the actor does NOT control, which
#// is what $anyPlayerUnitActions["LAW_156"] does. Without that registration this section reds while every
#// other section on the card stays green — which is exactly the blind spot being closed.
#// ⚠ SWUComputeActionsData only computes while the seat is ACTIVE (MAIN + their turn + both queues empty),
#//   so this section leaves no decision pending.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: LAW_156:1:0
WithP2Credits: 1

## WHEN

## EXPECT
P2UNITACTIONSHAS:theirGroundArena-0

---

# TwinSuns_OfferedOnEVERYSeatsBoardNotJustOne
#// ⚠ THE SEAT-COUNT CELL for the offer path — added 2026-08-24. This is the pin for Pass-0 seam 7.
#// `SWUComputeActionsData` surfaced any-player unit actions with `$oppAP = OtherPlayer($player)` — ONE
#// seat. Above two seats that meant the action was offered on a single opponent's board and was simply
#// ABSENT from the others: seats 2/3/4 all looked at seat 1's board, and seat 1 looked only at seat 2's.
#// For a "take control of this unit" card that is a silent, unreachable ability, with no prompt for a
#// player to notice missing.
#// SEAT 3 and SEAT 4 each control a Hunter For Hire. P2 is active and holds a Credit, so BOTH must appear
#// in P2's offer list — as p3GroundArena-0 and p4GroundArena-0 (the seat-qualified form emitted above two
#// seats; the 2-player "their…" form is kept byte-identical and is pinned by the section above).
#// ⚠ A 2-player version CANNOT FAIL — with one opponent OtherPlayer() already names the only board.
#// Mutation check: revert the OpponentsOf() loop to OtherPlayer() and one of the two reds.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP3GroundArena: LAW_156:1:0
WithP4GroundArena: LAW_156:1:0
WithP2Credits: 1
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN

## EXPECT
SEATCOUNT:4
P2UNITACTIONSHAS:p3GroundArena-0&p4GroundArena-0
