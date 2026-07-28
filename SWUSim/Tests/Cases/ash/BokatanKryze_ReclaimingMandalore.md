# Deployed_OnAttack_CreateMandalorian
#// ASH_010 Bo-Katan Kryze (deployed) — On Attack: if you control a unit in each arena, create a
#// Mandalorian token. Bo-Katan (ground) + an X-Wing (space) → one unit in each arena → token.

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_010:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_T01

---

# Deployed_OnAttack_NoSpaceUnit_NoToken
#// ASH_010 Bo-Katan Kryze (deployed) — On Attack fizzle: with no friendly space unit, no
#// Mandalorian token is created (ground count stays 1).

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_010:1:1:1
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:1

---

# Deployed_Passive_MandalorianBuff
#// ASH_010 Bo-Katan Kryze (deployed) — passive: other friendly Mandalorian units get +1/+0.
#// The Mandalorian token (ASH_T01, 2/2) becomes 3/2.

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_010:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_T01:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:2

---

# NotEachArena_NoToken
#// ASH_010 Bo-Katan Kryze — the token requires a unit in EACH arena. With only a ground unit, no token is
#// created (the ground arena stays at 1); the cost is still paid (Bo-Katan exhausts, 2 resources spent).
## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_010
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# UnitEachArena_Token
#// ASH_010 Bo-Katan Kryze — Leader Action [2 resources, Exhaust]: if you control a unit in each arena, create
#// a Mandalorian token. P1 has SOR_095 (ground) and SOR_237 (space), so a Mandalorian token (ASH_T01, ground)
#// is created — the ground arena goes to 2 units; Bo-Katan exhausts and 2 resources are spent.
## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_010
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENACOUNT:2
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# Deploy_Blocked_BelowThreshold
#// ASH_010 Bo-Katan Kryze — her deploy threshold is 10, reduced by 1 for each Mandalorian unit you control.
#// With 8 resources and one Mandalorian unit (ASH_064 The Armorer; SOR_095 Battlefield Marine is not
#// Mandalorian), the total 8 + 1 = 9 is below 10, so she cannot be deployed — the deploy is a no-op and she
#// stays in leader form.
## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_010
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1GroundArena: [ASH_064:1:0 SOR_095:1:0]
## WHEN
- P1>DeployLeader
## EXPECT
P1LEADER:NOTDEPLOYED

---

# Deploy_Allowed_ResourcesOnly
#// ASH_010 Bo-Katan Kryze — with 10 resources and no Mandalorian units, the deploy threshold (10) is met by
#// resources alone, so she deploys as a leader unit.
## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_010
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1LEADER:DEPLOYED

---

# Deployed_ConstantBuff_SelectiveMandalorian
#// ASH_010 Bo-Katan Kryze (deployed) — "Other friendly Mandalorian units get +1/+0." The friendly Mandalorian
#// ASH_064 The Armorer (5/5) becomes 6/5; the non-Mandalorian SOR_095 Battlefield Marine (3/3) is unchanged;
#// and Bo-Katan herself (4/7) is not buffed ("other" units only).
## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_010:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [ASH_064:1:0 SOR_095:1:0]
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_064
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
P1GROUNDARENAUNIT:2:CARDID:ASH_010
P1GROUNDARENAUNIT:2:POWER:4
P1GROUNDARENAUNIT:2:HP:7

---

# Deploy_Allowed_MandalorianReducesThreshold
#// ASH_010 Bo-Katan Kryze — deploy threshold 10 is reduced by each friendly Mandalorian unit. With 9
#// resources and one Mandalorian unit (ASH_064 The Armorer), 9 + 1 = 10 meets the threshold, so she deploys.
## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_010
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 9
WithP1GroundArena: ASH_064:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1LEADER:DEPLOYED
