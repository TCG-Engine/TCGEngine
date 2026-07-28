# WhenPlayedReadyBountyHunter
#// LAW_061 Asajj Ventress (3/3) — When Played: you may ready another Bounty Hunter unit. Ready the
#// exhausted LAW_124 (Bounty Hunter).

## GIVEN
CommonSetup: grw/bgw/{myResources:5}
WithP1GroundArena: LAW_124:0:0
WithP1Hand: LAW_061

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:READY

---

# WhenPlayedDeclineReady
#// LAW_061 Asajj Ventress (3/3) — the When Played ready is a "you may", so it can be declined. Decline it;
#// the exhausted friendly LAW_124 stays exhausted.

## GIVEN
CommonSetup: grw/bgw/{myResources:5}
WithP1GroundArena: LAW_124:0:0
WithP1Hand: LAW_061

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# WhenPlayedReadyEnemyBountyHunter
#// LAW_061 Asajj Ventress — "ready another Bounty Hunter unit" has no "friendly" qualifier, so an ENEMY
#// exhausted Bounty Hunter (LAW_124) is a legal target and gets readied.

## GIVEN
CommonSetup: grw/bgw/{myResources:5}
WithP2GroundArena: LAW_124:0:0
WithP1Hand: LAW_061

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:READY

---

# WhenPlayedReadyFriendlyBountyHunterLeader
#// LAW_061 Asajj Ventress — a friendly DEPLOYED Bounty Hunter leader (LAW_007 Boba Fett) is also a valid
#// ready target (deployed leaders live in the arena). The exhausted deployed Boba is readied.

## GIVEN
CommonSetup: grw/bgw/{
  myLeader:LAW_007:1:1:1;
  myResources:5
}
SkipPreGame: true
WithP1Hand: LAW_061

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:READY
