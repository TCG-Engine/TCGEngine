# ReturnSameName
#// TWI_199 Clear the Field (Event, Cunning/Heroism) — "Choose a non-leader unit that costs 3 or less. Return
#// it and each enemy non-leader unit with the same name to their owners' hands." Both enemy SOR_095 bounce.
## GIVEN
CommonSetup: yyw/bbw/{myResources:2;handCardIds:TWI_199}
P1OnlyActions: true
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:2

---

# TwinSuns_EnemyMeansEVERYOpponentNotJustTheChosenOnesController
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1). TWI_199 is its own category in the sweep plan
#// ("choose a unit", 1 card). The choice itself is unqualified and already correct — the offer fans out —
#// but the SECOND clause was scoped to one seat.
#//
#// RULING APPLIED: "each ENEMY non-leader unit with the same name" means enemy of the ABILITY'S
#// CONTROLLER (the caster). That is the CR meaning of "enemy" and it does NOT shift with the chosen
#// unit's owner. Consequences, both pinned here:
#//   • picking an opponent's unit still returns EVERY OTHER opponent's same-name units, and
#//   • the caster's OWN same-name unit is never returned (it is not "enemy").
#// ⚠ The two readings are byte-identical at two seats — the TWI_204 situation exactly, where the same
#//   sentence describes a different shape once there are more than two players. The wrong reading would
#//   have returned the CASTER'S own units when they picked an enemy's.
#//
#// The old code flipped $playerID to a single OtherPlayer() and searched my* under that frame, reaching
#// exactly ONE opponent. Now it stays in the caster's frame and searches their*, which ZoneSearch already
#// fans out across every live opponent.
#//
#// P1 plays it and picks SEAT 2's Battlefield Marine. Seats 2, 3 AND 4 each control one, so all three
#// bounce; P1's OWN Battlefield Marine must STAY (it is not an enemy unit).
#// Under the old code only seat 2's returned — seats 3 and 4 were never even looked at.
#// ⚠ A 2-player version CANNOT FAIL — with one opponent "their*" and that opponent's "my*" are the same
#//   arena. The seat count IS the test.
#// Mutation check: revert to the $playerID flip + my* search and this reds while ReturnSameName stays green.

## GIVEN
CommonSetup: yyw/bbw/{myResources:2;handCardIds:TWI_199}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-0

## EXPECT
SEATCOUNT:4
P2GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:0
P4GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
