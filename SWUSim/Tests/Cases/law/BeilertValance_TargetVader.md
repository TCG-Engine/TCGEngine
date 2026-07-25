# OnAttackDrawDeal
#// LAW_051 Beilert Valance (3/6) — On Attack: draw a card; you may deal damage to a ground unit equal to
#// the number of cards you've drawn this phase. Attacks the base; draws 1 (1 drawn this phase) -> deal 1
#// to the enemy SOR_046.

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
