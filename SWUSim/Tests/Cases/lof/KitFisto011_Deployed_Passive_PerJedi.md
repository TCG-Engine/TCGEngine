# LOF_011 Kit Fisto (deployed, 1/6) — passive: gets +1/+0 for each OTHER friendly Jedi unit. With
# two other Jedi (LOF_230, LOF_093) → power 3.

## GIVEN
CommonSetup: grw/brk/{
  myLeader:LOF_011:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_230:1:0
WithP1GroundArena: LOF_093:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:2:POWER:3
