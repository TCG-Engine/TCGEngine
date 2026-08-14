# OnAttack_Draw
#// COVERAGE: offer=N/A (the look-at-top is a fixed Draw/Discard option pair on one hidden card, no
#//           target pool) · decline=N/A (the mode choice is mandatory once a top card exists; the
#//           empty-deck skip is EmptyDeck_WhenPlayed_NoPrompt + EmptyDeck_OnAttack_NoPrompt)
#//           · boundary=WhenPlayed_DiscardHeal + OnAttack_DiscardHeal (heal 5→2 with FROM:DECK
#//           provenance) + AmbushPlay_TriggersTwice (both trigger sources on one play)
#//           · control=N/A (own-deck, own-base effect; no control-change interaction)
#//           · reqboundary=AmbushPlay_TriggersTwice (trigger-order pick, mode pick, Ambush YESNO and
#//           second mode pick resolve across separate serialized answers)
#// SOR_119 Reinforcement Walker — On Attack: the same look-at-top ability fires when the Walker
#// attacks (dual When Played/On Attack trigger). The Walker (already in play, ready) attacks P2's
#// base; the On Attack trigger resolves first (choose Draw → draw top SOR_095, deck 3 → 2, hand 1),
#// then combat deals the Walker's 6 power to P2's base.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_119:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Draw

## EXPECT
P2BASEDMG:6
P1HANDCOUNT:1
P1DECKCOUNT:2
P1DISCARDCOUNT:0

---

# WhenPlayed_DiscardHeal
#// SOR_119 Reinforcement Walker — When Played: look at the top card; choosing "Discard and heal 3"
#// discards the top card (to discard, From DECK) and heals 3 damage from P1's base. P1's base starts
#// at 5 damage → heals to 2. Top card SOR_095 is milled (deck 3 → 2, discard 0 → 1). Nothing drawn.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: SOR_119
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Discard

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1DISCARDUNIT:0:FROM:DECK
P1BASEDMG:2

---

# WhenPlayed_Draw
#// SOR_119 Reinforcement Walker (Unit 6/9, cost 8, Command, Vehicle/Walker) — When Played:
#// look at the top card; choosing "Draw" draws it. P1 plays the Walker (matched Command aspects,
#// 8 resources → printed cost 8), then via the option picker chooses Draw. Top card (SOR_095) is
#// drawn (hand 0 → 1), deck 3 → 2, nothing discarded.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_119
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Draw

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:2
P1DISCARDCOUNT:0

---

# OnAttack_DiscardHeal
#// SOR_119 Reinforcement Walker — the Discard-and-heal-3 mode also works on the ATTACK trigger.
#// Base at 5 damage; the Walker attacks the base, the look-at-top resolves first: Discard mills
#// SOR_095 (to discard, FROM DECK) and heals 3 (5 → 2). Combat then deals 6 to P2's base.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: SOR_119:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Discard

## EXPECT
P2BASEDMG:6
P1BASEDMG:2
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1DISCARDUNIT:0:FROM:DECK

---

# EmptyDeck_WhenPlayed_NoPrompt
#// SOR_119 Reinforcement Walker — with an EMPTY deck there is no top card to look at: the When
#// Played ability skips entirely (no Draw/Discard prompt), the Walker still enters play, and the
#// action passes cleanly.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_119

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:0
P1NODECISION

---

# EmptyDeck_OnAttack_NoPrompt
#// SOR_119 Reinforcement Walker — the On Attack half also skips on an empty deck: no prompt, the
#// attack still deals the Walker's 6 to P2's base.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_119:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:0
P1NODECISION

---

# AmbushPlay_TriggersTwice
#// SOR_119 Reinforcement Walker — played while SOR_079 Admiral Piett grants Ambush (cost ≥6):
#// the look-at-top fires for the PLAY, and then AGAIN for the Ambush attack (On Attack). The two
#// entry triggers raise a Choose_trigger_to_resolve pick (EffectStack-0 = the When Played): P1
#// resolves the When Played first (Draw SOR_237), accepts Ambush, the Walker attacks the lone
#// enemy marine (auto-target, 6 kills it, takes 3 back), and the On Attack look-at-top draws SOR_128.

## GIVEN
CommonSetup: ggk/ggk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_079:1:0
WithP1Hand: SOR_119
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_237 SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:Draw
- P1>AnswerDecision:YES
- P1>AnswerDecision:Draw

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_119
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:1:EXHAUSTED
