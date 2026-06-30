# ASH_004 Grand Admiral Thrawn (deployed) — On Attack: if you control more units than the
# defending player, you may defeat a non-leader unit they control. P1 has 2 units (Thrawn +
# Dark Trooper), P2 has 1 → may defeat the enemy unit.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_004:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
