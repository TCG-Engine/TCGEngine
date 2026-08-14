# OnAttackDrawDeal
#// LAW_051 Beilert Valance (3/6) — On Attack: draw a card; you may deal damage to a ground unit equal to
#// the number of cards you've drawn this phase. Attacks the base; draws 1 (1 drawn this phase) -> deal 1
#// to the enemy SOR_046.
#// COVERAGE: offer=OnAttackOfferIsGroundUnitsBothSides (pending SELECTABLEEXACT: ground units on both
#//           sides incl. Valance, space units excluded) · decline=OnAttackDeclineDeal ·
#//           boundary pair=OnAttackEmptyDeckHitsOwnBase (0 drawn — no damage, own base takes 3) +
#//           OnAttackDrawDeal (1) + OnAttackCountsDrawsFromEarlierInstances (4) ·
#//           control=N/A (seat-level drawn-this-phase counter, no persistent per-unit marker) ·
#//           reqboundary=OnAttackCountsDrawsFromEarlierInstances (the event draw and the attack draw
#//           happen on separate requests; the phase counter survives and accumulates)

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_051:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1
P1HANDCOUNT:1

---

# OnAttackDeclineDeal
#// LAW_051 Beilert Valance — the deal-damage is a "you may", so it can be declined. Attacks the base, still
#// draws 1 card, but passes the damage: the enemy SOR_046 takes nothing.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_051:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:1

---

# OnAttackEmptyDeckHitsOwnBase
#// LAW_051 Beilert Valance — drawing from an EMPTY deck deals 3 damage to your own base and draws no card,
#// so 0 cards were drawn this phase and the enemy ground unit takes 0. Attacks the base.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_051:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3
P1HANDCOUNT:0

---

# OnAttackCountsDrawsFromEarlierInstances
#// LAW_051 Beilert Valance — "the number of cards you've drawn this phase" accumulates across SEPARATE
#// draw instances, not just the attack's own draw. P1 first plays TWI_175 Strategic Analysis (draw 3),
#// then attacks with Valance (draw 1): 4 cards drawn this phase, so the optional damage deals 4 to the
#// enemy SOR_046 (3/7 — survives at 4 damage).

## GIVEN
CommonSetup: brk/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_051:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: TWI_175
WithP1Deck: [SOR_237 SOR_237 SOR_237 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P1HANDCOUNT:4

---

# OnAttackOfferIsGroundUnitsBothSides
#// LAW_051 Beilert Valance — the optional damage targets GROUND units only, on either side: the offer is
#// exactly Valance himself + the enemy ground unit, and neither player's SPACE unit is a candidate. The
#// decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_051:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:1
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
