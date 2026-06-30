# SEC_133 Syril Karn (Ground, 2/3, Aggression/Villainy) — On Attack: you may disclose
#   AggressionAggressionVillainy → choose a unit; deal 2 to it unless its controller discards a card.
# Syril (idx0) attacks the base. On Attack: disclose two SEC_133 (Agg,Villainy) → choose the enemy
# SOR_046 → its controller (P2) declines to discard → SOR_046 takes 2 damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_133:1:0
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:NO

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P2HANDCOUNT:1
P1NODECISION
