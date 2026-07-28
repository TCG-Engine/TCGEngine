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

---

# LeaderSide_LoneBothArenas_BuffSpace
#// ASH_003 Baylan Skoll (leader Action) — a unit alone in EITHER arena qualifies. With one ground unit
#// (SOR_164 Wampa) and one space unit (SEC_213 A-Wing), both are eligible; buffing the lone space A-Wing
#// gives +2/+2 → 3/4. Baylan exhausts and 1 resource is spent.
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_003
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: SEC_213:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:4
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderSide_LoneSpace_GroundMultiple
#// ASH_003 Baylan Skoll (leader Action) — with two ground units (SOR_164 Wampa + LOF_254 Porg) neither ground
#// unit is alone, but the lone space unit (SEC_213 A-Wing) still qualifies and is the only legal target
#// (auto-resolved) → +2/+2 to 3/4.
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_003
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: [SOR_164:1:0 LOF_254:1:0]
WithP1SpaceArena: SEC_213:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:4
P1LEADER:EXHAUSTED

---

# Deployed_LeaderExcluded_BuffGroundUnit
#// ASH_003 Baylan Skoll (deployed, On Attack) — the leader unit itself is excluded when checking "alone in
#// its arena", so a single friendly non-leader ground unit (SOR_164 Wampa) sharing the arena with deployed
#// Baylan still counts as alone. On attack, choosing Wampa grants +2/+2 and Sentinel → 6/7.
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_003:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: SEC_213:1:0
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# Deployed_BothMultiple_NoBuff
#// ASH_003 Baylan Skoll (deployed, On Attack) — if every arena has multiple friendly non-leader units, no
#// unit is alone and nothing is buffed. Ground has SOR_164 Wampa + LOF_254 Porg, space has SEC_213 A-Wing +
#// JTL_T01 TIE Fighter; on attack there is no legal target and all units keep base stats.
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_003:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_164:1:0 LOF_254:1:0]
WithP1SpaceArena: [SEC_213:1:0 JTL_T01:1:0]
## WHEN
- P1>AttackGroundArena:2:BASE
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:1
P1SPACEARENAUNIT:0:POWER:1

---

# Deployed_PassAbility
#// ASH_003 Baylan Skoll (deployed, On Attack) — the buff is optional. With a lone friendly unit available
#// (SOR_164 Wampa, ground), declining the ability leaves everything at base stats.
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_003:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: SEC_213:1:0
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:PASS
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:POWER:1
