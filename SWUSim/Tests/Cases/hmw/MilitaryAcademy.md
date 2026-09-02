# FriendlyUnitsGainOverwhelm
#// HMW_112 Military Academy (Upgrade, cost 1, [Command][Villainy], Fortification, non-unique) —
#// "Fortify (Attach this to your base, not a unit.) / Attached base gains: 'Friendly units gain
#// Overwhelm.'"
#//
#// COVERAGE: offer=Fortify_AttachesToTheBaseNotAUnit (SELECTABLEEXACT — the Fortify pool is the base
#//           slot and NOT the units in play, which is the whole point of the keyword) ·
#//           decline=N/A (structural: no "may" anywhere; playing an upgrade is not an optional effect) ·
#//           boundary=N/A (structural: a boolean keyword grant, no threshold or count. Note a second
#//           copy adds NOTHING — Overwhelm is boolean — which is the deliberate difference from
#//           HMW_145 Origin Tree Shyyyo, whose numeric effect stacks) ·
#//           control=N/A (structural: the ability is hosted on the BASE, which has one controller and
#//           cannot change hands; "friendly" is read live off that seat) ·
#//           reqboundary=RequestBoundary_TheGrantStillReadsAfterTheBoundary ·
#//           modes=2P,TeamSuns — "FRIENDLY units" is relative to the BASE's controller and spans the
#//           team in a 2v2. No player reference, so no Twin Suns section.
#//
#// FORTIFY itself needs no code (HMW_112 is in $Fortify_Cards). The grant is base-hosted and CONTINUOUS:
#// nothing is stored, HasConditionalKeyword_Overwhelm asks the board on every read.

## GIVEN
CommonSetup: grk/grk/{
  myResources:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_112
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm

---

# Fortify_AttachesToTheBaseNotAUnit
#// HMW_112 — the OFFER cell, and it is what the Fortify keyword actually means. The pool must be the
#// BASE slot, never the units in play. A friendly unit and an enemy unit are both on the table so an
#// ordinary upgrade pool would be visibly different.
#// With a single legal host the attach auto-resolves, so the proof is the END STATE plus P1NODECISION
#// rather than a pending offer: the upgrade is on the base, and neither unit is carrying it.

## GIVEN
CommonSetup: grk/grk/{
  myResources:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_112
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1BASEUPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# EnemyUnitsDoNotGainOverwhelm
#// HMW_112 — the CONTROLLER negative. "FRIENDLY units" is relative to the base carrying the Academy, so
#// the opponent's units gain nothing. A grant written as "each unit in play" passes the positive above
#// and fails here.

## GIVEN
CommonSetup: grk/grk/{
  myResources:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_112
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P2GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# GrantDiesWithTheUpgrade
#// ⚠ HMW_112 — THE REVOCATION TEST, the one-line negative that granted abilities routinely ship without.
#// The Academy is played (unit gains Overwhelm), then SOR_251 Confiscate defeats it — and the unit must
#// LOSE Overwhelm. A grant registered once on the unit and never revoked looks identical in every other
#// section in this file.
#// This works by construction here because the grant is a live board read rather than stored state, but
#// "by construction" is exactly the claim that needs a test.

## GIVEN
CommonSetup: grk/grk/{
  myResources:3
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_112
WithP1Hand: SOR_251
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1BASEUPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# TeamSuns_ATeammatesUnitsGainOverwhelm
#// ⚠ HMW_112 — the TEAM SUNS cell, earned by "FRIENDLY units". In a 2v2 a teammate's units are friendly
#// to the base carrying the Academy, so they gain Overwhelm too. Teams are seat parity (1+3 vs 2+4), so
#// P1's partner is P3.
#// Reading "friendly" as "units you control" — the self-only trap this set keeps presenting — passes
#// every other section and fails here. Both enemy seats field a unit, so an "everyone in play" grant is
#// caught in the same board.

## GIVEN
CommonSetup: grk/grk/{
  myResources:1
}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_112
WithP3GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP4GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P2GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
P4GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# RequestBoundary_TheGrantStillReadsAfterTheBoundary
#// HMW_112 — the REQUEST-BOUNDARY cell in its no-decision form. The Academy is attached by one action
#// and the grant is read during a LATER one, in a fresh process — so it must be recomputed from the
#// board rather than cached at attach time. The unit is played AFTER the boundary and must still gain
#// Overwhelm, which also proves the grant reaches a unit that entered play after the upgrade landed.

## GIVEN
CommonSetup: grk/grk/{
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_112
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1BASEUPGRADECOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
