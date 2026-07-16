# Deployed_OnAttack_BuffLoneUnit
#// ASH_003 Baylan Skoll (deployed) — On Attack: may give a friendly unit +2/+2 and Sentinel
#// for this phase if it's the only non-leader unit you control in its arena. The lone space TIE
#// (SOR_225, 2/1) qualifies → becomes 4/3 with Sentinel.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_003:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:3
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# BuffLoneUnit
#// ASH_003 Baylan Skoll — Leader Action [1 resource, Exhaust]: give a friendly unit +2/+2 for this phase if
#// it's the only unit you control in its arena. SOR_095 is alone in the ground arena (and the only valid
#// target, auto-resolved), so it gets +2/+2 (3 → 5 power); Baylan exhausts and 1 resource is spent.
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_003
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# NotAlone_NoBuff
#// ASH_003 Baylan Skoll — the +2/+2 only applies to a unit ALONE in its arena. With two ground units,
#// neither qualifies, so no buff is given (both stay at base power); the cost is still paid (Baylan exhausts,
#// 1 resource spent).
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_003
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_135:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:4
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
