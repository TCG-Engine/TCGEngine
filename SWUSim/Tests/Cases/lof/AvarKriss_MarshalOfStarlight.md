# CreateForce
#// LOF_007 Avar Kriss — Action [Exhaust]: The Force is with you (create your Force token). P1 starts without
#// the Force and gains it.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:LOF_007;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASFORCE
P1LEADER:EXHAUSTED

---

# Deployed_Passive_ForceBuff
#// LOF_007 Avar Kriss (deployed, 4/10) — passive: while the Force is with you, this unit gets +4/+0
#// and gains Overwhelm. With the Force → 8 power + Overwhelm.

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
