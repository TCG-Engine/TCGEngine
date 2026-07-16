# OnAttack_GiveOfficialRestore2
#// SEC_045 (Ground, 2/5) — Restore 1 (auto) + On Attack: give another friendly Official unit Restore 2
#//   for this phase. SEC_045 attacks P2's base; On Attack grants SEC_041 (an Official) Restore 2.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_045:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:1:HASKEYWORD:Restore
