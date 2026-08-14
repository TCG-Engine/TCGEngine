# Modal_Discard6AndShield
#// COVERAGE: offer=Offer_ShieldPoolIsEveryUnit (any unit incl. enemy deployed leader) +
#//           Offer_DefeatPoolIsRemainingHP3OrLess (remaining-HP filter across arenas)
#//           decline=N/A ("Choose two" is mandatory; empty-pool modes are still pickable and no-op —
#//           EmptyDeckDiscard6_And_Heal0_NoOp / NoUnits_DefeatAndShield_NoOp are the nothing-happens
#//           branches) · control=N/A (one-shot event, no per-unit marker outliving resolution)
#//           boundary=N/A (no duration effect) · reqboundary=all sections (each mode pick and each
#//           target answer arrives in its own request)
#//
#// SOR_058 Vigilance (event, cost 4) — "Choose two, in any order." P1 chooses Discard6 (mill 6 from the
#// opponent's deck) then Shield (give a Shield to a unit; P1's lone unit auto-targets). The two modes
#// resolve in sequence.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_058
WithP1Resources: 4
WithP1GroundArena: SEC_080:1:0
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Discard6
- P1>AnswerDecision:Shield

## EXPECT
P2DECKCOUNT:2
P2DISCARDCOUNT:6
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1

---

# Modal_Heal5AndDefeat
#// SOR_058 Vigilance — the other two modes. P1 chooses Heal5 (heal 5 from a base — picks its own base,
#// which was at 5 damage → 0) then Defeat (defeat a unit with ≤3 remaining HP — SOR_128 is a 3/1, the
#// only qualifying unit, auto-defeated).

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  myBaseDamage:5;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_058
WithP1Resources: 4
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Heal5
- P1>AnswerDecision:myBase-0
- P1>AnswerDecision:Defeat

## EXPECT
P1BASEDMG:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# EmptyDeckDiscard6_And_Heal0_NoOp
#// SOR_058 Vigilance — modes with nothing to act on still resolve as no-ops. P2's deck is EMPTY, so
#// Discard6 mills nothing; both bases are undamaged, so Heal5 heals 0 (the base pool always offers
#// both bases — the heal-0 pick is a deliberate soft pass, same treatment as the SOR_074 Repair
#// ruling: the prompt only auto-skips when the mode has no pool at all). Nothing on the board
#// changes; the event still lands in the discard.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_058
WithP1Resources: 4

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Discard6
- P1>AnswerDecision:Heal5
- P1>AnswerDecision:myBase-0

## EXPECT
P2DECKCOUNT:0
P2DISCARDCOUNT:0
P1BASEDMG:0
P2BASEDMG:0
P1DISCARDCOUNT:1
P1NODECISION

---

# NoUnits_DefeatAndShield_NoOp
#// SOR_058 Vigilance — with NO units anywhere, both unit-targeting modes (Defeat / Shield) have empty
#// pools and resolve as silent no-ops: no target choice is raised for either, and the game ends the
#// play cleanly with no pending decision.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_058
WithP1Resources: 4

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Defeat
- P1>AnswerDecision:Shield

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1BASEDMG:0
P2BASEDMG:0
P1DISCARDCOUNT:1
P1NODECISION

---

# Offer_ShieldPoolIsEveryUnit
#// SOR_058 Vigilance — the Shield mode targets "a unit": ANY unit, both sides, including a deployed
#// enemy leader unit. P1 picks Shield first and the target choice is left pending so the exact pool
#// can be asserted: P1's ground unit, P2's ground unit, and P2's deployed leader unit.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021;
  theirLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_058
WithP1Resources: 4
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirGroundArena-1

---

# Offer_DefeatPoolIsRemainingHP3OrLess
#// SOR_058 Vigilance — the Defeat mode's pool is filtered by REMAINING HP (max HP minus damage), not
#// printed HP: P1's SOR_128 (3/1, remaining 1) and P2's LAW_124 (4/7 with 4 damage, remaining 3)
#// qualify; P1's undamaged SOR_195 in space (3/4, remaining 4) does not. The choice is left pending
#// to assert the exact pool.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_058
WithP1Resources: 4
WithP1GroundArena: SOR_128:1:0
WithP1SpaceArena: SOR_195:1:0
WithP2GroundArena: LAW_124:1:4

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Defeat

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
