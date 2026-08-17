# HealIfFriendlyDefeated
#// LAW_109 Tantive IV (5/8, Restore 2) — When Played: if a friendly unit was defeated this phase, heal 4
#// from your base. P1's SOR_128 (3/1) attacks into SOR_046 and dies (friendly defeated), then Tantive
#// heals 4 from the base (4 -> 0).

## GIVEN
CommonSetup: bbw/bgw/{myResources:7;myBaseDamage:4}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_109

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P1SPACEARENAUNIT:0:CARDID:LAW_109

---

# NoHealIfNoFriendlyDefeated
#// LAW_109 Tantive IV — When Played heals 4 ONLY if a friendly unit was defeated this phase. With no
#// combat and nothing defeated, playing Tantive heals nothing; base damage stays at 4.

## GIVEN
CommonSetup: bbw/bgw/{myResources:7;myBaseDamage:4}
P1OnlyActions: true
WithP1Hand: LAW_109

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:4
P1SPACEARENAUNIT:0:CARDID:LAW_109

---

# FriendlyDefeatedThisPhaseSurvivesTheRequestBoundary
#// LAW_109 Tantive IV — request-boundary guard for the phase-scoped "a friendly unit was defeated this
#// phase" state. In a real game every answer starts a fresh process, so that flag has to live in the
#// serialized gamestate. None of Tantive's own flows contain a decision, so LAW_130 Betrayed Trust is
#// played in between purely as a decision carrier (two enemy units = a genuine pending choose): P1's
#// SOR_128 (3/1) trades into SOR_046 and dies (flag set), Betrayed Trust is played, the game round-trips
#// through serialization with its target pick still open, the pick is answered, and only THEN is Tantive
#// played. The heal must still fire (base 4 -> 0), which it can only do if the flag survived the
#// round-trip.

## GIVEN
CommonSetup: bbw/bgw/{myResources:9;myBaseDamage:4}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]
WithP1Hand: [LAW_130 LAW_109]

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P1SPACEARENAUNIT:0:CARDID:LAW_109

---

# NoHealIfOnlyEnemyDefeated
#// LAW_109 Tantive IV — a defeated ENEMY unit does not satisfy "a friendly unit was defeated this phase".
#// Friendly SOR_164 Wampa (4/5) attacks enemy SOR_128 (3/1): the enemy dies, Wampa survives. Playing
#// Tantive then heals nothing; base damage stays at 4.

## GIVEN
CommonSetup: bbw/bgw/{myResources:7;myBaseDamage:4}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: LAW_109

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1BASEDMG:4
P1SPACEARENAUNIT:0:CARDID:LAW_109

---

# FriendlyIsByControl_ForeignOwnedDeathCounts
#// LAW_109 — control axis for "if a FRIENDLY unit was defeated this phase" and "heal 4 damage from
#// YOUR base". Both words resolve from the ability's controller. P1's ground arena holds SOR_128
#// (3/1) OWNED BY P2 but CONTROLLED BY P1 (the end state after a control-take). It attacks SOR_046
#// (3/7) and dies — for P1 that is a FRIENDLY unit defeated, so Tantive's When Played heals P1's base
#// from 4 to 0.
#// The same board pins the owner side of the split: the dead unit goes to its OWNER's discard, so
#// P2's discard holds the body (P2DISCARDCOUNT:1) while P1's discard stays EMPTY. A "friendly" test
#// keyed on ownership would see only a card leaving for P2's pile and heal nothing.

## GIVEN
CommonSetup: bbw/bgw/{myResources:7;myBaseDamage:4}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_128:2
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_109

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:0
P1SPACEARENAUNIT:0:CARDID:LAW_109

---

# FriendlyIsByControl_OwnedButEnemyControlledDeathDoesNotCount
#// LAW_109 — the mirror of FriendlyIsByControl_ForeignOwnedDeathCounts, and the section that makes
#// the pair discriminating. The only unit defeated this phase is SOR_128, sitting in P2's arena and
#// CONTROLLED BY P2 but OWNED BY P1. It is an ENEMY unit dying, so "a friendly unit was defeated this
#// phase" stays false and Tantive heals nothing — base damage stays at 4.
#// The tell that ownership is the wrong key: because P1 owns the dead unit, P1's OWN discard pile
#// grows to 1 (the body returns to its owner) while P2's discard stays empty. An implementation that
#// asked "did a card I own die?" would heal here. P1's SOR_046 (3/7) survives the trade, so no other
#// defeat can confound the read.
#//
#// COVERAGE: offer=N/A (no target picker — the heal is fixed on "your base" and the amount is fixed
#//           at 4) · decline=N/A (both the condition check and the heal are mandatory; there is no
#//           "you may") · control=FriendlyIsByControl_ForeignOwnedDeathCounts +
#//           FriendlyIsByControl_OwnedButEnemyControlledDeathDoesNotCount ("friendly" resolved by
#//           controller in both directions, with the body routed to its OWNER's discard either way) ·
#//           reqboundary=FriendlyDefeatedThisPhaseSurvivesTheRequestBoundary · boundary=
#//           HealIfFriendlyDefeated vs NoHealIfNoFriendlyDefeated (nothing died) vs
#//           NoHealIfOnlyEnemyDefeated (the wrong side died).

## GIVEN
CommonSetup: bbw/bgw/{myResources:7;myBaseDamage:4}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArenaControlled: SOR_128:1
WithP1Hand: LAW_109

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1BASEDMG:4
P1DISCARDCOUNT:1
P2DISCARDCOUNT:0
P1SPACEARENAUNIT:0:CARDID:LAW_109
