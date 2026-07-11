# SHD_194 Triple Dark Raid — the played Vehicle returns to its owner's hand at the end of the phase.
# P1 plays SHD_194 and free-plays the X-Wing, then both players pass to reach the regroup phase. At
# RegroupPhaseStart the SWU_SHD194_RETURN sweep bounces the X-Wing back to P1's hand: the space arena
# empties and the X-Wing does NOT go to discard (only the SHD_194 event is there → DISCARDCOUNT 1), which
# distinguishes a bounce-to-hand from a defeat. (HANDCOUNT is left unasserted — the regroup draw pollutes it.)

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
