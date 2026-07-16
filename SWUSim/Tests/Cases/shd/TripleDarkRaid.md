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
