# LAW_074 Maz Kanata — when the top 5 contain NO Underworld unit, nothing is played (the player picks
# none). Maz's deck is all SOR_095 (Rebel/Trooper — not Underworld), so the search finds no valid target;
# declining leaves the board with just Maz, resources untouched, and the 5 looked-at cards returned to
# the deck (count unchanged at 6).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: LAW_074:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:3
P1DECKCOUNT:6
