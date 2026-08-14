# RegroupExpCantReady
#// LAW_073 Patient Hunter (3/3) — When the regroup phase starts: you may give an Experience token to a
#// non-leader unit; if you do, that unit can't ready during this regroup phase. Give it to the exhausted
#// SEC_080 -> it gains Experience AND stays exhausted through the next ready step.
#// COVERAGE: offer=friendly and enemy branches of the pool are exercised (RegroupExpCantReady own unit,
#//           RegroupExpEnemyUnitCantReady enemy unit; the interplay section's probe showed all four
#//           non-leader units offered); no pending SELECTABLE section · reqboundary=every section (the
#//           can't-ready mark is written at regroup start and read at the ready step, across the
#//           resource-step requests) · control=N/A (no control-change interaction; the mark rides the
#//           unit object, not a seat) · boundary=RegroupExpCantReady (marked unit stays exhausted) vs
#//           RegroupExpCanBePassed (unmarked unit readies) · decline=RegroupExpCanBePassed

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

---

# RegroupInterplay_BountyPlayMidRegroup_TargetStillCantReady
#// LAW_073 Patient Hunter alongside OTHER regroup-start triggers and a mid-regroup free play. P2's
#// pre-damaged JTL_198 Fireball (3/3, 2 dmg, Bounty Hunter's Quarry SHD_123 attached) pings itself when
#// the regroup phase starts and dies; P1 collects the bounty and plays SHD_210 Cloud-Rider (3/1, Ambush)
#// for free, Ambush-attacking the exhausted SOR_046 that Patient Hunter just marked with an Experience
#// token. Cloud-Rider trades into SOR_046's 3-power counter (1 HP). End of regroup: SOR_046 has the
#// token, 3 damage, and is STILL exhausted, while the untouched SEC_080 (and P1's Patient Hunter, the
#// friendly control) readied normally. Flow: exp target -> bounty YES -> search pick -> Ambush YES ->
#// Ambush target -> both resource passes.

## GIVEN
CommonSetup: gyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_073:1:0
WithP2GroundArena: [SOR_046:0:0 SEC_080:0:0]
WithP2SpaceArena: JTL_198:1:2
WithP2SpaceArenaUpgrade: 0:SHD_123
WithP1Deck: [SHD_210 SOR_171 SOR_171]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SHD_210
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P2SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_073
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:READY
