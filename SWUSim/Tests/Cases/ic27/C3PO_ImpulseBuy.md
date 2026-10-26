# Action_FivePowerUnitBecomesSix_Draws
#// IC27_070 C-3PO (Ground 1/3) — "Action [Exhaust]: Give an Experience token to another unit. Then, if it
#// has 6 or more power, draw a card." The only other unit is P1's B1 Attack Platform (5/2): the token
#// makes it 6/3, and its power is read AFTER the token, so P1 draws. C-3PO is exhausted by the cost.
#// COVERAGE: offer=Offer_AnyOtherUnitOnTheTable_HimselfExcluded (pending SELECTABLEEXACT: both sides, both
#//           arenas, a deployed leader unit; C-3PO excluded by "another") ·
#//           decline=N/A (no "you may": the pick is mandatory; the only decline is not activating) ·
#//           boundary=Action_FourPowerUnitBecomesFive_NoDraw vs this section (5→6 draws, 4→5 does not —
#//           also separates "power after the token" from "power before") ·
#//           control=ControlChange_TheController_UsesItAndDraws (a stolen C-3PO draws for its CONTROLLER) ·
#//           reqboundary=Action_TargetAnsweredAcrossRequestBoundary
#//           modes=2P,TwinSuns (unqualified "another unit" spans every seat — TwinSuns_AFarSeatUnitGetsTheToken) ·
#//           TeamSuns=N/A (no friendly/enemy wording; the unqualified pool is the same path)

## GIVEN
CommonSetup: ggw/bbw
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]
WithP1GroundArena: IC27_070:1:0
WithP1GroundArena: TWI_133:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:1
P1NODECISION

---

# Action_FourPowerUnitBecomesFive_NoDraw
#// The other half of the boundary pair: P1's Swoop Racer (4/3) becomes 5/4 — still under 6 — so the token
#// lands but no card is drawn.

## GIVEN
CommonSetup: ggw/bbw
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]
WithP1GroundArena: IC27_070:1:0
WithP1GroundArena: SOR_210:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:0
P1DECKCOUNT:2
P1NODECISION

---

# Offer_AnyOtherUnitOnTheTable_HimselfExcluded
#// "another unit" has no "friendly", so the pool is every unit in play except C-3PO: P1's Swoop Racer
#// (ground 1), P1's deployed leader unit (ground 2 — a deployed leader seats after the plain lines),
#// P1's TIE/ln Fighter in space, and P2's Battlefield Marine and Alliance X-Wing. C-3PO (ground 0) is
#// excluded. The pick is left PENDING.

## GIVEN
CommonSetup: ggw/bbw/{myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: IC27_070:1:0
WithP1GroundArena: SOR_210:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-2&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0
P1GROUNDARENAUNIT:2:ISLEADERUNIT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Action_EnemyFivePowerUnit_TheActivatorDraws
#// The token may go to an ENEMY unit, and the draw still belongs to whoever used the action. P1 gives
#// the token to P2's B1 Attack Platform (5 → 6): P1 draws, P2 does not. Two candidates keep the pick
#// interactive (P1's Swoop Racer is the other).

## GIVEN
CommonSetup: ggw/bbw
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]
WithP1GroundArena: IC27_070:1:0
WithP1GroundArena: SOR_210:1:0
WithP2GroundArena: TWI_133:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1HANDCOUNT:1
P2HANDCOUNT:0
P1NODECISION

---

# Action_NoOtherUnit_UsableButResolvesToNothing
#// With no other unit in play "another unit" has no referent. The [Exhaust] cost still changes the game
#// state (CR 6.4.587.c), so the action is usable: C-3PO exhausts, no token is created anywhere (he never
#// gets it himself), and no card is drawn.

## GIVEN
CommonSetup: ggw/bbw
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]
WithP1GroundArena: IC27_070:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:0
P1NODECISION

---

# Exhausted_CannotPayTheExhaustCost
#// The action costs [Exhaust]: an already-exhausted C-3PO cannot pay it, so nothing happens — the B1
#// Attack Platform gets no token and no card is drawn.

## GIVEN
CommonSetup: ggw/bbw
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]
WithP1GroundArena: IC27_070:0:0
WithP1GroundArena: TWI_133:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1HANDCOUNT:0
P1NODECISION

---

# ControlChange_TheController_UsesItAndDraws
#// C-3PO is OWNED by P1 but CONTROLLED by P2 (the end state after a take-control effect). P2 uses the
#// action: the only other unit is P2's B1 Attack Platform (5 → 6), and the card is drawn by P2 — the
#// activator — not by the owner. (Controlled units seat after the plain arena lines: Platform 0, C-3PO 1.)

## GIVEN
CommonSetup: ggw/bbw
WithActivePlayer: 2
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]
WithP2GroundArena: TWI_133:1:0
WithP2GroundArenaControlled: IC27_070:1

## WHEN
- P2>UseUnitAbility:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:IC27_070
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:POWER:6
P2HANDCOUNT:1
P1HANDCOUNT:0

---

# TwinSuns_AFarSeatUnitGetsTheToken
#// Three seats, no teams. The only other unit on the table is seat 3's B1 Attack Platform — a pool built
#// from "my + the one opponent in view" would find nothing. It gets the token (5 → 6) and P1 draws.

## GIVEN
CommonSetup: ggw/bbw
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1Deck: [SOR_095 SOR_095]
WithP1GroundArena: IC27_070:1:0
WithP3GroundArena: TWI_133:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
SEATCOUNT:3
P3GROUNDARENAUNIT:0:POWER:6
P3GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:1
P1NODECISION

---

# Action_TargetAnsweredAcrossRequestBoundary
#// The pick answered in a FRESH process: P1 chooses its B1 Attack Platform over the Swoop Racer, and both
#// the token and the power-gated draw still land.

## GIVEN
CommonSetup: ggw/bbw
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]
WithP1GroundArena: IC27_070:1:0
WithP1GroundArena: SOR_210:1:0
WithP1GroundArena: TWI_133:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P1GROUNDARENAUNIT:2:POWER:6
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1HANDCOUNT:1
P1NODECISION
