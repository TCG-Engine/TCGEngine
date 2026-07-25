# ForceBounceOwnerReplay
#// LOF_185 Baylan Skoll — Hidden + When Played: may use the Force → return a non-leader unit (cost ≤4)
#// to its owner's hand; then its owner may play it for free. P1 plays Baylan, uses the Force, bounces
#// P2's damaged SOR_188 (2 damage). P2 (the owner) replays it for free → a FRESH copy with 0 damage,
#// proving the cross-player bounce + free-replay chain.

## GIVEN
CommonSetup: bbk/rrk/{myResources:14;handCardIds:LOF_185}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_188:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:YES

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:CARDID:SOR_188
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# ForceBounceFriendly_ControllerReplaysFree
#// LOF_185 Baylan — the returned unit may be a FRIENDLY one; then its owner (the caster) may play it free.
#// P1 bounces its own damaged Battlefield Marine (2 dmg) and replays it fresh (0 dmg) for free. Only one
#// unit is a legal ≤4 non-leader return target, so the return auto-resolves (no target prompt): the
#// answers are just the Force YES and the free-replay YES.
## GIVEN
CommonSetup: bbk/rrk/{myResources:14;handCardIds:LOF_185}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:2
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# FriendlyReturn_DeclineFreeReplay
#// LOF_185 Baylan — the free replay is optional. P1 uses the Force (YES), bounces its own damaged
#// Battlefield Marine (auto-target: lone ≤4 non-leader), then the owner DECLINES the free replay (NO).
#// The Marine stays in P1's hand (not replayed); only Baylan remains on the ground; Force spent.
## GIVEN
CommonSetup: bbk/rrk/{myResources:14;handCardIds:LOF_185}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:2
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:NO
## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_185
P1HANDCOUNT:1

---

# DeclineForce_NoEffect
#// LOF_185 Baylan — "You MAY use the Force." With a Force token available, P1 DECLINES (NO). No unit is
#// returned, the enemy's damaged SOR_188 is untouched (still 2 damage), and the Force token is retained.
## GIVEN
CommonSetup: bbk/rrk/{myResources:14;handCardIds:LOF_185}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_188:1:2
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_188
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NoForce_NotTriggered
#// LOF_185 Baylan — the When Played ability is gated on having a Force token. With NO Force, the ability
#// never prompts at all: the enemy's damaged SOR_188 is untouched and nothing is returned.
## GIVEN
CommonSetup: bbk/rrk/{myResources:14;handCardIds:LOF_185}
P1OnlyActions: true
WithP2GroundArena: SOR_188:1:2
## WHEN
- P1>PlayHand:0
## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_188
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# UseForce_NoValidTargets_ForceStillSpent
#// LOF_185 Baylan — with a Force token but NO legal return target (Baylan itself costs 5; no other
#// non-leader unit ≤4 cost is in play), P1 still uses the Force (YES). The Force is spent even though the
#// return fizzles for lack of a target, and no target prompt appears.
## GIVEN
CommonSetup: bbk/rrk/{myResources:14;handCardIds:LOF_185}
P1OnlyActions: true
WithP1Force: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_185
