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
