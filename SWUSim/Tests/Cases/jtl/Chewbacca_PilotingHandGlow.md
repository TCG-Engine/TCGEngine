# Glow_AffordableOnlyAsAPILOT_StillHighlights
#// Bug report #995: "piloting cards are not showing as playable in hand highlighted for their optional
#// cost. Example: 5-cost Chewy was not showing as playable for piloting cost with 3 open resources."
#//
#// ROOT CAUSE: CanAffordActivationReserve prices ONLY the unit cost. It already knows about cost
#// halving (JTL_105), Exploit, the HMW_125 reduction and Credits/Droids — but nothing about Piloting,
#// which is a genuine ALTERNATE play cost, not a discount on the unit cost.
#//
#// JTL_103 Chewbacca is unit cost 5 / Piloting cost 3, so with 3 resources the unit play is out of
#// reach while the pilot play is affordable. The glow said no; the play said yes — the same
#// glow-vs-gate drift as the Credits bug, at a different site.
#//
#// The fix asks the SAME helper the play path asks — SWUGetPilotValidTargets, which prices through
#// SWUComputePilotCost (the one chokepoint shared by affordability and the charge) and filters to legal
#// hosts — so the highlight and the offer cannot disagree.
#//
#// Leia (Command/Heroism) on a Command base covers both of Chewbacca's pips, so there is no aspect
#// penalty muddying the numbers: unit 5, piloting 3.
#// SOR_237 Alliance X-Wing is a friendly Vehicle with no Pilot, i.e. a legal host.

## GIVEN
CommonSetup: ggw/bbw/{myResources:3}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [SOR_237:1:0]
WithP1Hand: [JTL_103]

## EXPECT
P1HANDGLOW:0

---

# Glow_NoLegalVehicleToPilot_StaysDark
#// THE NEGATIVE THAT KEEPS THE FIX HONEST. Affordability alone must not light the card: with 3
#// resources and NO Vehicle to attach to, neither play is available — the unit costs 5 and there is
#// nowhere to pilot — so Chewbacca must stay dark.
#// Without this, "always glow a Piloting card you could afford as a pilot" would pass the section above
#// while lighting up cards that cannot be played at all.

## GIVEN
CommonSetup: ggw/bbw/{myResources:3}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Hand: [JTL_103]

## EXPECT
P1HANDGLOWNOT:0

---

# Glow_UnaffordableEvenAsAPilot_StaysDark
#// The lower boundary. A legal host is present, but 2 resources cannot pay even the Piloting cost of 3,
#// so the card stays dark. Paired with the first section this pins the threshold at exactly 3 rather
#// than at "any Piloting card with a host".

## GIVEN
CommonSetup: ggw/bbw/{myResources:2}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [SOR_237:1:0]
WithP1Hand: [JTL_103]

## EXPECT
P1HANDGLOWNOT:0

---

# Glow_AffordableAsAUNIT_HighlightsWithNoVehicleAtAll
#// The control for the path that already worked: at 5 resources the ordinary unit play is affordable,
#// so the card glows whether or not any Vehicle is around. This is what proves the sections above fail
#// for the RIGHT reason — the fixture card really is playable and really does light up — and it guards
#// the unit-cost path against being lost in the rewrite.

## GIVEN
CommonSetup: ggw/bbw/{myResources:5}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Hand: [JTL_103]

## EXPECT
P1HANDGLOW:0

---

# Glow_CreditsCountTowardThePilotingCost
#// Live report (game 3608): 2 ready resources + 1 Credit against Chewbacca's Piloting cost of 3.
#// Total payment capacity is 3 — Credits pay play costs (CR 3.13) — so the pilot play IS affordable,
#// but SWUGetPilotValidTargets counted only REAL ready resources (2), returned no legal hosts, and the
#// card stayed dark.
#// ⚠ That helper does not merely drive the highlight: it builds the OFFER, i.e. which Vehicles you may
#// attach to. So the same miscount also removed the Unit/Pilot choice entirely and priced Chewbacca as
#// a 5-cost UNIT — which is how the player ended up in the Credit picker for a cost they could never
#// meet (see core/AltPaymentNotOfferedWhenUnaffordable.md).

## GIVEN
CommonSetup: rgw/bbw
WithP1Resources: 2:SOR_095:1
WithP1Credits: 1
WithP1SpaceArena: [SOR_237:1:0]
WithP1Hand: [JTL_103]
WithActivePlayer: 1
WithInitiativePlayer: 1

## EXPECT
P1HANDGLOW:0

---

# Glow_CreditsDoNotConjureAffordability_OneShortIsStillDark
#// The boundary partner. One Credit and ONE ready resource is a capacity of 2 against a Piloting cost
#// of 3 — still short — so the card must stay dark. Without this, "count Credits too" could be
#// implemented as "always affordable when any Credit is held" and pass the section above.

## GIVEN
CommonSetup: rgw/bbw
WithP1Resources: 1:SOR_095:1
WithP1Credits: 1
WithP1SpaceArena: [SOR_237:1:0]
WithP1Hand: [JTL_103]
WithActivePlayer: 1
WithInitiativePlayer: 1

## EXPECT
P1HANDGLOWNOT:0

---

# Play_TwoResourcesPlusACredit_ACTUALLYPaysThePilotingCost
#// ★ THE ACCEPTANCE TEST — the reporter's own words: "I can use my 2R + 1C to pay for the 3R piloting
#// cost of Chewbacca." Every section above is about the HIGHLIGHT; this one is about the play really
#// completing, which is the part that mattered.
#//
#// The full chain now: the unit cost of 5 is out of reach, the Piloting cost of 3 is not, so the play
#// takes the pilot-only branch, auto-picks the lone legal Vehicle, and offers the Credit toward the 3.
#// Defeating it leaves 2 resources to exhaust — capacity exactly met.
#//
#// Before the fix this same click priced Chewbacca as a 5-cost UNIT, offered a Credit payment that
#// could never reach it, and DESTROYED the Credit on confirm (core/AltPaymentNotOfferedWhenUnaffordable
#// .md guards that half).

## GIVEN
CommonSetup: rgw/bbw
WithP1Resources: 2:SOR_095:1
WithP1Credits: 1
WithP1SpaceArena: [SOR_237:1:0]
WithP1Hand: [JTL_103]
WithActivePlayer: 1
WithInitiativePlayer: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1HANDCOUNT:0
P1CREDITCOUNT:0
P1RESAVAILABLE:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_103
P1NODECISION
