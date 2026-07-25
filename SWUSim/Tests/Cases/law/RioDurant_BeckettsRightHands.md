# BounceReplayShielded
#// LAW_093 Rio Durant (2/5) — When Played: you may return a non-leader unit that costs 3 or less to its
#// owner's hand. Then its owner may play it for free; it gains Shielded for this phase. Return P1's own
#// SEC_080 (cost 2), replay it free with Shielded.

## GIVEN
CommonSetup: byk/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_093

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:HASKEYWORD:Shielded

---

# PassAbility
#// LAW_093 Rio Durant — the return is optional ("you may"). With SEC_080 available to return, P1 instead
#// passes the ability: nothing is returned and the board is unchanged (just Rio and SEC_080 in play).

## GIVEN
CommonSetup: byk/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_093

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:1:CARDID:LAW_093
P1HANDCOUNT:0

---

# ReturnOwn_DeclineReplay
#// LAW_093 Rio Durant — after returning a friendly unit to hand, the "play it for free" step is also
#// optional. P1 returns SEC_080 to hand, then declines the replay: SEC_080 stays in hand and only Rio
#// remains in the arena.

## GIVEN
CommonSetup: byk/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_093

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_093
P1HANDCOUNT:1

---

# ReturnEnemy_OwnerReplaysFreeShielded
#// LAW_093 Rio Durant — the return targets ANY non-leader unit costing 3 or less, including an enemy unit,
#// and it is that unit's OWNER who may replay it for free with Shielded. P1 returns P2's SEC_213 A-Wing
#// (cost 1); P2 replays it for free (no resources spent) and it gains a Shield.

## GIVEN
CommonSetup: byk/bgw/{myResources:4;theirResources:0}
P1OnlyActions: true
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: LAW_093

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SEC_213
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2RESAVAILABLE:0

---

# ReturnEnemy_OwnerDeclinesReplay
#// LAW_093 Rio Durant — the enemy owner may decline the free replay. P1 returns P2's SEC_213 A-Wing to
#// P2's hand; P2 declines, so the A-Wing stays in P2's hand and the space arena is empty.

## GIVEN
CommonSetup: byk/bgw/{myResources:4;theirResources:0}
P1OnlyActions: true
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: LAW_093

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:NO

## EXPECT
P2SPACEARENACOUNT:0
P2HANDCOUNT:1
