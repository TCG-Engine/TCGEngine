# SOR_009 Leia Organa — Deployed: Raid 1 + "When this unit completes an attack: you may attack
# with another Rebel unit." Deployed Leia (3/6, Rebel) attacks the base for 3+1(Raid)=4, then her
# OnAttackEnd lets a second Rebel attack the base for 3 → 7 total base damage.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:DEPLOYED
