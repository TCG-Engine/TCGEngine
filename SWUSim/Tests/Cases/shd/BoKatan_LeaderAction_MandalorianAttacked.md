# SHD_012 Bo-Katan Kryze — Leader Action: Mandalorian attacked → deal 1 to a unit.
# SOR_162 (Disabling Fang Fighter) is Mandalorian trait, Space arena.

## GIVEN
P1LeaderBase: SHD_012/SOR_026
P2LeaderBase: SOR_009/SOR_024
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: SOR_162:2:0
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED
