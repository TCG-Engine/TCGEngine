# AmbushTrade
#// COVERAGE: offer=Offer_OnlyUnitsCostingSixOrLess (pending SELECTABLEEXACT: units ≤6 printed only;
#//           events and cost-8 walker excluded) · decline=ChooseNothing_NothingPlayed (nothing
#//           played, Epic Action spent) + NoEligibleUnits_SoftPass_EpicSpent (empty-pool soft pass)
#//           · boundary=AmbushTrade (cost paid exactly: 2 ready resources → 0) + ObiWanAmbush
#//           (aspect penalty still charged on the play: 8 = 6+2) · control=
#//           CrossSeat_P2UsesTheLabAndAmbushesFromItsOwnHand (a base never changes control, so the
#//           axis is read as "who resolves it": the Lab is moved onto SEAT 2's base and every "your"
#//           — the hand the pool comes from, the arena the unit enters, the row that pays, the Epic
#//           flag that is spent, the arena the granted Ambush reaches — must follow that seat)
#//           · reqboundary=Offer_OnlyUnitsCostingSixOrLess (the choose pends across the boundary)
#// SOR_022 Energy Conversion Lab: Epic Action plays BF Marine at printed cost, grants AMBUSH.
#// P1 has exactly 2 resources (printed cost of SOR_095, no aspect penalty with SOR_014+SOR_022).
#// Ambush attack into opponent's ready Marine: both 3/3 units trade. Both arenas empty.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 2:SOR_095
WithP1Hand: SOR_095
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED

---

# ObiWanAmbush
#// SOR_022 ECL: Obi-Wan Kenobi played out-of-aspect, paying printed cost + aspect penalty.
#// SOR_049: cost 6, 4/6, Vigilance+Heroism. With SOR_014 (Aggression/Heroism) + SOR_022 (Command):
#// Heroism covered, Vigilance uncovered → +2 penalty → player pays 8 total.
#// Ambush attack into P2's ready BF Marine (3/3). Obi-Wan (4 power) kills Marine. Takes 3 back.
#// Obi-Wan survives with 3 damage (6 HP). Resources exhausted to 0.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 8:SOR_095
WithP1Hand: SOR_049
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_049
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED

---

# Wilderness_AmbushFirst
#// SOR_022 ECL: Wilderness Fighter (Shielded) played with AMBUSH. Player picks Ambush first.
#// SOR_064: cost 3, 2/4, Shielded, Vigilance aspect. +2 penalty → pays 5.
#// P2 Marine has 1 damage. Wilderness attacks (no shield yet) → Marine dies. Takes 3 back → 3 damage.
#// Shield token then applied after combat. Survives at 3 damage with a fresh shield.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 5:SOR_095
WithP1Hand: SOR_064
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>ResolveTrigger:Ambush
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_064
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED

---

# Wilderness_ShieldFirst
#// SOR_022 ECL: Wilderness Fighter (Shielded) played with AMBUSH. Player declines Ambush.
#// SOR_064: cost 3, 2/4, Shielded, Vigilance aspect. +2 penalty → pays 5.
#// Ambush fires first (auto-dispatch). Player says NO → no attack. Shielded fires next → shield given.
#// Unit survives with 0 damage and 1 shield.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 5:SOR_095
WithP1Hand: SOR_064
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>ResolveTrigger:Shielded
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_064
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED

---

# Offer_OnlyUnitsCostingSixOrLess
#// SOR_022 ECL — the Epic Action's pool is exactly the hand's UNITS with printed cost ≤6:
#// Reinforcement Walker (SOR_119, cost 8) and Vanquish (SOR_078, an event) are excluded;
#// Rebel Pathfinder / Alliance X-Wing / AT-ST are in. Decision left pending to pin the offer.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 10:SOR_095
WithP1Hand: [SOR_119 SOR_239 SOR_237 SOR_232 SOR_078]

## WHEN
- P1>UseBaseAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-1&myHand-2&myHand-3

---

# ChooseNothing_NothingPlayed
#// SOR_022 ECL — declining the unit choice plays nothing: both units stay in hand, no arena
#// entry, and the Epic Action is spent (the use was the cost, not the play).

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 10:SOR_095
WithP1Hand: [SOR_239 SOR_232]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:2
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:10
P1BASE:EPICUSED
P1NODECISION

---

# NoEligibleUnits_SoftPass_EpicSpent
#// SOR_022 ECL — with no unit costing ≤6 in hand (only an event), using the base ability is a
#// legal soft pass: no prompt, nothing played, Epic Action spent.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 10:SOR_095
WithP1Hand: SOR_078

## WHEN
- P1>UseBaseAbility

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0
P1BASE:EPICUSED
P1NODECISION

---

# UnaffordableUnit_ExcludedFromPool
#// Candidate #5 fix guard: the pool must ALSO be affordability-gated (full payment capacity,
#// effective cost) — with 3 resources the AT-ST (cost 6) cannot be paid for, and picking it would
#// burn the once-per-game Epic Action on a silent no-op (the epic-preservation family). The two
#// cost-2 Heroism units stay in; the AT-ST is out. Offer left pending.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 3:SOR_095
WithP1Hand: [SOR_239 SOR_237 SOR_232]

## WHEN
- P1>UseBaseAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# SimulateRequestBoundary_AmbushGrantSurvives
#// SOR_022 ECL — the granted-Ambush "do you want to attack?" prompt ends the request in production:
#// the AMBUSH grant on the just-played unit and the epic-action context must come back from the
#// serialized gamestate, not from in-memory state left over from the play. Mirrors AmbushTrade with
#// the boundary inserted before the Ambush YES.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 2:SOR_095
WithP1Hand: SOR_095
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED

---

# ChooseNothing_ByConfirmingEmpty_StillClosesTheAction
#// ⚠ REGRESSION GUARD, live bug 2026-08-27 — the PASS twin of ChooseNothing_NothingPlayed, which answers
#// `-` and could not see this.
#//
#// SOR_022#0's null branch calls SWUAfterAction(): the continuation is what CLOSES the Epic Action. It is
#// queued as a RAW MZMAYCHOOSE + CUSTOM pair (not via SWUQueueMayChooseTarget), so flipping that helper's
#// default did not cover it — the flag had to be added at this call site. Skipped on a sticky "PASS", the
#// once-per-game Epic Action was spent and the player still held the turn.
#//
#// P1OnlyActions is deliberately absent so TURNPLAYER is observable — that is the whole assertion.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithActivePlayer: 1
WithP1Resources: 10:SOR_095
WithP1Hand: [SOR_239 SOR_232]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:PASS

## EXPECT
TURNPLAYER:2
P1HANDCOUNT:2
P1GROUNDARENACOUNT:0
P1BASE:EPICUSED
P1NODECISION

---

# CrossSeat_P2UsesTheLabAndAmbushesFromItsOwnHand
#// Intended: "Play a unit that costs 6 resources or less from YOUR hand. Give IT Ambush for this
#// phase" resolves entirely for the seat whose base carries the Epic Action — every zone, the payment
#// and the Ambush grant. Every other section in this file drives the Lab from seat 1; this one moves
#// the Lab onto SEAT 2's base and drives it from there, so any seat-1-framed step (the hand the pool
#// is gathered from, the arena the unit enters, whose resources pay, whose Epic flag is spent, and
#// which arena the granted Ambush attack may reach into) is visible.
#// Mirror of AmbushTrade from the other side: P2 has exactly 2 ready resources — the printed cost of
#// the Battlefield Marine with no aspect penalty — so the play can only be paid out of P2's row. The
#// granted Ambush attack reaches P1's ready Marine and the two 3/3s trade, emptying both arenas.
#// P1's Epic Action must still be AVAILABLE: only the Lab's own controller spent one.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_023;
  theirBase:SOR_022
}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 2:SOR_095
WithP2Hand: SOR_095
WithP1GroundArena: SOR_095:1:0

## WHEN
- P2>UseBaseAbility
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P2RESAVAILABLE:0
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2BASE:EPICUSED
P1BASE:EPICAVAILABLE
