# LOF_007 Avar Kriss (deployed, 4/10) — passive: while the Force is with you, this unit gets +4/+0
# and gains Overwhelm. With the Force → 8 power + Overwhelm.

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:LOF_007:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
