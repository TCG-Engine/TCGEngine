# Unaffordable_NoForceOffer
#// LOF_188 As I Have Foreseen — "Look at the top card. You may use the Force. If you do, play that card
#// (4 less)." Using the Force is only worthwhile if the top card can then be played, so the offer must be
#// gated on affordability: if the player can't pay cost−4, don't offer to use the Force (it would be spent
#// for nothing). No decision should appear, and the Force token must be retained.
#//
#// LOF_188 costs 1 (Cunning/Villainy, covered by Thrawn/yellow base) → 0 ready after playing it. Top card
#// SOR_119 (cost 8) → 8 − 4 = 4 net (plus any penalty) > 0 → UNaffordable. (Companion:
#// AsIHaveForeseen188_UseForce_PlayTopDiscounted covers the affordable case, where the offer IS made.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;handCardIds:LOF_188}
P1OnlyActions: true
WithP1Force: true
WithP1Deck: SOR_119

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1HASFORCE

---

# UseForce_PlayTopDiscounted
#// LOF_188 As I Have Foreseen — "Look at the top card. You may use the Force. If you do, play that card.
#// It costs 4 resources less." The top card is SEC_080 (cost 3 → 0 after −4), so P1 uses the Force and
#// plays it for free.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;handCardIds:LOF_188}
P1OnlyActions: true
WithP1Force: true
WithP1Deck: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
