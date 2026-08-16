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
