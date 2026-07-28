# OnAttackMillAggressionDeal
#// LAW_173 BT-1 (2/4) — On Attack: discard a card from your deck. If it's Aggression, you may deal 1 to
#// a ground unit. Mills SOR_128 (Aggression) -> deal 1 to the enemy SOR_046.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_173:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:1

---

# OnAttackMillNonAggressionNoDamage
#// LAW_173 BT-1 — On Attack still discards a card from the deck, but if it is NOT Aggression there is no
#// deal-1 option. Mills SOR_237 (Heroism) -> the card is discarded but no unit takes damage.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_173:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1

---

# OnAttackEmptyDeckNoPrompt
#// LAW_173 BT-1 — with an empty deck there is nothing to discard, so no deal-1 prompt and no damage; the
#// attack still lands on the enemy base.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_173:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:0
P2BASEDMG:2
