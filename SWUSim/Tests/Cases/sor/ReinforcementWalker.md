# OnAttack_Draw
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
