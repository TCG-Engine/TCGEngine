# BounceReplayShielded
#// LAW_093 Rio Durant (2/5) — When Played: you may return a non-leader unit that costs 3 or less to its
#// owner's hand. Then its owner may play it for free; it gains Shielded for this phase. Return P1's own
#// SEC_080 (cost 2), replay it free with Shielded.
#// COVERAGE: offer=OfferIsCost3OrLessBothSides (SELECTABLEEXACT across both arenas/sides; cost-4+ units
#//           and Rio himself excluded) · decline=PassAbility (skip the return) + ReturnOwn_DeclineReplay
#//           + ReturnEnemy_OwnerDeclinesReplay (skip the replay, each owner) ·
#//           control=OpponentControlledFriendlyOwned_ReturnsToOwner (P2-controlled, P1-OWNED unit goes to
#//           P1's hand and P1 replays it) · boundary pair=BounceReplayShielded vs ReturnOwn_DeclineReplay
#//           and ReturnEnemy_OwnerReplaysFreeShielded vs ReturnEnemy_OwnerDeclinesReplay ·
#//           request boundary=ReturnEnemy_* (P2 answers the replay offer on its own request after P1's
#//           play+target requests)

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

---

# OfferIsCost3OrLessBothSides
#// LAW_093 Rio Durant — the return offer spans BOTH players' non-leader units costing 3 or less, and
#// excludes anything costing 4+ (including Rio himself, cost 4). Board: P1 SEC_080 (cost 2, in) and
#// SOR_164 Wampa (cost 4, out); P2 ground SOR_164 Wampa (cost 4, out) and P2 space SEC_213 A-Wing
#// (cost 1, in). Exactly the two cheap units are offered; with two legal options the pick stays
#// pending.

## GIVEN
CommonSetup: byk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_164:1:0]
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: LAW_093

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirSpaceArena-0

---

# NativelyShieldedUnit_ReplaysWithOneShield
#// LAW_093 Rio Durant — returning a unit that already has printed Shielded and replaying it for free:
#// the unit's own Shielded and the granted "gains Shielded for this phase" are redundant instances of
#// the same keyword, so the replayed unit re-enters with exactly ONE Shield token. P1 returns its own
#// LAW_211 Black Sun Patroller (cost 2, Shielded) and replays it free.

## GIVEN
CommonSetup: byk/rrk/{myResources:4}
P1OnlyActions: true
WithP1SpaceArena: LAW_211:1:0
WithP1Hand: LAW_093

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_211
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:1

---

# OpponentControlledFriendlyOwned_ReturnsToOwner
#// LAW_093 Rio Durant — a unit the OPPONENT controls but P1 OWNS returns to its OWNER's (P1's) hand,
#// and it is P1 who may then play it for free with Shielded. P2 controls a P1-owned SEC_080 (cost 2);
#// P1 plays Rio, returns it, and replays it free: it ends in P1's arena with a Shield, P2's arena is
#// empty, and P1 spent only Rio's cost.

## GIVEN
CommonSetup: byk/rrk/{myResources:4}
P1OnlyActions: true
WithP2GroundArenaControlled: SEC_080:1
WithP1Hand: LAW_093

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1RESAVAILABLE:0
