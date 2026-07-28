# OnAttackDefeatCreditsExp
#// LAW_032 Cad Bane (6/6, Shielded, Overwhelm) — On Attack: defeat any number of friendly Credit tokens;
#// give an Experience token to this unit for each. Defeat 2 Credits -> 2 Experience (6/6 -> 8/8).

## GIVEN
CommonSetup: brk/bgw/{myResources:0}
P1OnlyActions: true
WithP1Credits: 2
WithP1GroundArena: LAW_032:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_032
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:8
P1CREDITCOUNT:0

---

# OnAttackDefeatAllCreditsExp
#// LAW_032 Cad Bane — On Attack: defeat ALL 4 friendly Credits -> 4 Experience (6/6 -> 10/10);
#// attacks base at power 10 -> base takes 10.

## GIVEN
CommonSetup: brk/bgw/{myResources:0}
P1OnlyActions: true
WithP1Credits: 4
WithP1GroundArena: LAW_032:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myResources-0&myResources-1&myResources-2&myResources-3

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:4
P1GROUNDARENAUNIT:0:POWER:10
P1CREDITCOUNT:0
P2BASEDMG:10

---

# OnAttackDefeatSomeCreditsKeepRest
#// LAW_032 Cad Bane — On Attack: with 4 Credits, defeat only 2 -> 2 Experience (6/6 -> 8/8), 2 Credits
#// remain; attacks base at power 8.

## GIVEN
CommonSetup: brk/bgw/{myResources:0}
P1OnlyActions: true
WithP1Credits: 4
WithP1GroundArena: LAW_032:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:8
P1CREDITCOUNT:2
P2BASEDMG:8

---

# OnAttackDefeatNoCredits
#// LAW_032 Cad Bane — On Attack: decline to defeat any Credits -> no Experience (stays 6/6), all 4 Credits
#// remain; attacks base at power 6.

## GIVEN
CommonSetup: brk/bgw/{myResources:0}
P1OnlyActions: true
WithP1Credits: 4
WithP1GroundArena: LAW_032:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:6
P1CREDITCOUNT:4
P2BASEDMG:6

---

# OnAttackNoCreditsNoExp
#// LAW_032 Cad Bane — On Attack with no Credits: ability does nothing, no Experience (stays 6/6);
#// attacks base at power 6.

## GIVEN
CommonSetup: brk/bgw/{myResources:0}
P1OnlyActions: true
WithP1Credits: 0
WithP1GroundArena: LAW_032:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:6
P1CREDITCOUNT:0
P2BASEDMG:6
