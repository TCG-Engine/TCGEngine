# OnAttack_NamedTrait_EnemyUnitsLoseIt
#// HMW_108 The First Legion, Vader's Fist — Cost 4 · 5/5 · Ground · [Command][Villainy] ·
#// Imperial, Trooper · unique
#// Text: "On Attack: Name a Trait. Enemy cards, including those not in play, lose that Trait for
#//        this phase."
#//
#// COVERAGE: offer=N/A (structural: NAMETRAIT is a free-text pick over the whole printed trait universe,
#//             not a target pool — the server validates the answer against SWUAllTraits, which
#//             UnrecognisedTraitNamed_NothingHappens pins) ·
#//           decline=N/A (structural: no "may" — naming is mandatory once the attack begins) ·
#//           boundary=N/A (no threshold) ·
#//           control=N/A (structural: the flag is stamped on the NAMING seat and read against each
#//             card's OWNER, so there is no owner-vs-controller zone to resolve for the wrong player) ·
#//           reqboundary=RequestBoundary_TheNamedTraitSurvives ·
#//           modes=2P,TwinSuns,TeamSuns — "ENEMY cards" is a player reference AND a friendly/enemy
#//             word, so both axes apply: TwinSuns_EveryOpponentLosesTheTrait and
#//             TeamSuns_TheTeammateKeepsTheTrait
#//
#// SEC_046 Galen Erso one axis over: Galen blanks a named CARD's abilities including out of play, this
#// strips a named TRAIT from every enemy card including out of play.
#// NOTTRAIT: routes through TraitContains, which is the in-play chokepoint the suppression hooks into.

## GIVEN
CommonSetup: grk/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_108:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Vehicle

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:NOTTRAIT:Vehicle
P2BASEDMG:5
P1NODECISION

---

# FriendlyCardsKeepTheNamedTrait
#// "ENEMY cards" — the naming player's own Vehicles are untouched. P1 fields a TIE Fighter (Vehicle)
#// alongside The First Legion; it keeps the trait while the enemy X-Wing loses it, in the same section
#// so the two readings cannot both be satisfied by a blanket strip.

## GIVEN
CommonSetup: grk/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_108:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Vehicle

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:HASTRAIT:Vehicle
P2SPACEARENAUNIT:0:NOTTRAIT:Vehicle

---

# OnlyTheNamedTraitIsLost
#// The X-Wing is Rebel AND Vehicle AND Fighter. Naming Vehicle takes exactly one of them.

## GIVEN
CommonSetup: grk/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_108:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Vehicle

## EXPECT
P2SPACEARENAUNIT:0:NOTTRAIT:Vehicle
P2SPACEARENAUNIT:0:HASTRAIT:Rebel
P2SPACEARENAUNIT:0:HASTRAIT:Fighter

---

# NamedVehicle_ForceChokeCanNowHitAFormerVehicle
#// USER CASE 5. SOR_139 Force Choke deals 5 to a "non-VEHICLE unit". Its target filter reads the unit
#// object, so a Vehicle stripped of the trait becomes a legal target — the funny-but-correct edge of a
#// restriction phrased as an exclusion.
#// The X-Wing is 2/3 and takes 5, so it dies; the arena emptying is the readout.
#// ⚠ Force Choke is [Aggression][Villainy]; under a [Command] base and an [Aggression][Villainy] leader
#// one pip is unmatched, so it is billed 2 + 2 = 4 (its own "costs 1 less if you control a Force unit"
#// does not apply — The First Legion is not Force).

## GIVEN
CommonSetup: grk/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_108:1:0
WithP1Hand: SOR_139
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Vehicle
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_237

---

# ForceChokeStillCannotHitAVehicleThatKeptTheTrait
#// The control for the section above: with a DIFFERENT trait named, the X-Wing is still a Vehicle and
#// Force Choke cannot be aimed at it. With no other legal unit on the board the event fizzles and the
#// X-Wing is untouched — which is what separates "the strip made it legal" from "the filter never
#// worked".

## GIVEN
CommonSetup: grk/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_108:1:0
WithP1Hand: SOR_139
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Rebel
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:HASTRAIT:Vehicle

---

# NamedVehicle_EnemyPilotHasNoLegalHost
#// USER CASE 2. Piloting attaches to a friendly VEHICLE, and SWUPilotCanAttach reads the host object —
#// so once the enemy's Vehicles have lost the trait their pilot has nowhere to go and the Unit/Pilot
#// choice never appears. The pilot is played as an ordinary UNIT instead.
#// JTL_046 is a Piloting card; P2 holds it with a Vehicle already on the table.

## GIVEN
CommonSetup: grk/rrk/{myResources:4;theirResources:8}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: HMW_108:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2Hand: JTL_046

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Vehicle
- P2>PlayHand:0

## EXPECT
P2SPACEARENAUNIT:0:NOTTRAIT:Vehicle
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P2NODECISION

---

# NamedVehicle_NonVehicleUpgradeBecomesLegalOnAFormerVehicle
#// USER CASE 3, and the mirror of case 5: a restriction phrased as an EXCLUSION loosens when the trait
#// goes. SOR_136 Vader's Lightsaber attaches to a non-Vehicle unit; with the enemy X-Wing stripped of
#// Vehicle it becomes a legal host, so the upgrade auto-attaches to it as the only legal target.
#// ⚠ This is the direction that reads as a BUG if you only think about the drawback cases — it is
#// correct, and it is why the trait read has to be object-aware rather than a per-card allowlist.
#// ⚠ The strip creates a SECOND legal host: The First Legion is itself a non-Vehicle unit, so the host
#// pick is a real choice rather than an auto-attach. That the enemy X-Wing is even ON that menu is the
#// assertion.

## GIVEN
CommonSetup: grk/rrk/{myResources:8}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_108:1:0
WithP1Hand: SOR_136
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Vehicle
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_136

---

# MonMothma_NamedRebel_FindsNoRebelCardInTheirOwnDeck
#// USER CASE 1 — THE OUT-OF-PLAY HALF, and the whole reason the read is owner-aware. Mon Mothma
#// (SOR_096) searches the top 5 of her controller's deck for a REBEL card. Those cards are in a DECK,
#// where there is no object to carry a marker — so the filter runs through _SWUCardHasTrait(owner, …),
#// which knows the deck belongs to an enemy of the naming seat.
#// P2's deck is nothing but Rebel cards (SOR_095 Battlefield Marine is Rebel/Trooper) and the search
#// finds NONE of them.
#// ⚠ THE ANSWER IS DELIBERATELY ILLEGAL. A top-deck search with zero legal matches still PROMPTS, and
#// answering BLANK proves nothing — nothing is drawn either way, so the section would pass with the
#// whole suppression deleted (measured: a green mutation). Instead P2 answers with SOR_095, a card that
#// IS in the peeked five and WOULD be a legal Rebel pick but for the strip, and the assertion is that
#// the engine REFUSES it. That is strictly stronger than inspecting the client's match list, and it is
#// the only form that fails when the out-of-play read is removed.

## GIVEN
CommonSetup: grk/ggw/{myResources:4;theirResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: HMW_108:1:0
WithP2Hand: SOR_096
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Rebel
- P2>PlayHand:0
- P2>AnswerDecision:SOR_095

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_096
P2HANDCOUNT:0
P2DECKCOUNT:5
P2NODECISION

---

# MonMothma_ADifferentTraitNamed_StillFindsHerRebel
#// The control for case 1: with "Vehicle" named instead, Mon Mothma's search works normally and draws a
#// Rebel. Without this the section above would pass just as well against a search that never worked.

## GIVEN
CommonSetup: grk/ggw/{myResources:4;theirResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: HMW_108:1:0
WithP2Hand: SOR_096
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Vehicle
- P2>PlayHand:0
- P2>AnswerDecision:SOR_095

## EXPECT
P2GROUNDARENACOUNT:1
P2HANDCOUNT:1
P2DECKCOUNT:4

---

# TwinSuns_EveryOpponentLosesTheTrait
#// USER CASE 4, first half. "ENEMY cards" at 3-4 seats means EVERY opponent of the naming player, not
#// just seat 2 — the OtherPlayer() shortcut would strip from seat 2 alone.
#// Seats 2, 3 and 4 each field a Vehicle; all three lose it, and P1's own keeps it.

## GIVEN
CommonSetup: grk/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: HMW_108:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_237:1:0
WithP3SpaceArena: SOR_237:1:0
WithP4SpaceArena: SOR_237:1:0
WithP3Base: SOR_024
WithP4Base: SOR_024

## WHEN
- P1>AttackGroundArena:0:P2B
- P1>AnswerDecision:Vehicle

## EXPECT
SEATCOUNT:4
P2SPACEARENAUNIT:0:NOTTRAIT:Vehicle
P3SPACEARENAUNIT:0:NOTTRAIT:Vehicle
P4SPACEARENAUNIT:0:NOTTRAIT:Vehicle
P1SPACEARENAUNIT:0:HASTRAIT:Vehicle

---

# TeamSuns_TheTeammateKeepsTheTrait
#// USER CASE 4, second half — and the one a Twin Suns implementation gets wrong for free. In a 2v2 the
#// naming player's TEAMMATE is not an enemy, so seat 3 (P1's partner under the fixed 1+3 / 2+4 seating)
#// keeps Vehicle while seats 2 and 4 lose it. SWUTeamOf is what draws that line; it collapses to the
#// seat itself outside a team game, so two-player play is byte-identical.

## GIVEN
CommonSetup: grk/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: HMW_108:1:0
WithP2SpaceArena: SOR_237:1:0
WithP3SpaceArena: SOR_237:1:0
WithP4SpaceArena: SOR_237:1:0
WithP3Base: SOR_024
WithP4Base: SOR_024

## WHEN
- P1>AttackGroundArena:0:P2B
- P1>AnswerDecision:Vehicle

## EXPECT
SEATCOUNT:4
P3SPACEARENAUNIT:0:HASTRAIT:Vehicle
P2SPACEARENAUNIT:0:NOTTRAIT:Vehicle
P4SPACEARENAUNIT:0:NOTTRAIT:Vehicle

---

# SurvivesTheFirstLegionLeavingPlay
#// The duration is "for this PHASE", not "while this unit is in play" — contrast SEC_046 Galen, whose
#// naming lasts only while Galen is on the table. Defeating The First Legion after it names does NOT
#// give the trait back.
#// It attacks into a body big enough to kill it on the counter (SOR_046 is 3/7: it takes 5 and lives,
#// and deals 3 back into a 5/5 that is already... no — pre-damaged to 2 remaining so the counter kills).

## GIVEN
CommonSetup: grk/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_108:1:3
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Vehicle

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2SPACEARENAUNIT:0:NOTTRAIT:Vehicle

---

# ExpiresAtTheEndOfThePhase
#// "for this phase" must END. The round is passed out and the enemy Vehicle has its trait back.
#// ⚠ Under P1OnlyActions the opponent holds the claimed initiative and leads the new round, so the
#// chain needs a trailing P2>Pass; both players need a seeded deck or the regroup draw damages a base.

## GIVEN
CommonSetup: grk/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_108:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Vehicle
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass

## EXPECT
P2SPACEARENAUNIT:0:HASTRAIT:Vehicle

---

# UnrecognisedTraitNamed_NothingHappens
#// The answer is validated against the real trait universe (SWUAllTraits, derived from the card
#// dictionaries rather than hand-listed). A string that is not a printed trait arms nothing — it must
#// not stamp a flag that later matches everything, or nothing.

## GIVEN
CommonSetup: grk/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_108:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NotARealTrait

## EXPECT
P2SPACEARENAUNIT:0:HASTRAIT:Vehicle
P2SPACEARENAUNIT:0:HASTRAIT:Rebel
P1NODECISION

---

# RequestBoundary_TheNamedTraitSurvives
#// The trait is named in one request and read in later ones. It rides a GlobalEffect (a serialised
#// gamestate zone), never an in-memory global.

## GIVEN
CommonSetup: grk/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_108:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Vehicle
- P1>SimulateRequestBoundary

## EXPECT
P2SPACEARENAUNIT:0:NOTTRAIT:Vehicle
