#// JTL_235 Commandeer — "At the start of the next regroup phase, return that unit to its owner's hand."
#//
#// BUG REPORT, game 1157606 (3-seat Twin Suns): seat 3 commandeered seat 1's Jade Squadron Patrol
#// (SEC_049, a 6-cost Vehicle) and at the start of the regroup phase it never went back to seat 1's hand.
#// The live gamestate at report time had the unit still in SEAT 3's space arena (Owner=1 Controller=3)
#// with its return marker SWU_JTL235_RETURN_14 still sitting, unconsumed, in SEAT 3's global effects.
#//
#// ROOT CAUSE — the TWO-SEAT HARDCODE family. The regroup consumer in GameLogic.php scans every seat's
#// arenas for a marked unit (`for ($jp = 1; $jp <= SeatCountForGame(); $jp++)`) but then asks for the
#// marker on seats 1 and 2 ONLY:
#//     GlobalEffectCount(1, 'SWU_JTL235_RETURN_'.$juid) > 0 || GlobalEffectCount(2, ...) > 0
#// The marker is written on the CASTER's seat (AddGlobalEffects($player, ...) in cards/jtl/Commandeer.php),
#// so a caster in seat 3 or 4 is never found and the unit is never returned. The HMW_200 Rish Loo block
#// directly below it — the same PERM per-UID marker shape — loops `$hs <= SeatCountForGame()` correctly.
#//
#// ⚠ WHY A 2-SEAT SECTION CANNOT CATCH THIS, and why both arities are here: with seats 1 and 2 the
#// hardcode is accidentally right. The 2-seat section below is the CONTROL — it passes before and after
#// the fix, and it is what proves the 3-seat section is measuring the seat lookup and not the mechanism.
#//
#// ⚠ WHAT IS DELIBERATELY NOT COVERED: the marker CLEAR (the all-seats loop beside the lookup). No
#// gameplay assertion can observe it. SWUBounceUnit returns the card with AddHand(CardID:), which builds a
#// NEW object, so the commandeered instance's UniqueID dies as it leaves the arena and a leftover marker
#// can never match anything again. A section asserting "the replayed copy is not bounced again" was
#// written, and DROPPED: it stayed green under every single-site mutation (seats 1-2 clear, CardID keying,
#// no clear at all), so it was decorative. The clear is hygiene against marker accumulation, not behaviour.
#//
#// Official ruling (card-specific-rulings.md): "Commandeer only returns the unit to hand if it's in play."
#// Both sections keep the unit in play, so the return is unconditional here.

# ThreeSeat_CommandeeredUnitReturnsToItsOWNERSHandAtRegroup
#// THE REPORTED BUG. Seat 3 takes seat 1's Vehicle, then every seat passes to reach the regroup.
#// The unit must leave seat 3's arena and arrive in SEAT 1's hand — its owner's, not the caster's.
## GIVEN
CommonSetup3P: grw/ggk/ygk
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP1SpaceArena: SEC_049:1:0
WithP3Hand: JTL_235
WithP3Resources: 9:SOR_095
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P3>PlayHand:0
- P3>AnswerDecision:p1SpaceArena-0
- P1>Pass
- P2>Pass
- P3>Pass
- P1>AnswerDecision:-
- P2>AnswerDecision:-
- P3>AnswerDecision:-
## EXPECT
P1HANDCARD:0:SEC_049
P3SPACEARENACOUNT:0
P1SPACEARENACOUNT:0

---

# ThreeSeat_TheUnitIsUNDERTHECASTERSCONTROLBeforeTheRegroup
#// The CONTRAST that pins WHEN the return happens. Same line, stopped before the passes: the unit is in
#// seat 3's space arena and NOT in seat 1's hand. Without this, a fix that bounced the unit immediately
#// on play would satisfy the section above and break the card.
## GIVEN
CommonSetup3P: grw/ggk/ygk
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP1SpaceArena: SEC_049:1:0
WithP3Hand: JTL_235
WithP3Resources: 9:SOR_095
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P3>PlayHand:0
- P3>AnswerDecision:p1SpaceArena-0
## EXPECT
P3SPACEARENACOUNT:1
P1SPACEARENACOUNT:0
P1HANDCOUNT:0

---

# TwoSeat_CommandeeredUnitReturnsAtRegroup_CONTROL
#// The 2-seat case, which has always worked: it is the control for the seat lookup. If this ever goes red
#// the mechanism itself broke, not the seat handling.
## GIVEN
CommonSetup: grw/ygk
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP1SpaceArena: SEC_049:1:0
WithP2Hand: JTL_235
WithP2Resources: 9:SOR_095
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Pass
- P2>Pass
- P1>AnswerDecision:-
- P2>AnswerDecision:-
## EXPECT
P1HANDCARD:0:SEC_049
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:0
