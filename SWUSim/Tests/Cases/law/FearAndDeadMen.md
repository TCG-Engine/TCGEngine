# DealsFourToEachEnemyGround
#// LAW_179 Fear and Dead Men (Aggression,Villainy event, cost 7) — cost reduction (1 less per card
#// discarded from hand this phase) handled by the play-cost modifier; effect: "Deal 4 damage to each
#// enemy ground unit." SOR_046 (3/7) survives at DAMAGE:4; SOR_095 (3/3) dies.

## GIVEN
CommonSetup: rrk/bgw/{myResources:7}
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_179

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1

---

# ForcedHandDiscount_CountsOpponentInducedDiscards
#// LAW_179 "costs 1 less per card discarded from your hand this phase" must count FORCED discards too —
#// e.g. an opponent's Pillage (SHD_181) making you discard. P2 Pillages P1: P1 discards 2 of 3 cards
#// (keeping LAW_179), so LAW_179 costs 7-2=5. P1 has exactly 5 resources → it is playable ONLY because the
#// two forced discards counted (regression: SWUDiscardCards previously never set SWU_DISCARDED_HAND).

## GIVEN
CommonSetup: rrk/brk/{theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 5
WithP2Resources: 8
WithP1Hand: LAW_179
WithP1Hand: SOR_095
WithP1Hand: SOR_063
WithP2Hand: SHD_181
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:myHand-2
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:DAMAGE:4
