# SHD_012 Bo-Katan + SHD_182 Bravado — same Mandalorian attacks twice, second OnAttack
# ability must NOT fire. "Another Mandalorian unit" requires a different unit (uid != attacker).
# Bravado paid at full 5 (no enemy defeated this phase).

## GIVEN
CommonSetup: rrw/ggw/{
  myLeader:SHD_012
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 11:SOR_095
WithP1Hand: SHD_182
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:8
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EPICUSED
P1RESAVAILABLE:6
