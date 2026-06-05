# SOR_158 Jedha Agitator (Aggression unit, cost 2, 2/1, Rebel) — "Saboteur. On Attack: If you control
# a leader unit, deal 2 damage to a ground unit or a base." P1 has a DEPLOYED leader (SOR_014 flag) so
# the condition holds. Jedha attacks the enemy base (combat → 2 to base); its On Attack deals 2 to the
# enemy ground unit LAW_124 (4/7 → survives at DAMAGE:2).

## GIVEN
P1LeaderBase: SOR_014:1:1:0/SOR_026
P2LeaderBase: SOR_010:1:0:0/SOR_027
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_158:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2
