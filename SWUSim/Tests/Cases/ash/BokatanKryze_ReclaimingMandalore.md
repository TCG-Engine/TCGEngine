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
