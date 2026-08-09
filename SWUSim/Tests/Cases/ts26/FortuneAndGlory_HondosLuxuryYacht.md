# WhenPlayedCapturesNonLeader
#// TS26_27 Fortune and Glory (Unit 3/5 space, cost 4) — When Played: this unit captures a non-leader unit.
#// The only non-leader unit besides itself is the enemy SEC_080, which is captured (removed from the board).
## GIVEN
CommonSetup: gyk/rrk/{myResources:4;handCardIds:TS26_27}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:TS26_27

---

# WhenPlayedCanCaptureAFRIENDLYNonLeader
#// TS26_27 Fortune and Glory — "captures a non-leader unit" names no side. With P1's own SEC_080 as the
#// only non-leader other than the Yacht itself, it is the capture target and P1's ground arena empties.

## GIVEN
CommonSetup: gyk/rrk/{myResources:4;handCardIds:TS26_27}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:TS26_27

---

# BountyCollected_AFriendlyUnitOfTHEIRSCapturesANonLeader
#// TS26_27 Fortune and Glory — "Bounty: a friendly unit captures a non-leader unit", where "friendly" is
#// relative to the COLLECTOR. P2 defeats the Yacht with Rival's Fall and takes the bounty: their SOR_095
#// becomes the captor and P1's SEC_080 is the captive, so P1's ground empties while P2 keeps its unit.

## GIVEN
CommonSetup: gyk/bbk/{myResources:4;theirResources:8}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: TS26_27:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Hand: SHD_079
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1SPACEARENACOUNT:0

---

# BountyIsOptional_DecliningCapturesNothing
#// TS26_27 Fortune and Glory — the Bounty offer can be refused. P2 defeats the Yacht and answers NO: both
#// boards are left exactly as they were apart from the Yacht itself, and no decision is left pending.

## GIVEN
CommonSetup: gyk/bbk/{myResources:4;theirResources:8}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: TS26_27:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Hand: SHD_079
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1SPACEARENACOUNT:0
P2NODECISION

---

# BountyCanBeAbandonedAFTERPickingTheCaptor
#// TS26_27 Fortune and Glory — the Bounty is escapable at the second step too, not only at the "collect?"
#// prompt. P2 accepts, names SOR_095 as the captor, then declines the capture: P1's SEC_080 stays on the
#// board and both of P2's units remain.
#// Pairs with BountyIsOptional_DecliningCapturesNothing, which bails out one step earlier.

## GIVEN
CommonSetup: gyk/bbk/{myResources:4;theirResources:8}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: TS26_27:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP2Hand: SHD_079
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:2
P1SPACEARENACOUNT:0

---

# BountyAfterAControlChange_GoesBackToTheORIGINALOwner
#// TS26_27 Fortune and Glory — Bounty reads "when this unit is defeated or captured, YOUR OPPONENT
#// collects its bounty", and "your" is the unit's controller at that moment. P2 plays No Glory, Only
#// Results (JTL_043) to take the Yacht and defeat it, which makes P2 the controller — so P2's opponent,
#// i.e. P1 who originally owned it, is the one who collects. P1's SEC_080 captures P2's SOR_095, emptying
#// P2's ground arena while P1 keeps its unit.
#// ⚠ This looks like the OPPOSITE of the Sith Traditions / Mother Talzin control-change sections, and it
#// is not: all three read from the current controller. Bounty just points one step further, at that
#// controller's opponent — so stealing a Bounty unit to kill it hands the reward straight back.

## GIVEN
CommonSetup: gyk/bbk/{theirResources:8}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: TS26_27:1:0
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Hand: JTL_043
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
