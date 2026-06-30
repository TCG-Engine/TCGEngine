# SOR_158 Jedha Agitator (Aggression unit, cost 2, 2/1, Rebel) — "Saboteur. On Attack: If you control
# a leader unit, deal 2 damage to a ground unit or a base." P1 controls a deployed leader unit
# (Sabine @1) so the condition holds. Jedha (@0) attacks the enemy base (combat → 2 to base); its
# On Attack deals 2 to the enemy ground unit LAW_124 (4/7 → survives at DAMAGE:2).

## GIVEN
CommonSetup: rrw/rrk/{
  myLeader:SOR_014:1:1:1;
  theirBase:SOR_027
}
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
P1GROUNDARENAUNIT:1:ISLEADERUNIT
