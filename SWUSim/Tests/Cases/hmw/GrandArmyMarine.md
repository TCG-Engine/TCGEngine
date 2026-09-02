# GivesShieldToTheChosenGungan
#// HMW_072 Grand Army Marine (Unit, Ground, 2/2, cost 2, [Vigilance][Heroism], Gungan/Trooper,
#// non-unique) — "When Played: Give a Shield token to a friendly Gungan unit."
#//
#// COVERAGE: offer=Offer_OnlyFriendlyGungans_NonGungansAndEnemiesExcluded (SELECTABLEEXACT — the clause
#//           carries TWO restrictions, a controller one and a trait one, and only the pool shows both) ·
#//           decline=N/A (structural: MANDATORY — no "may", no "up to"; a plain MZCHOOSE over a public
#//           zone, so neither the printed text nor the hidden-zone rule offers a way out) ·
#//           boundary=N/A (structural: exactly one token to exactly one unit; no threshold, no count) ·
#//           control=N/A (structural: "friendly" is recomputed from live control when the pool is built,
#//               and the clause names no owner-scoped zone — the axis it does have is the team one,
#//               walked by TeamSuns_ATeammatesGunganIsFriendly) ·
#//           reqboundary=RequestBoundary_TheTargetSurvivesIt ·
#//           modes=2P,TeamSuns — "FRIENDLY" spans the team in a 2v2. No player reference in the text,
#//           so no Twin Suns section: at 3-4 seats free-for-all the pool is the same self-only set.
#//
#// ⚠ THE MARINE IS ITSELF A GUNGAN (traits: Gungan, Trooper), and a unit's When Played resolves after it
#// has entered play — so there is ALWAYS at least one legal target and this clause can never fizzle.
#// That makes the usual no-legal-target cell structurally unreachable, and turns the lone-unit case into
#// a forced self-shield instead (AloneOnTheBoard_ShieldsITSELF).
#//
#// Positive: a Gungan Warrior is already out, so playing the Marine gives two legal targets and the
#// choose really prompts. The Warrior takes the Shield; the Marine gets nothing.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_072
WithP1GroundArena: LOF_247:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:LOF_247
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:HMW_072
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0

---

# Offer_OnlyFriendlyGungans_NonGungansAndEnemiesExcluded
#// HMW_072 — the OFFER cell, and this clause stacks TWO restrictions that answering a target cannot
#// separate:
#//   • "FRIENDLY"  — the enemy Gungan Warrior must not be selectable;
#//   • "GUNGAN"    — the friendly Battlefield Marine (Rebel/Trooper) must not be selectable either.
#// Drop the trait filter and the non-Gungan appears; drop the controller filter and the enemy appears.
#// The pool is exactly the friendly Gungan Warrior plus the Marine itself (index 2, since a played unit
#// is appended after the seeded ones). Decision left pending so the pool can be read.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_072
WithP1GroundArena: LOF_247:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LOF_247:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2

---

# AloneOnTheBoard_ShieldsITSELF
#// HMW_072 — the consequence of the Marine being a Gungan itself. Played onto an empty board it is the
#// only legal target, so the MANDATORY choose auto-resolves onto it and it shields itself.
#// P1NODECISION is load-bearing: a single-target mandatory choose resolves through PASSPARAMETER with no
#// prompt at all, so an implementation that raised a pass here would be visibly wrong — and one that
#// EXCLUDED the source ("another friendly Gungan", which the card does not say) would fizzle instead and
#// leave SHIELDCOUNT at 0.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_072

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_072
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1NODECISION

---

# NonGunganFriendlyOnly_StillShieldsITSELF
#// HMW_072 — the trait filter proven from the other side. The only OTHER friendly unit is a
#// Battlefield Marine (Rebel/Trooper, no Gungan), so it is not a legal target and the pool narrows back
#// to the Marine alone — which auto-resolves onto itself.
#// If the trait filter were dropped there would be TWO targets, a prompt would appear, and P1NODECISION
#// would fail; if the source were excluded there would be NO target and the shield would never land.
#// The two assertions therefore fail in opposite directions.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_072
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:HMW_072
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# TeamSuns_ATeammatesGunganIsFriendly
#// ⚠ HMW_072 — the TEAM SUNS cell, earned by "FRIENDLY". In a 2v2 a teammate's Gungan is friendly even
#// though you do not control it, so it is a legal target. Teams are seat parity (1+3 vs 2+4): P1's
#// partner is P3.
#// ⚠ This is also why the card does NOT use GiveTokenUpgrade's `friendlyOnly` option — that maps to
#// side 'my', which is SELF-ONLY and could never reach a teammate. The section fails against that shape.
#// The enemy seats each field a Gungan too, so a pool built as "any Gungan" would also be caught here:
#// only the teammate's is shielded, theirs are untouched.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_072
WithP3GroundArena: LOF_247:1:0
WithP2GroundArena: LOF_247:1:0
WithP4GroundArena: LOF_247:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:CARDID:LOF_247
P3GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_072
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P4GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# RequestBoundary_TheTargetSurvivesIt
#// HMW_072 — the REQUEST-BOUNDARY cell. The target choose ends the request, so the continuation that
#// attaches the Shield resumes in a fresh process and must carry the chosen target on the decision
#// itself. Same board and answer as the positive, with one boundary inserted before the answer.

## GIVEN
CommonSetup: bbw/bbw/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_072
WithP1GroundArena: LOF_247:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_247
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:HMW_072
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
