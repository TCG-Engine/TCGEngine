# Krennic leader passive: friendly damaged unit gets +1/+0.
# SOR_095 has base power 3. With 1 damage, Krennic's passive gives it +1 -> power 4.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1GroundArena: SOR_095:1:1

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
