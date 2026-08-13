# NoVehicle_Fizzle
#// SHD_194 Triple Dark Raid — no Vehicle in the top 7 → clean fizzle (nothing played, no decision left).
#// The search still peeks (private) and returns the cards to the bottom, but with no match the player picks
#// nothing and the space arena stays empty.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_194
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046 LAW_180 SOR_063 SOR_207]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P1NODECISION

---

# PlaysVehicleReadyFree
#// SHD_194 Triple Dark Raid (Event, cost 3, Cunning/Villainy)
#//   "Search the top 7 cards of your deck for a Vehicle and play it. It costs 5 resources less and enters
#//    play ready. Return it to its owner's hand at the end of the phase."
#// Top 7 has exactly one Vehicle (SOR_237 Alliance X-Wing, cost 2). P1 (5 resources) plays SHD_194 (cost 3),
#// then free-plays the X-Wing (cost 2 - 5 = 0). Two resources remain (only SHD_194's 3 spent — the X-Wing
#// added nothing), and it enters the space arena READY, proving cost-reduction + enters-ready + free nested play.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_194
WithP1Deck: [SOR_237 SOR_095 SEC_080 SOR_128 SOR_046 LAW_180 SOR_063]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_237

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:READY
P1RESAVAILABLE:2

---

# ReturnsToHandAtRegroup
#// SHD_194 Triple Dark Raid — the played Vehicle returns to its owner's hand at the end of the phase.
#// P1 plays SHD_194 and free-plays the X-Wing, then both players pass to reach the regroup phase. At
#// RegroupPhaseStart the SWU_SHD194_RETURN sweep bounces the X-Wing back to P1's hand: the space arena
#// empties and the X-Wing does NOT go to discard (only the SHD_194 event is there → DISCARDCOUNT 1), which
#// distinguishes a bounce-to-hand from a defeat. (HANDCOUNT is left unasserted — the regroup draw pollutes it.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
WithActivePlayer: 1
WithP1Hand: SHD_194
WithP1Deck: [SOR_237 SOR_095 SEC_080 SOR_128 SOR_046 LAW_180 SOR_063]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_237
- P2>Pass
- P1>Pass

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1

---

# TakeNothing_VehicleAvailableButDeclined
#// The decline branch with a LEGAL target present — distinct from NoVehicle_Fizzle, where there is
#// nothing to take at all. Nothing is played, the peeked cards return to the deck rather than being
#// milled, and the event still costs its 3.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_194
WithP1Deck: [SOR_237 SOR_095 SEC_080 SOR_128 SOR_046 SOR_063 SOR_207]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:0
P1DECKCOUNT:7
P1RESAVAILABLE:2
P1DISCARDCOUNT:1

---

# CanPlayAVehicleUPGRADE
#// "search … for a VEHICLE" names a TRAIT, not a card type — so a Vehicle UPGRADE qualifies just as much
#// as a Vehicle unit. TWI_236 Grievous's Wheel Bike is a cost-4 Upgrade with the Vehicle trait
#// ("Attach to a non-Vehicle unit. Attached unit gains Overwhelm"), found and attached for free (4−5 → 0).
#// Every other section here uses a Vehicle UNIT, so nothing else notices if the upgrade half breaks —
#// and it WAS broken: the search offered the upgrade and the play then died on an affordability gate
#// that priced it at FULL cost, stranding it in hand.
#// SOR_095 Battlefield Marine is the non-Vehicle host it may attach to; +3/+3 taking it to 6/6 is what
#// proves it actually attached rather than merely leaving the deck.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_194
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [TWI_236 SOR_095 SEC_080 SOR_128 SOR_046 SOR_063 SOR_207]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:TWI_236

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:TWI_236
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
P1RESAVAILABLE:2

---

# TheVehicleUPGRADEAlsoReturnsToHand
#// "Return IT to its owner's hand at the end of the phase" applies to whatever was played — an upgrade no
#// less than a unit. The partner to ReturnsToHandAtRegroup (which uses a unit): at end of phase the Wheel
#// Bike leaves its host and goes to hand, and the host drops back to its printed 3/3.
#// This took FOUR engine links to work, each proven by instrumentation: the marker rides the
#// ATTACH_UPGRADE param (upgrade placement is async — the play-grant global is null by attach time);
#// the payment funnel's own dispatch case forwards that field (it exploded at limit 6 and silently
#// dropped it, even for a cost-0 attach); every attached upgrade now carries a UniqueID; and the
#// regroup return-loop scans SUBCARDS, not just arena units.
#// HANDCOUNT is 3: the bounced Wheel Bike + the two regroup draws (same reason the unit-version section
#// leaves it unasserted; here the count IS the point, so the draws are priced in).

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_194
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [TWI_236 SOR_095 SEC_080 SOR_128 SOR_046 SOR_063 SOR_207]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:TWI_236
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1HANDCOUNT:3

---

# DefeatedBeforeEndOfPhase_DoesNotReturnToHand
#// The return is a delayed effect on the specific OBJECT that was played: if it is already gone when the
#// phase ends, nothing comes back. The fetched X-Wing is defeated by LOF_264 It's Worse (aspectless, so
#// no penalty; the X-Wing is the only unit in play, so the mandatory defeat auto-resolves onto it).
#// Hand at regroup = the 2 regroup draws ONLY — an implementation returning "a copy of what was played"
#// rather than the surviving object would show 3 and fail here while passing the positive section.

## GIVEN
CommonSetup: yyk/rrk/{myResources:10}
P1OnlyActions: true
WithP1Hand: [SHD_194 LOF_264]
WithP1Deck: [SOR_237 SOR_095 SEC_080 SOR_128 SOR_046 SOR_063 SOR_207]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_237
- P1>PlayHand:0
- P1>Pass

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:2
P1DISCARDCOUNT:3

---

# StolenVehicle_StillReturnsToItsOWNERSHand
#// "Return it to ITS OWNER'S hand" — a control change does not shake the marker, and the return goes to
#// the owner, not the thief. P2 steals the fetched X-Wing with SOR_122 Traitorous (attaches to a
#// non-leader unit costing 3 or less and takes control — the X-Wing costs 2, and it is the only unit in
#// play so both the attach and the steal auto-resolve). At regroup the X-Wing leaves P2's arena and
#// lands in P1's hand: P1 has 3 cards (X-Wing + 2 draws), P2 has only its 2 draws.

## GIVEN
CommonSetup: yyk/ggk/{myResources:5;theirResources:5}
WithP1Hand: SHD_194
WithP2Hand: SOR_122
WithP1Deck: [SOR_237 SOR_095 SEC_080 SOR_128 SOR_046 SOR_063 SOR_207]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_237
- P2>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P1HANDCOUNT:3
P2HANDCOUNT:2

---

# WaylaidAndREPLAYED_DoesNotReturn
#// The marker belongs to the OBJECT the event played, not to the card name. P2 Waylays the fetched
#// X-Wing back to P1's hand (only unit in play — the mandatory return auto-resolves), P1 replays it as a
#// normal play (2 + 2 off-aspect = 4), and the fresh copy carries no marker: it SURVIVES the regroup
#// and stays in the arena. An implementation keying the return off the CardID would bounce it again.

## GIVEN
CommonSetup: yyk/yyk/{myResources:12;theirResources:5}
WithP1Hand: SHD_194
WithP2Hand: SOR_222
WithP1Deck: [SOR_237 SOR_095 SEC_080 SOR_128 SOR_046 SOR_063 SOR_207]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_237
- P2>PlayHand:0
- P1>PlayHand:0
- P2>Pass
- P1>Pass

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1HANDCOUNT:2

---

# BouncedVehicleWithAPilotLEADER_LeaderReturnsToBase
#// The composite the CR makes interesting: the fetched X-Wing gets JTL_001 Asajj Ventress DEPLOYED ONTO
#// IT AS A PILOT, then the end-of-phase return bounces the Vehicle. CR 9.3 defeats a bounced unit's
#// upgrades — but a pilot-LEADER is a leader, and a leader never goes to the discard: she returns to the
#// base zone undeployed. Discard = the event alone (Asajj NOT in it), leader NOTDEPLOYED, and the
#// X-Wing is in hand (3 = X-Wing + the two regroup draws).
#// Deploy is interactive (Deploy_as_Unit_or_Pilot? → Pilot; the fetched X-Wing is the only legal
#// Vehicle host, so the host pick auto-resolves).

## GIVEN
CommonSetup: yyk/rrk/{myResources:12;myLeader:JTL_001}
P1OnlyActions: true
WithP1Hand: SHD_194
WithP1Deck: [SOR_237 SOR_095 SEC_080 SOR_128 SOR_046 SOR_063 SOR_207]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_237
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>Pass

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:3
P1DISCARDCOUNT:1
P1LEADER:NOTDEPLOYED
