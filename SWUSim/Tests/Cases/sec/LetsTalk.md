# EachFriendlyCapturesSameArena
#// SEC_131 Let's Talk (Event, Command, cost 9) — each friendly unit captures an enemy non-leader unit in
#//   the same arena. SOR_095 (ground) captures the lone enemy SOR_046 (ground).

## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_131

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# TwoFriendliesEachCaptureGround
#// SEC_131 Let's Talk — "Each friendly unit captures an enemy non-leader unit in the same arena." Two
#//   friendly ground units each capture one of the two enemy ground units. The first captor is offered
#//   both enemies (choice); once one is taken the second captor auto-resolves onto the remaining enemy.

## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_037:1:0
WithP1Hand: SEC_131

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION

---

# FewerUnitsThanOpponent
#// SEC_131 Let's Talk — with fewer friendly units than the opponent, only as many captures happen as
#//   there are friendly units. P1's lone SOR_095 captures one of two enemy ground units (its choice);
#//   the other enemy remains.

## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_037:1:0
WithP1Hand: SEC_131

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# CrossArena_CapturesInOwnArena
#// SEC_131 Let's Talk — "in the same arena": a friendly ground unit captures an enemy ground unit and a
#//   friendly space unit captures an enemy space unit. Each captor has a single legal target in its own
#//   arena, so both auto-resolve with no prompt.

## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_066:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: SEC_131

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
