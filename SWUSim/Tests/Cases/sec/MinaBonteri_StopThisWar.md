# DeclineDisclose_NoDraw
#// SEC_094 Mina Bonteri — decline the When Defeated disclose → no draw.
#// Same defeat as the positive test; P1 declines (AnswerDecision:-) so the deck/hand are unchanged
#// (hand stays at the 2 fodder cards, deck at 2).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_094:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SEC_096
WithP1Hand: SEC_080
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:2
P2NODECISION

---

# WhenDefeated_Disclose_Draws
#// SEC_094 Mina Bonteri (Ground, 2/4, Command/Heroism) — Restore 1 (auto) + When Defeated: you may
#//   disclose CommandCommandHeroism → draw a card.
#// Mina (2/4) attacks LAW_124 (4/7): simultaneous damage defeats Mina (takes 4, has 4 HP) while
#// LAW_124 survives (takes 2). Mina's When Defeated discloses SEC_096 (Command,Heroism) + SEC_080
#// (Command,Villainy) → covers CommandCommandHeroism → draw 1.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_094:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SEC_096
WithP1Hand: SEC_080
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1HANDCOUNT:3
P1DECKCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P2NODECISION

---

# WhenDefeated_CannotSatisfyAspects_Skipped
#// SEC_094 Mina Bonteri — the disclose ability is skipped entirely when the cards in hand cannot cover
#//   the required CommandCommandHeroism aspects. Mina's hand holds only two Cartel Spacers (SOR_178,
#//   Cunning/Villainy) — no Command and no Heroism — so on defeat there is no prompt and no draw.
#// Mina (2/4) attacks LAW_124 (4/7): simultaneous damage defeats Mina while LAW_124 survives.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_094:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_178
WithP1Hand: SOR_178
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:2
P2NODECISION

---

# WhenDefeated_DiscloseExtraAspectsAlongsideTheRequiredOnes
#// SEC_094 Mina Bonteri — a disclose only has to REPRESENT the required aspects; revealing extra icons
#// alongside them is legal. P1 reveals SEC_096 (Command/Heroism), SEC_080 (Command/Villainy) and a
#// third card carrying only unrelated aspects: CommandCommandHeroism is still covered, so the draw
#// happens. Hand: 3 revealed cards stay in hand, +1 drawn = 4.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_094:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SEC_096
WithP1Hand: SEC_080
WithP1Hand: SOR_164
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:4
P1DECKCOUNT:1
