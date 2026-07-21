# DiscardDeal4Draw
#// LOF_176 Lightsaber Throw — Discard a Lightsaber card from your hand; if you do, deal 4 damage to a ground
#// unit and draw a card. P1 discards SOR_053 (a Lightsaber), deals 4 to SOR_046 and draws SOR_059.

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_176,SOR_053}
P1OnlyActions: true
WithP1Deck: SOR_059
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1HANDCOUNT:1

---

# DamageFriendlyUnit
#// LOF_176 Lightsaber Throw — the 4 damage may target ANY ground unit, including a friendly one. With only a
#// friendly unit (SOR_046) in play, P1 discards the Lightsaber (SOR_053), deals 4 to its own unit, and draws
#// SOR_059. Ref: "deal 4 damage to a friendly unit".

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_176,SOR_053}
P1OnlyActions: true
WithP1Deck: SOR_059
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P1HANDCOUNT:1

---

# DeclineDiscard_NoEffect
#// LOF_176 Lightsaber Throw — discarding a Lightsaber is optional ("If you do..."). With two Lightsabers in
#// hand P1 is prompted which to discard, then declines. No card is discarded, no damage is dealt, and no card
#// is drawn: the two Lightsabers stay in hand (hand = 2) and the enemy unit is unharmed. Ref: #// "should allow choosing nothing".

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_176,SOR_053,SOR_053}
P1OnlyActions: true
WithP1Deck: SOR_059
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:2
P1DECKCOUNT:1

---

# NoLightsaberInHand_NoEffect
#// LOF_176 Lightsaber Throw — with no Lightsaber card in hand there is nothing to discard, so the "if you do"
#// chain does not fire: no damage and no draw. The non-Lightsaber card (SOR_095) stays in hand, the deck is
#// untouched, and the enemy unit is unharmed. Ref: "do nothing when nothing available in hand".

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_176,SOR_095}
P1OnlyActions: true
WithP1Deck: SOR_059
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DECKCOUNT:1
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoDamageTargets_StillDiscardAndDraw
#// LOF_176 Lightsaber Throw — even with no ground units to damage, discarding a Lightsaber still lets you draw.
#// P1 discards SOR_053 and draws SOR_059 (hand = 1); both LOF_176 and the Lightsaber are in the discard
#// (count 2). Ref: "discard and draw even if there are no damage targets".

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_176,SOR_053}
P1OnlyActions: true
WithP1Deck: SOR_059

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1DISCARDCOUNT:2
