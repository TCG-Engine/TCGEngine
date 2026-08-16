# BounceUnit
#// LAW_241 The Blade Wing (Cunning, cost 6, space) — When Played: you may return a non-leader unit to
#// its owner's hand. Return the enemy SEC_080.

## GIVEN
CommonSetup: yyk/bgw/{myResources:6}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_241

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# ReturnFriendlyUnit
#// LAW_241 The Blade Wing — When Played "you may return a non-leader unit to its owner's hand". The target
#// set includes friendly units and itself; here it returns a FRIENDLY unit (SOR_035) to P1's hand.

## GIVEN
CommonSetup: yyk/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_035:1:0
WithP1Hand: LAW_241

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1HANDCOUNT:1

---

# DeclineReturn
#// LAW_241 The Blade Wing — the return is optional ("you may"). Decline (PASS): no unit is returned, the
#// friendly SOR_035 stays and The Blade Wing still enters play in space.

## GIVEN
CommonSetup: yyk/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_035:1:0
WithP1Hand: LAW_241

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:1
P1HANDCOUNT:0

---

# ReturnOpponentControlledUnitToOwner
#// LAW_241 The Blade Wing — When Played: "return a non-leader unit to its OWNER's hand." P1 controls an
#// enemy-owned Wampa (SOR_164, owned by P2 — the end state after a Change of Heart, seated directly). P1
#// plays The Blade Wing and returns the controlled Wampa; it goes to P2's hand (its owner), not P1's.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_164:2
WithP1Hand: LAW_241

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P2HANDCOUNT:1
P2HANDCARD:0:SOR_164

---

# OfferPool_NonLeaderSpansEverythingButLeaderUnits
#// LAW_241 The Blade Wing — offer assertion for "return a NON-LEADER unit to its owner's hand". "Non-leader"
#// is the single printed restriction, so the pool must be every other body in play: the friendly ground
#// unit, the friendly space unit (The Blade Wing ITSELF at mySpaceArena-0 — the text does not say
#// "another"), the enemy ground unit and the enemy space unit are all in. The deployed enemy Luke leader
#// unit at theirGroundArena-1 is the lone exclusion, and it is the only reason a board needs a deployed
#// leader at all: without one, "non-leader" filters nothing and the assertion would be vacuous.
#// COVERAGE: offer=OfferPool_NonLeaderSpansEverythingButLeaderUnits (pending SELECTABLEEXACT: both sides,
#//           both arenas and the source itself in, deployed enemy leader unit out) · decline=DeclineReturn
#//           (PASS on the "you may") · control=ReturnOpponentControlledUnitToOwner (a P1-controlled,
#//           P2-owned unit returns to its OWNER's hand) · boundary pair=BounceUnit / ReturnFriendlyUnit
#//           (a unit is returned) vs DeclineReturn (nothing is returned, P1HANDCOUNT:0) ·
#//           reqboundary=not encoded (the play and the return answer are separate requests in production;
#//           no serialize round-trip section exists yet)

## GIVEN
CommonSetup: yyk/bgw/{myResources:6; theirLeader:SOR_005:1:1:1}
P1OnlyActions: true
WithP1GroundArena: SOR_035:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_241

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_241
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0
