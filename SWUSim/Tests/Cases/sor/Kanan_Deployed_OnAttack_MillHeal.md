# SOR_047 Kanan Jarrus — the REAL deployed-leader path (not an arena fixture): deploy Kanan via the
# Epic Action, then attack. His deploy-side OnAttack fires: 1 friendly Spectre (Kanan himself) →
# mill 1 from the defender's deck (Aggression) → 1 distinct aspect → heal 1 from P1's base (2 → 1).
# Kanan's 4 power hits P2's base. (Explicit leader — CommonSetup's 'bw' code maps to Luke, not Kanan.)

## GIVEN
P1LeaderBase: SOR_047/SOR_021:2
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2Deck: SOR_172

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:DEPLOYED
P1BASEDMG:1
P2BASEDMG:4
P2DECKCOUNT:0
P2DISCARDCOUNT:1
