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
