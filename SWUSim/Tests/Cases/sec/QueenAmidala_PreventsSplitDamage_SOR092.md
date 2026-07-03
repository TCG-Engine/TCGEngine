# SEC_101 Queen Amidala — SPLIT/DIVIDED damage prevention (SOR_092 Overwhelming Barrage). P1 buffs its
# SEC_080 (3/3 → 5/5) and divides its 5 power among P2's units: 1 to Amidala (already at 2 damage, so
# lethal), 2 to each of two Spy tokens (SEC_T01, 0/2 — lethal). Divided damage is simultaneous (CR 34/35.5),
# so Amidala's "if damage would be dealt to this unit, you may defeat a trait-sharing friendly to prevent
# it" fires: P2 defeats a Spy (Official, shares a trait) to prevent the 1 that would have killed her. End
# state: BOTH Spies defeated (one as the prevent cost, one from its own 2 damage) but Amidala lives at 2.
## GIVEN
CommonSetup: ggk/ggw/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_101:1:2
WithP2GroundArena: SEC_T01:1:0
WithP2GroundArena: SEC_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:1,theirGroundArena-1:2,theirGroundArena-2:2
- P2>AnswerDecision:myGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:1
