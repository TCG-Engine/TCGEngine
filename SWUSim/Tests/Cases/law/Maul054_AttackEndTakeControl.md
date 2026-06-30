# LAW_054 Maul (6/8, Overwhelm) — When Attack Ends: if this unit dealt combat damage to a player's
# base, you may take control of a non-leader unit that player controls. Maul attacks the base; take
# control of the enemy SEC_080.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_054:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0
