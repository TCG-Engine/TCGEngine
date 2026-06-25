# LOF_004 Kanan Jarrus (deployed, 3/6) — passive: while you control another Creature or Spectre
# unit, this unit gets +2/+2. With a Creature (LOF_254) in play → Kanan is 5/8.

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:LOF_004:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_254:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:8
