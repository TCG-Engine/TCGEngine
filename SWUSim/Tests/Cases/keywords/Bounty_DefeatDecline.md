# Bounty (defeat): opponent declines draw-card bounty
# Same setup as collect test. Player answers NO.
# P1 hand remains empty; deck card remains.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_164:1:0   # Wampa 5/4
WithP2GroundArena: SHD_027:1:0   # Hylobon Enforcer (Bounty: draw a card)
WithP1Deck: SOR_095               # Card that should NOT be drawn

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:1
