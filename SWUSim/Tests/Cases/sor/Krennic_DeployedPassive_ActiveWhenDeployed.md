# Krennic's passive is active on both leader and leader-unit side.
# When Krennic is deployed in the arena, another friendly damaged unit still gets +1/+0.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001:1:1:1
}
SkipPreGame: true
WithP1GroundArena: SOR_095:1:1

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
