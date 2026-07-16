# CaptureTokenDefeatsInstead
#// SEC_195 Arrest — "Your base captures an enemy non-leader unit." Tokens can't be captured: a token that
#// would be captured is defeated and removed from play instead (never stored as a base captive, so it is
#// NOT returned to its owner at regroup). P1's base "captures" P2's SEC_T01 Spy → the Spy is defeated to
#// P2's discard, and P2's arena is empty with no base-captive to rescue.
## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP2GroundArena: SEC_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1

---

# BaseCaptive_RescuedAtRegroup
#// SEC_195 Arrest — the base captive is rescued by its owner at the start of the regroup phase.
#// P1 captures P2's SOR_095 (it leaves play), then both players pass to reach the regroup phase. At
#// RegroupPhaseStart, SOR_095 returns to P2's control (in its arena). Net: P2 has SOR_095 back.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095

---

# BaseCapturesEnemyUnit
#// SEC_195 Arrest (Event, cost 2, Cunning/Villainy)
#//   "Your base captures an enemy non-leader unit. At the start of the regroup phase, its owner rescues it."
#// This test: the capture. P1 plays Arrest and captures P2's SOR_095 — it leaves play (removed; stored on
#// P1's base via a GlobalEffects flag since bases have no Subcards). P2's arena is now empty.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
