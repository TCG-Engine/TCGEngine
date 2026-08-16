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

---

# OfferPool_AnotherBountyHunterEitherSideReadyOrExhausted
#// LAW_061 Asajj Ventress — offer assertion for "ready ANOTHER Bounty Hunter unit". Every restriction word
#// has a violator on the board: Asajj herself is a Bounty Hunter (excluded by "another"; she lands at
#// myGroundArena-3), SOR_095 / SOR_046 are non-Bounty-Hunters on both sides (excluded by trait), and the
#// enemy LAW_124 IS offered because the text carries no "friendly". A READY friendly LAW_124
#// (myGroundArena-2) is also offered — "ready another Bounty Hunter unit" prints no exhausted-only
#// restriction, so a ready unit is a legal (if pointless) target. The decision is left pending so the pool
#// itself is the assertion.
#// COVERAGE: offer=OfferPool_AnotherBountyHunterEitherSideReadyOrExhausted (pending SELECTABLEEXACT: self
#//           excluded by "another", non-Bounty-Hunters excluded both sides, enemy Bounty Hunter included) ·
#//           decline=WhenPlayedDeclineReady · boundary pair=WhenPlayedReadyBountyHunter (readied) vs
#//           WhenPlayedDeclineReady (stays exhausted) · control=N/A (one-shot ready, no persistent
#//           per-unit marker to survive a control change) · reqboundary=not encoded (the play and the
#//           ready-answer are separate requests in production; no serialize round-trip section exists yet)

## GIVEN
CommonSetup: grw/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [LAW_124:0:0 SOR_095:0:0 LAW_124:1:0]
WithP2GroundArena: [LAW_124:0:0 SOR_046:0:0]
WithP1Hand: LAW_061

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:3:CARDID:LAW_061
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2&theirGroundArena-0
