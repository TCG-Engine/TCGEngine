# ShieldsEveryFriendlyGroundUnitWithoutOne
#// HMW_080 Fambaa Shield Team (Unit, Ground, 4/7, cost 7, [Vigilance][Heroism], Creature/Gungan,
#// non-unique) — "When Played: Give a Shield token to each friendly ground unit without a Shield token
#// on it."
#//
#// COVERAGE: offer=N/A (structural: "EACH" is a LOOP, not a choice — there is no target pool and no
#//           decision at any point. The equivalent of an offer here is WHICH units the loop touches,
#//           which the three exclusion sections below pin from three different directions) ·
#//           decline=N/A (structural: no "may", no "up to", and nothing to answer) ·
#//           boundary=N/A (structural: one token per unit, no threshold and no count) ·
#//           control=N/A (structural: "friendly" is read live when the loop runs and the clause names
#//               no owner-scoped zone; the team axis is walked by TeamSuns_ATeammatesGroundUnitIsShielded) ·
#//           reqboundary=RequestBoundary_TheLoopStillRunsAfterTheBoundary ·
#//           modes=2P,TeamSuns — "FRIENDLY" spans the team in a 2v2. No player reference, so no Twin
#//           Suns section: at 3-4 seats free-for-all this is the same self-only set as Premier.
#//
#// THREE filters in one sentence — friendly / ground / without-a-Shield — and each needs its own
#// negative, because dropping any one of them still passes this positive.
#// ⚠ The Fambaa is itself a friendly ground unit with no Shield, so it shields ITSELF too. The text says
#// "each friendly ground unit", not "each OTHER", so that is correct and is asserted here.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:7
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_080
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: LOF_247:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:LOF_247
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:2:CARDID:HMW_080
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1

---

# AlreadyShielded_DoesNotGetASecond
#// ⚠ HMW_080 — THE EXCLUSION, and the only clause of the three that a careless loop silently ignores.
#// "without a Shield token on it" means an already-shielded unit is skipped entirely, not topped up: it
#// ends on exactly ONE Shield, not two. The unshielded neighbour beside it still gets one, so the
#// section separates "the filter works" from "the whole loop stopped".
#// A loop that dropped the filter leaves the first unit on SHIELDCOUNT:2 and passes every other section
#// in this file.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:7
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_080
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: LOF_247:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:LOF_247
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:2:CARDID:HMW_080
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1

---

# FriendlySpaceUnits_AreNotShielded
#// HMW_080 — the ARENA filter. "each friendly GROUND unit" excludes your own space units, and a loop
#// built from SWUFriendlyUnits() with no arena argument would shield them too. The friendly ground unit
#// beside it still gets one, so a dead loop cannot pass this either.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:7
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_080
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:HMW_080
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:SHIELDCOUNT:0

---

# EnemyGroundUnits_AreNotShielded
#// HMW_080 — the CONTROLLER filter. "each FRIENDLY ground unit" leaves the opponent's board alone; a
#// loop over every ground unit on the table would hand the enemy free Shields, which is a strictly
#// worse card. The friendly units still get theirs.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:7
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_080
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:HMW_080
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:1:SHIELDCOUNT:0

---

# AloneOnTheBoard_ShieldsOnlyITSELF
#// HMW_080 — the degenerate case. With no other friendly ground unit the loop still has exactly one
#// member: the Fambaa itself. It shields itself and nothing else happens — no prompt, no fizzle.
#// A loop that excluded the source ("each OTHER friendly ground unit", which the card does not say)
#// leaves SHIELDCOUNT at 0 here while passing every multi-unit section above.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:7
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_080
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# TeamSuns_ATeammatesGroundUnitIsShielded
#// ⚠ HMW_080 — the TEAM SUNS cell, earned by "FRIENDLY". In a 2v2 a teammate's ground unit is friendly
#// even though you do not control it, so the loop must reach it. Teams are seat parity (1+3 vs 2+4), so
#// P1's partner is P3.
#// The enemy seats each field a ground unit as well, so this catches BOTH failure directions at once: a
#// self-only loop misses P3, and an everyone-on-the-table loop shields P2 and P4.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:7
}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_080
WithP3GroundArena: LOF_247:1:0
WithP2GroundArena: SEC_080:1:0
WithP4GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:CARDID:LOF_247
P3GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_080
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P4GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# RequestBoundary_TheLoopStillRunsAfterTheBoundary
#// HMW_080 — the REQUEST-BOUNDARY cell in its no-decision form. The card raises no prompt, but a request
#// ends at every player ACTION, so the play that runs the loop happens in a different process from the
#// action before it: the friendly set must be recomputed from the live board rather than from anything
#// cached earlier. A cheap filler is played first, then the boundary, then the Fambaa — which must still
#// find and shield it.
#// ⚠ RESOURCES ARE 11, NOT 9. SOR_095 Battlefield Marine is [Command][Heroism] and this board covers
#// only Vigilance and Heroism, so Command is uncovered and the filler costs 2 + 2 = 4, not 2. At 9 the
#// Fambaa was then 2 short, silently stayed in hand, and the section failed with one unit in the arena —
#// which reads exactly like the loop dying across the boundary rather than an aspect-penalty miscount.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:11
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: HMW_080

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:HMW_080
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
