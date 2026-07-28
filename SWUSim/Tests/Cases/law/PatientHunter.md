# RegroupExpCantReady
#// LAW_073 Patient Hunter (3/3) — When the regroup phase starts: you may give an Experience token to a
#// non-leader unit; if you do, that unit can't ready during this regroup phase. Give it to the exhausted
#// SEC_080 -> it gains Experience AND stays exhausted through the next ready step.

## GIVEN
CommonSetup: gyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_073:1:0
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>Pass
- P1>AnswerDecision:myGroundArena-1
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# RegroupExpEnemyUnitCantReady
#// LAW_073 — the target may be an ENEMY non-leader unit. Give the Experience token to an exhausted enemy
#// Dark Trooper; it gains the token AND cannot ready this regroup, so it stays exhausted.

## GIVEN
CommonSetup: gyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_073:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>Pass
- P1>AnswerDecision:theirGroundArena-0
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# RegroupExpCanBePassed
#// LAW_073 — the ability is a "may"; declining gives no Experience and the exhausted ally readies
#// normally during the regroup phase.

## GIVEN
CommonSetup: gyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_073:1:0
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>Pass
- P1>AnswerDecision:PASS
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:READY
