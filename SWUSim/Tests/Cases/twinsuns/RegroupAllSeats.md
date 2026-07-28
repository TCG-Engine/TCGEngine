# TwinSuns_RegroupDrawsForEverySeat
#// Twin Suns (3 seats): the regroup DRAW step must draw 2 for EVERY live seat, not just seats 1-2. All three
#// players pass to end the action phase; regroup runs and seat 3 draws its 2 cards. (Regression: DrawPhase
#// hardcoded DoDrawCard(1)/DoDrawCard(2), so seats 3+ never drew — "regroup only fires for 2 players".)
## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Deck: SOR_095
WithP3Deck: SOR_095
WithP3Deck: SOR_095
## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
## EXPECT
SEATCOUNT:3
P3HANDCOUNT:2

---

# TwinSuns_RegroupResourcesForEverySeat
#// Twin Suns (3 seats): the regroup RESOURCE step must offer a resource prompt to EVERY live seat. After the
#// action phase ends, seat 3 has a pending "resource up to 1 card" decision. (Regression: ResourcePhase only
#// queued for firstPlayer/secondPlayer, so seats 3+ were never offered a resource.)
## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Deck: SOR_095
WithP3Deck: SOR_095
WithP3Deck: SOR_095
## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
## EXPECT
SEATCOUNT:3
P3HASDECISION
P3DECISIONTOOLTIP:Resource_up_to_1_card

---

# TwinSuns_RegroupStartTriggerFiresForSeat3
#// Twin Suns (3 seats): the regroup-start card triggers in RegroupPhaseStart must sweep EVERY seat. A seat-3
#// JTL_198 Fireball ("When the regroup phase starts: deal 1 damage to this unit") takes its 1 damage.
#// (Regression: those per-seat sweeps were hardcoded 1..2, so a seat-3 unit's regroup trigger never fired.)
## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Deck: SOR_095
WithP3Deck: SOR_095
WithP3GroundArena: JTL_198
## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
## EXPECT
SEATCOUNT:3
P3GROUNDARENAUNIT:0:CARDID:JTL_198
P3GROUNDARENAUNIT:0:DAMAGE:1
