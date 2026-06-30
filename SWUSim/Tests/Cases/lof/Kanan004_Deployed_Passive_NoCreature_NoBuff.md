# LOF_004 Kanan Jarrus (deployed) — passive fizzle: with no other Creature/Spectre unit, no buff
# (stays 3/6).

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:LOF_004:1:1:1
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:6
