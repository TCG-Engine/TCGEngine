# Bounty (defeat): opponent collects draw-card bounty when unit is defeated
# Wampa (SOR_164, 5/4) attacks Hylobon Enforcer (SHD_027, Bounty: draw a card).
# HE is defeated. P1 (opponent) is offered the bounty and answers YES.
# P1 draws from their deck. Hand count becomes 1, deck count becomes 0.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_164:1:0   # Wampa 5/4
WithP2GroundArena: SHD_027:1:0   # Hylobon Enforcer (Bounty: draw a card)
WithP1Deck: SOR_095               # Card to draw when bounty collected

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:0
