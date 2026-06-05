# SOR_158 Jedha Agitator — the On Attack target can be a BASE instead of a unit. Jedha attacks the
# enemy ground unit (combat → 2 to LAW_124, Jedha 2/1 dies to the 4-power counter); its On Attack
# deals 2 to the enemy base. Combat went to the unit, so the base's 2 damage is purely the ability —
# proving the base branch.

## GIVEN
P1LeaderBase: SOR_014:1:1:0/SOR_026
P2LeaderBase: SOR_010:1:0:0/SOR_027
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_158:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:0
