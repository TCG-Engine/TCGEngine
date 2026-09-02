# DamagesTheChosenFriendly
#// HMW_158 Battle-Scarred Destroyer (Unit, Space, 7/8, cost 6, [Aggression][Villainy],
#// Imperial/Vehicle/Capital Ship, non-unique) — "When Played: Deal 4 damage to a friendly unit."
#//
#// COVERAGE: offer=Offer_OnlyFriendlyUnits_BothArenas_EnemiesExcluded (SELECTABLEEXACT: "friendly" is a
#//           controller restriction and the clause names no arena, so both of yours are in and neither
#//           of theirs is) ·
#//           decline=N/A (structural: MANDATORY — no "may", no "up to". A plain MZCHOOSE cannot be
#//           passed, and the arena is a public zone so the hidden-zone always-declinable rule does not
#//           reach it. AloneOnTheBoard_ItMustDamageITSELF is the sharp end of that) ·
#//           boundary=N/A (structural: a flat 4, no threshold and no count) ·
#//           control=N/A (structural: "friendly" is recomputed from live control when the pool is built
#//               and the clause names no owner-scoped zone — a stolen unit is simply friendly or not,
#//               which is the axis TeamSuns_ATeammatesUnitIsFriendly already walks) ·
#//           reqboundary=RequestBoundary_TheTargetSurvivesIt ·
#//           modes=2P,TeamSuns — "FRIENDLY" spans your TEAM in a 2v2, so a teammate's unit is a legal
#//           target even though you do not control it. No player reference anywhere in the text, so no
#//           Twin Suns section: at 3-4 seats free-for-all this is the same self-only pool as Premier.
#//
#// Positive: a 4/7 Frigate is already in the space arena, so playing the Destroyer gives TWO friendly
#// targets (the Frigate and the Destroyer itself) and the choose really prompts. The Frigate takes 4 and
#// survives on 7 HP; the Destroyer is untouched.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_158
WithP1SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:DAMAGE:4
P1SPACEARENAUNIT:1:CARDID:HMW_158
P1SPACEARENAUNIT:1:DAMAGE:0

---

# Offer_OnlyFriendlyUnits_BothArenas_EnemiesExcluded
#// HMW_158 — the OFFER cell, pinning two things at once that answering a target cannot:
#//   • "FRIENDLY" is a controller restriction — the enemy Frigate must NOT be selectable, and a pool
#//     built from "a unit" (unqualified) would happily include it;
#//   • the clause names NO ARENA, so a friendly GROUND unit is just as legal as a space one. A pool
#//     built from the source's own arena would silently drop it.
#// Three legal targets: the friendly ground Marine, the friendly space Frigate, and the Destroyer
#// itself. The decision is left pending so the pool can be read.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_158
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&mySpaceArena-1

---

# AloneOnTheBoard_ItMustDamageITSELF
#// ⚠ HMW_158 — the sharp consequence of "friendly" plus MANDATORY. A unit's When Played resolves after
#// it has entered play, so the Destroyer is itself a friendly unit and a legal target for its own
#// ability. Played onto an otherwise empty board it is the ONLY legal target, so the mandatory choose
#// auto-resolves onto it and it damages itself for 4 — there is no decline to take.
#// It is a 7/8, so it survives on 4 damage; the card is self-limiting rather than self-defeating.
#// P1NODECISION is load-bearing: a single-target MANDATORY choose resolves via PASSPARAMETER with no
#// prompt, so an implementation that offered a pass here would be visibly wrong.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_158

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_158
P1SPACEARENAUNIT:0:DAMAGE:4
P1SPACEARENAUNIT:0:HP:8
P1NODECISION

---

# KillsYourOwnUnit_WhenChosen
#// HMW_158 — 4 damage is enough to destroy most things you own, and nothing in the text protects a
#// friendly target. The 2/1 TIE is chosen and dies; the Destroyer stays on the board undamaged, so the
#// section separates "the target died" from "the whole play fizzled".
#// Two friendly targets exist (the TIE and the Destroyer), so this is a real choice rather than the
#// forced self-hit above.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_158
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_158
P1SPACEARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_225

---

# ShieldedFriendly_AbsorbsTheFourDamage
#// HMW_158 — interaction with the standard modifier. A Shield token prevents ALL damage from one
#// source, so a shielded friendly takes nothing and the token is defeated instead. Without the shield
#// the 4/7 Frigate would be sitting on 4 damage, so DAMAGE:0 plus SHIELDCOUNT:0 pins both halves.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_158
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P1SPACEARENAUNIT:1:CARDID:HMW_158
P1SPACEARENAUNIT:1:DAMAGE:0

---

# TeamSuns_ATeammatesUnitIsFriendly
#// ⚠ HMW_158 — the TEAM SUNS cell, earned by the printed word "FRIENDLY". In a 2v2 a teammate's unit is
#// friendly even though you do not CONTROL it, so it is a legal target for this ability. Teams are seat
#// parity (1+3 vs 2+4), so P1's partner is P3.
#// P1 fields nothing of its own beyond the Destroyer, and the teammate's Frigate is damaged for 4 —
#// which a self-only pool (the older `ZoneSearch('myGroundArena'/'mySpaceArena')` shape that predates
#// the shared helper) could never reach. The enemy seats' units stay untouched, so "friendly" is shown
#// to mean the TEAM rather than merely "not mine".

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_158
WithP3SpaceArena: JTL_069:1:0
WithP2SpaceArena: SOR_225:1:0
WithP4SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3SpaceArena-0

## EXPECT
SEATCOUNT:4
P3SPACEARENAUNIT:0:CARDID:JTL_069
P3SPACEARENAUNIT:0:DAMAGE:4
P1SPACEARENAUNIT:0:CARDID:HMW_158
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P4SPACEARENAUNIT:0:DAMAGE:0

---

# RequestBoundary_TheTargetSurvivesIt
#// HMW_158 — the REQUEST-BOUNDARY cell. The target choose ends the request, so the continuation that
#// applies the 4 damage resumes in a fresh process and must carry the chosen target on the decision
#// itself. Same board and answer as the positive, with one boundary inserted before the answer.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_158
WithP1SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:DAMAGE:4
P1SPACEARENAUNIT:1:CARDID:HMW_158
P1SPACEARENAUNIT:1:DAMAGE:0
