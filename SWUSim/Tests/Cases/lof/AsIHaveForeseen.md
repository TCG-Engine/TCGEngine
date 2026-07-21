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

---

# PlayTopPilotAsUpgrade
#// LOF_188 As I Have Foreseen — "play the top card (−4)" can play a Piloting card AS AN UPGRADE. The top
#// card Iden/Astromech (JTL_057, a Vigilance pilot, Piloting cost 2) is played onto the friendly TIE
#// Advanced; with the −4 it costs 0 → attaches for free. Regression: top-deck plays skipped
#// SWUBeginPlayCard's Piloting branch, so a pilot only ever played as a unit. LOF_188 itself is on-aspect
#// here (yyk covers Cunning/Villainy), so it costs 1 → 10−1−0 = 9 resources left.
## GIVEN
CommonSetup: yyk/bbk/{myResources:10;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_188
WithP1SpaceArena: SOR_231:1:0
WithP1Deck: JTL_057
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Pilot
## EXPECT
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_057
P1RESAVAILABLE:9
P1NOFORCE

---

# PlayTopPilotAsUnit_StillDiscounted
#// The same top-deck pilot can instead be played as a UNIT; the −4 discount still applies to the unit cost
#// (JTL_057 unit cost 2 − 4 = 0), so 10−1−0 = 9 resources remain.
## GIVEN
CommonSetup: yyk/bbk/{myResources:10;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_188
WithP1SpaceArena: SOR_231:1:0
WithP1Deck: JTL_057
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Unit
## EXPECT
P1RESAVAILABLE:9

---

# LeaveOnTop_DeclineForce
#// LOF_188 As I Have Foreseen — the offer to use the Force is a "may". Even when the top card is affordable,
#// the player can DECLINE: the Force token is retained and the card stays on top of the deck (not played).
#// Top card SEC_080 (cost 3 → 0 after −4) is affordable, but P1 declines. Ref: "shows the top card of the
#// deck and allows the player to leave it on top".

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;handCardIds:LOF_188}
P1OnlyActions: true
WithP1Force: true
WithP1Deck: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:0
P1DECKCOUNT:1

---

# NoForce_ViewOnly
#// LOF_188 As I Have Foreseen — without a Force token the "you may use the Force to play it" clause cannot
#// resolve: P1 simply looks at the top card and it stays on top (no play, no Force to lose). Ref: "when the
#// player does not have the Force ... it shows the top card of the deck".

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;handCardIds:LOF_188}
P1OnlyActions: true
WithP1Deck: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1DISCARDCOUNT:1

---

# EmptyDeck_NoOp
#// LOF_188 As I Have Foreseen — with an EMPTY deck there is no top card to look at, so the event resolves
#// with no effect: the Force token is retained and the event goes to the discard. Ref: "when the deck is
#// empty ... it does nothing".

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;handCardIds:LOF_188}
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
