# PassAfterOne
#// SHD_109 Endless Legions — play one unit, then pass: the loop stops there (your clarification), leaving
#// the second unit-resource in play as a resource. One unit enters; resource count drops by exactly 1.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Hand: SHD_109
WithP1Resources: 1:SEC_080:0,1:SOR_128:0,14:SOR_251:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1RESCOUNT:15

---

# PassImmediately
#// SHD_109 Endless Legions — passing on the first offer plays nothing (loop ends immediately).
#// Same setup, but P1 declines the reveal: no units enter play, all resources remain.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Hand: SHD_109
WithP1Resources: 1:SEC_080:0,1:SOR_128:0,14:SOR_251:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:16
P1DISCARDCOUNT:1

---

# PlayedUnitFiresWhenPlayed
#// SHD_109 Endless Legions — a free-played unit-resource fires its own When Played (one at a time).
#// SEC_240 (Space, 3/5, "When Played: Deal 2 damage to this unit.") sits as a resource. Played via the loop,
#// it enters the space arena and ends at DAMAGE:2 (a single fire), proving the nested play resolves its
#// entry trigger exactly once.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Hand: SHD_109
WithP1Resources: 1:SEC_240:0,14:SOR_251:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_240
P1SPACEARENAUNIT:0:DAMAGE:2

---

# PlaysTwoUnitResources
#// SHD_109 Endless Legions (Event, cost 14, Command/Command)
#//   "Reveal any number of resources you control. Play each unit revealed this way for free (one at a time)."
#// P1 has 2 unit-resources (SEC_080, SOR_128, both exhausted) + 14 ready event-resources (SOR_251) to pay
#// the 14 cost (Command covered by the g base). Playing SHD_109 exhausts the 14; the reveal loop then offers
#// only the 2 UNIT resources. P1 plays both for free (one at a time) — both enter the ground arena, the
#// resource count drops by 2 (14 event-resources remain untouched), and the loop auto-ends (no units left).

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Hand: SHD_109
WithP1Resources: 1:SEC_080:0,1:SOR_128:0,14:SOR_251:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myResources-0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESCOUNT:14
P1DISCARDCOUNT:1

---

# EachUnitsWhenPlayedResolvesBeforeTheNextPlay
#// BUG REPORT #1110 (game 1438045). Official ruling (07/16/2024): "If playing multiple units, resolve all
#// abilities triggered while playing each unit before playing the next unit." The re-offer was queued at
#// the SAME block as the played unit's own decisions, so a When Played that needed a decision drained
#// BEHIND the next "reveal a resource" offer — in the reported game nine units were played before any of
#// their interactive When Playeds resolved, and by then the board had shifted under SEC_193 Thrawn's.
#// Here Thrawn is played first while P2 holds a unit: P2's capture choice must be the pending decision,
#// and P1 must NOT already be looking at the next offer.
## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Hand: SHD_109
WithP1Resources: 1:SEC_193:0,1:SEC_080:0,14:SOR_251:1
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_193
P2DECISIONTOOLTIP:Choose_a_unit_to_be_captured_by_Thrawn_(or_pass)
P1NODECISION

---

# EachUnitsWhenPlayedResolvesBeforeTheNextPlay_ThenTheLoopContinues
#// Continuation of the section above: P2 declines the capture, so Thrawn readies — and only THEN does P1
#// get the next offer and play the Imperial Dark Trooper.
## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Hand: SHD_109
WithP1Resources: 1:SEC_193:0,1:SEC_080:0,14:SOR_251:1
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
- P2>AnswerDecision:-
- P1>AnswerDecision:myResources-0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_193
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENACOUNT:1
P1NODECISION
P2NODECISION

---

# Thrawn_NoEnemyNonLeaderUnit_Readies
#// The reported expectation, on its own: SEC_193 Thrawn played through Endless Legions while the only
#// enemy unit is a LEADER unit — "an opponent may choose a non-leader unit they control" has nothing to
#// choose, so "if they don't, ready this unit" applies.
## GIVEN
CommonSetup: ggk/rrk/{theirLeader:SOR_017:1:1}
P1OnlyActions: true
WithP1Hand: SHD_109
WithP1Resources: 1:SEC_193:0,14:SOR_251:1
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_017
P1GROUNDARENAUNIT:0:CARDID:SEC_193
P1GROUNDARENAUNIT:0:READY
P1NODECISION
