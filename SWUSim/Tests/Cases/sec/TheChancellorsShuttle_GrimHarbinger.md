# WhenDefeated_PalpatineExp
#// SEC_027 The Chancellor's Shuttle (Ground, 1/3) — Restore 1 + When Defeated: if you control Chancellor
#//   Palpatine (leader or unit), you may give an Experience token to a unit. SEC_082 (Palpatine unit) is
#//   in play; SEC_027 attacks LAW_124 and dies → give an Experience token to SEC_082.

## GIVEN
CommonSetup: bbk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_082:1:0
WithP1GroundArena: SEC_027:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_082
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# WhenDefeated_NoPalpatine_NoTrigger
#// SEC_027 The Chancellor's Shuttle — the When Defeated Experience grant should NOT trigger when you do
#//   not control Chancellor Palpatine. Here P1 controls no Palpatine; the shuttle attacks LAW_124 and dies,
#//   and no Experience token is granted (the friendly Battlefield Marine stays un-upgraded).

## GIVEN
CommonSetup: bbk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_027:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENACOUNT:1
P1NODECISION

---

# WhenDefeated_UnderEnemyControl_ChecksTHEIRPalpatine
#// SEC_027 The Chancellor's Shuttle — "if YOU control Chancellor Palpatine" is read from whoever controls
#// the Shuttle when it dies. P2 plays JTL_043 No Glory, Only Results on it while P2 (not P1) controls a
#// Palpatine unit: the condition is satisfied for P2, so P2 hands the Experience token to their own unit.

## GIVEN
CommonSetup: bbk/bbk
WithActivePlayer: 2
WithP2Resources: 6
WithP1GroundArena: SEC_027:1:0
WithP2GroundArena: SEC_082:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SEC_082
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
