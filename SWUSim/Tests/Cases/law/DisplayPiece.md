# DefeatThenResource
#// LAW_103 Display Piece (Vigilance,Villainy event, cost 4) — "Defeat an enemy non-leader unit. Its
#// controller resources it from its owner's discard pile." Defeat P2's SEC_080 (single target ->
#// auto-resolves); P2 resources it (exhausted). P2 started with 0 resources.

## GIVEN
CommonSetup: brk/rrk/{myResources:4;theirResources:0}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_103

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2RESCOUNT:1
P2RESAVAILABLE:0
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1

---

# ChooseAcrossArenas
#// LAW_103 Display Piece — the target is any enemy non-leader unit, in either arena. With SOR_095 in the
#// ground arena and SEC_213 A-Wing in the space arena, P1 picks the space A-Wing; it is defeated and P2
#// resources it from discard (net +1 resource). The ground unit is untouched.

## GIVEN
CommonSetup: brk/rrk/{myResources:4;theirResources:0}
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: LAW_103

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P2RESCOUNT:1

---

# CantBeDefeatedByAbility
#// LAW_103 Display Piece — SHD_187 Lurking TIE Phantom "can't be captured, damaged, or defeated by enemy
#// card abilities". Display Piece (an enemy card ability) targets it, but the defeat is prevented: the
#// Phantom stays in play and is NOT resourced.

## GIVEN
CommonSetup: brk/rrk/{myResources:4;theirResources:0}
WithP2SpaceArena: SHD_187:1:0
WithP1Hand: LAW_103

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2RESCOUNT:0
P1DISCARDCOUNT:1

---

# ResourcedOnlyOnce_Superlaser
#// LAW_103 Display Piece — SOR_083 Superlaser Technician's own When Defeated ("put this unit into play as a
#// resource and ready it") and Display Piece's resource-from-discard both target the same card, but it can
#// only be resourced once: P2 ends with exactly one extra resource.

## GIVEN
CommonSetup: brk/rrk/{myResources:4;theirResources:0}
WithP2GroundArena: SOR_083:1:0
WithP1Hand: LAW_103

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2RESCOUNT:1

---

# WhenDefeatedFiresAfterResource
#// LAW_103 Display Piece — defeating LAW_142 Scarif Lieutenant triggers its When Defeated ("give an
#// Experience token to a friendly Rebel unit"): P2 resources the Scarif from discard AND its controller's
#// SOR_095 Battlefield Marine (Rebel) receives the Experience token.

## GIVEN
CommonSetup: brk/rrk/{myResources:4;theirResources:0}
WithP2GroundArena: [SOR_095:1:0 LAW_142:1:0]
WithP1Hand: LAW_103

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P2>Drain

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2RESCOUNT:1

---

# PilotedUnit_PilotToDiscard_UnitResourced
#// LAW_103 Display Piece — defeating a unit that carries a Pilot upgrade sends the PILOT to its owner's
#// discard (upgrades are trashed on the host's defeat) while the UNIT itself is resourced by its
#// controller. P2's A-Wing (SEC_213) carries JTL_196 Dagger Squadron Pilot as a Piloting upgrade; Display
#// Piece defeats the A-Wing: JTL_196 lands in P2's discard, the A-Wing becomes P2's 1 resource.
#// COVERAGE: offer=ChooseAcrossArenas (both-arena pool answered; single-target sections auto-resolve) ·
#//           reqboundary=WhenDefeatedFiresAfterResource (the resource step and the cross-player When
#//           Defeated resolve across the reaction drain) · control=OPEN — candidate engine bug: with a
#//           STOLEN unit (controller != owner) the "controller resources it from the owner's discard"
#//           step no-ops (the discard token resolves against the controller's seat); assert once fixed ·
#//           boundary=CantBeDefeatedByAbility + ResourcedOnlyOnce_Superlaser (no-defeat-no-resource and
#//           resource-exactly-once edges) · decline=N/A (both the defeat and the resource are mandatory)

## GIVEN
CommonSetup: brk/rrk/{myResources:4;theirResources:0}
WithP2SpaceArena: SEC_213:1:0
WithP2SpaceArenaUpgrade: 0:JTL_196
WithP1Hand: LAW_103

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2RESCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:JTL_196
P1DISCARDCOUNT:1

---

# StolenUnit_ControllerResourcesItFromOwnersDiscard
#// Owner ≠ controller: P2 controls SEC_213 OWNED by P1. Display Piece defeats it into owner P1's
#// discard, and its CONTROLLER (P2) resources it from there — P2 gains the resource (exhausted,
#// Owner stays P1), P1's discard keeps only the event... nothing else. Intended: the cross-seat
#// lookup must resolve the owner's discard from the controller's frame.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: LAW_103
WithP2SpaceArenaControlled: SEC_213:1

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2RESCOUNT:1
P1DISCARDCOUNT:1
