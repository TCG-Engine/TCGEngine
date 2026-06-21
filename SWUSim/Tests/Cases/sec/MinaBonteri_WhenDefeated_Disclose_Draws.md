# SEC_094 Mina Bonteri (Ground, 2/4, Command/Heroism) — Restore 1 (auto) + When Defeated: you may
#   disclose CommandCommandHeroism → draw a card.
# Mina (2/4) attacks LAW_124 (4/7): simultaneous damage defeats Mina (takes 4, has 4 HP) while
# LAW_124 survives (takes 2). Mina's When Defeated discloses SEC_096 (Command,Heroism) + SEC_080
# (Command,Villainy) → covers CommandCommandHeroism → draw 1.

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
