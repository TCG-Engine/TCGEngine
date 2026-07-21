# OnAttack_OpponentChoosesDraw
#// LOF_065 Watto — On Attack: an opponent chooses one: you give an Experience token to a friendly unit,
#// or you draw a card. Watto attacks the base; P2 picks "Draw", so P1 draws a card.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_065:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:Draw

## EXPECT
P1HANDCOUNT:1
P2BASEDMG:1

---

# OnAttack_OpponentChoosesGiveExperience
#// LOF_065 Watto — On Attack the opponent instead picks the Experience branch: P1 gives an Experience token
#// to a friendly unit (here the Marine, SOR_095) — no card is drawn. Watto (idx 0) attacks the base; P2
#// chooses GiveExp; P1 puts the Experience on the Marine (idx 1). Ref: opponent chooses to have you give an
#// Experience token to a friendly unit.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_065:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:GiveExp
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1HANDCOUNT:0
P2BASEDMG:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:4
