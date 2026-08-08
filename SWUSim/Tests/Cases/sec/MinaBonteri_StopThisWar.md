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

---

# WhenDefeated_ResolvesForTheNewControllerAfterTakeControl
#// SEC_094 Mina Bonteri — the When Defeated belongs to whoever CONTROLS her when she dies, not to her
#// owner. P2 plays JTL_043 No Glory, Only Results ("Take control of a non-leader unit, then defeat it")
#// on P1's Mina: P2 becomes the controller, the defeat follows immediately, and the disclose is P2's —
#// read against P2's hand and drawing from P2's DECK.
#// The owner-vs-controller split is what this section pins: P1 also holds a covering hand (SEC_096 +
#// SEC_080) and a stocked deck, and neither is touched — P1 draws nothing and is never prompted.
#// P2 discloses SEC_096 (Command/Heroism) + SEC_080 (Command/Villainy), covering CommandCommandHeroism.
#// P2's hand: 3 cards, spends NGOR (-> 2), keeps both revealed cards, draws 1 => 3. Deck 2 -> 1.
#// Mina still goes to her OWNER's discard (P1), per normal defeat handling.
#// A second P1 unit (SOR_046) is seated so NGOR's take-control choice is a genuine prompt — with Mina
#// as the only non-leader unit it would auto-resolve and swallow the answer meant for it.

## GIVEN
CommonSetup: ggw/bbk
WithActivePlayer: 2
WithP2Resources: 6
WithP1GroundArena: SEC_094:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SEC_096
WithP1Hand: SEC_080
WithP2Hand: JTL_043
WithP2Hand: SEC_096
WithP2Hand: SEC_080
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2HANDCOUNT:3
P2DECKCOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:2
P1NODECISION
P2NODECISION
