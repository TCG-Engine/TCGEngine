# TopIsAUnit_Drawn_ExperienceGiven_EventResourced
#// IC27_087 Issue a Command — Event, cost 4, [Command][Command], Plan.
#// Text: Reveal the top card of your deck. If it's a unit, draw it.
#//       Give an Experience token to a friendly unit.
#//       Resource this event.
#// COVERAGE: offer=Offer_FriendlyUnitsOnly (an enemy unit excluded) · decline=N/A — no "may" anywhere ·
#//           no target=NoFriendlyUnit_StillDrawsAndResources (three separate sentences: the others still resolve) ·
#//           negative=TopIsNotAUnit_LeftOnTop · empty deck=EmptyDeck_NoDrawNoDamage · order=this (the drawn unit, not the
#//           next card; the EVENT is resourced, not the new deck top) · reqboundary=RequestBoundary_BeforeTheExperience ·
#//           control=N/A — an event, no take-control interaction · modes=2P,TeamSuns ("a friendly unit") —
#//           TeamSuns_TeammatesUnitIsOffered · TwinSuns=N/A (no player reference).
#//
#// ⚠ PREVIEW SET — IC27 is absent from card-specific-rulings.md. Clause 1 copies HMW_148 Local Support's reveal-and-draw
#// (a non-unit stays on top); clause 3 copies HMW_151 Overgrowth's "Resource this card" (exhausted, CR 7.7).
#//
#// THE POSITIVE. Top card Battlefield Marine (a unit) is drawn — Waylay is the new top. The only friendly unit gets the
#// Experience. The event is resourced, exhausted: 4 resources + 1, none ready, nothing in the discard.
## GIVEN
CommonSetup: ggk/ggw/{myResources:4;myhandCardIds:IC27_087}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_222]
WithP1GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_222
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1RESCOUNT:5
P1RESAVAILABLE:0
P1DISCARDCOUNT:0

---

# TopIsNotAUnit_LeftOnTop
#// "If it's a unit": the top card is Waylay (an event) — revealed, not drawn, still on top.
## GIVEN
CommonSetup: ggk/ggw/{myResources:4;myhandCardIds:IC27_087}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_222 SOR_095]
WithP1GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_222
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESCOUNT:5

---

# EmptyDeck_NoDrawNoDamage
#// An empty deck: nothing is revealed or drawn, and — since nothing is DRAWN — no empty-deck damage. The other two
#// sentences still resolve.
## GIVEN
CommonSetup: ggk/ggw/{myResources:4;myhandCardIds:IC27_087}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:0
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESCOUNT:5

---

# Offer_FriendlyUnitsOnly
#// "a FRIENDLY unit": both of P1's units (ground and space) are offered, P2's unit is not.
## GIVEN
CommonSetup: ggk/ggw/{myResources:4;myhandCardIds:IC27_087}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_222
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# ChosenUnitGetsTheExperience
#// The pick lands: the space unit is chosen and gets it; the ground unit does not.
## GIVEN
CommonSetup: ggk/ggw/{myResources:4;myhandCardIds:IC27_087}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_222
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESCOUNT:5

---

# NoFriendlyUnit_StillDrawsAndResources
#// No friendly unit (only an enemy one): no Experience, no dangling decision — the draw and the resource still happen.
## GIVEN
CommonSetup: ggk/ggw/{myResources:4;myhandCardIds:IC27_087}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_222]
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESCOUNT:5
P1DISCARDCOUNT:0

---

# RequestBoundary_BeforeTheExperience
#// The Experience choice is answered in a later request; it still lands and the event is still in the resource zone.
## GIVEN
CommonSetup: ggk/ggw/{myResources:4;myhandCardIds:IC27_087}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_222
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESCOUNT:5
P1DISCARDCOUNT:0

---

# TeamSuns_TeammatesUnitIsOffered
#// TEAM SUNS: "friendly" is the team — P1's unit and teammate P3's are offered; the enemy seats' are not.
## GIVEN
CommonSetup: ggk/ggw/{myResources:4;myhandCardIds:IC27_087}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Deck: SOR_222
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0
