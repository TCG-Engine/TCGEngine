# OnAttack_Heal
#// JTL_066 Trace Martez (pilot) — Attached gains "On Attack: you may heal 2 total from any number of
#// units." The host (SOR_225 + pilot) attacks the base; the granted On Attack heals SOR_046 (3 → 1).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaUpgrade: 0:JTL_066
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
