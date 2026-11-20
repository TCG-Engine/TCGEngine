# PlayAnotherRebelUnit_DealsOneToTheirBase
#// IC27_117 Hera Syndulla — Against the Odds (4/3 Ground, Aggression/Heroism, Rebel/Twi'lek/Spectre, unique)
#// "When you play another Rebel card: Deal 1 damage to a base."
#// A case in SWUCollectOwnPlayReactions (the SOR_143 Fighters for Freedom shape: "another" by UniqueID, the
#// trait read off the PLAYED card, any card type — a pilot played as an upgrade is a card too). No "may", so
#// the base pick is mandatory; "a base" is unqualified, so every base at the table is offered.
#// JUDGEMENT CALL (no ruling yet — IC27 is too new for the rulings list): "Rebel card" = any card type with the
#// Rebel trait, matching how Fighters for Freedom and Poe Dameron (IC27_115) read their "another X card".
#//
#// COVERAGE: offer=OfferIsEveryBase + TwinSuns_OffersEveryBaseAtTheTable · decline=N/A (no "you may") ·
#//           boundary=TwoRebelPlays_TwoDamage (1 play → 1, 2 plays → 2) ·
#//           control=OpponentPlaysARebelCard_NoTrigger ("you play") ·
#//           reqboundary=TwoRebelPlays_TwoDamage (a SimulateRequestBoundary between the two plays) ·
#//           modes=2P + Twin Suns ("a base" = any base at the table)
#//
#// Hera is on the board; P1 plays Battlefield Marine (Rebel; Command off-aspect here: 2 + 2) and hits P2's base.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_117:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:2
P2BASEDMG:1
P1BASEDMG:0
P1NODECISION

---

# OfferIsEveryBase
#// OFFER ASSERTED: "a base" with no qualifier — both bases, the controller's own included.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_117:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# MayHitOwnBase
#// The own-base half of the offer is real: choosing it damages P1's base, not P2's.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_117:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:1
P2BASEDMG:0

---

# NonRebelCard_NoTrigger
#// THE LOAD-BEARING NEGATIVE on "Rebel": Kaydel Connix is Resistance, not Rebel — no prompt, no damage.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_117:1:0
WithP1Hand: IC27_032

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P2BASEDMG:0
P1BASEDMG:0
P1NODECISION

---

# HerasOwnPlay_DoesNotCount
#// "ANOTHER Rebel card": Hera is a Rebel, but playing her does not trigger her own ability.

## GIVEN
CommonSetup: rrw/bbk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_117

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_117
P2BASEDMG:0
P1BASEDMG:0
P1NODECISION

---

# RebelEvent_Counts
#// Any card type: Medal Ceremony (SOR_245) is a Rebel EVENT (cost 0). Nothing attacked this phase, so the event
#// itself does nothing — and Hera still deals 1.

## GIVEN
CommonSetup: rrw/bbk/{myResources:0}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_117:1:0
WithP1Hand: SOR_245

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1HANDCOUNT:0
P2BASEDMG:1
P1NODECISION

---

# RebelPilot_PlayedAsAnUpgrade_Counts
#// A Rebel card need not be a unit play: Biggs Darklighter (JTL_150, Rebel Pilot) played as a PILOT onto Black
#// One (Piloting cost 1) is still "another Rebel card". With 1 resource the pilot play is the only affordable
#// one (the unit costs 3), so no unit-or-pilot question is asked; UPGRADECOUNT proves the pilot path.

## GIVEN
CommonSetup: rrw/bbk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_117:1:0
WithP1SpaceArena: IC27_116:1:0
WithP1Hand: JTL_150

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:1
P1NODECISION

---

# TwoRebelPlays_TwoDamage
#// QUANTITY + REQUEST BOUNDARY: each Rebel play is its own trigger. Two plays, with a request boundary between
#// them, deal 2 in total.

## GIVEN
CommonSetup: rrw/bbk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_117:1:0
WithP1Hand: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>SimulateRequestBoundary
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:3
P2BASEDMG:2
P1NODECISION

---

# OpponentPlaysARebelCard_NoTrigger
#// "When YOU play": P2 playing a Rebel card does nothing for P1's Hera.

## GIVEN
CommonSetup: rrw/bbw/{theirResources:4}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: IC27_117:1:0
WithP2Hand: SOR_095

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P1BASEDMG:0
P2BASEDMG:0

---

# Blanked_NoTrigger
#// A Hera that has lost all abilities (SOR_138 marker) does not react.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_117:1:0:SOR_138
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P2BASEDMG:0
P1NODECISION

---

# TwinSuns_OffersEveryBaseAtTheTable
#// "A base" spans every seat: at four seats (teams 1+3 vs 2+4) the teammate's and both opponents' bases are
#// offered alongside P1's own. The pick is left pending to read the pool.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_117:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:myBase-0&p2Base-0&p3Base-0&p4Base-0
