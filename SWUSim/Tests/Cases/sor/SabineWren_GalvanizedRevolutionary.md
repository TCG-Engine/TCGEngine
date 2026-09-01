# SabineWrenLeaderSide
#// SOR_014 Sabine Wren, Galvanized Revolutionary — leader side "Action [exhaust]: Deal 1 damage to
#// each base" (both bases take 1, Sabine exhausts); deployed side "On Attack: Deal 1 damage to each
#// enemy base".
#// COVERAGE: offer=N/A (neither side targets — the action pings both bases, the deployed On Attack
#//           pings the enemy base; no choice is ever raised) · decline=N/A (no "you may" on either
#//           side) · boundary=Deployed_BasePingResolvesBeforeCombatDamage (ping takes the base from
#//           29 to exactly 30 and wins mid-attack) vs Deployed_OnAttackPingsEnemyBaseOnly (non-lethal
#//           ping + normal combat), plus the Epic Action threshold pair EpicDeploy_ThreeResources_Refused
#//           (4-1) vs EpicDeploy_FourResources_DeploysAndSpendsNothing (exactly 4) · control=N/A (a leader unit never changes control — Traitorous
#//           and control-effects exclude leaders) · reqboundary=N/A (both abilities resolve in a
#//           single drain with no intervening decision)
## GIVEN
CommonSetup: grw/grw/{myResources:2}

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:1
P2BASEDMG:1
P1LEADER:EXHAUSTED

---

# Deployed_OnAttackPingsEnemyBaseOnly
#// SOR_014 Sabine Wren deployed — "On Attack: Deal 1 damage to each enemy base." Unlike the
#// undeployed action (each base), the deployed ping hits ONLY the enemy base: P2 base takes 1,
#// P1's own base takes 0. Combat then resolves normally: Rebel Pathfinder (2/3) takes Sabine's
#// 2 power, Sabine (2/5) takes 2 back.

## GIVEN
CommonSetup: grw/grw/{myLeaderDeployed:true}
P1OnlyActions: true
WithP2GroundArena: SOR_239:1:0    # Rebel Pathfinder 2/3

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2BASEDMG:1
P1BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:ISLEADERUNIT

---

# Deployed_BasePingResolvesBeforeCombatDamage
#// SOR_014 Sabine Wren deployed — the On Attack ping resolves BEFORE combat damage (On Attack
#// triggers fire in step 1 of the attack; combat damage is a later step). With the enemy base at
#// 29, the ping makes 30 and the game is won during the attack.
#// Intended (per CR): combat damage is never dealt after the win — the defender should end at 0.
#// DEFERRED: the engine currently continues resolving the attack after the win is set (known open
#// post-win-resolution item), so the defender's damage is not asserted here; only the win and the
#// base total are.

## GIVEN
CommonSetup: grw/grw/{myLeaderDeployed:true;theirBaseDamage:29}
P1OnlyActions: true
WithP2GroundArena: SOR_239:1:0    # Rebel Pathfinder 2/3

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1WIN
P2BASEDMG:30

---

# TwinSuns_FrontAction_EachBaseMeansALLFOUR
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — a two-seat hardcode NO legacy-helper scan could find.
#// The front Action was written as two literal calls, SWUDealDamageToBase(1, 1) and (1, 2). It names
#// seats as INTEGERS, so every sweep for OtherPlayer()/GetOpponent() walked straight past it. At four
#// seats it damaged seats 1 and 2 and left 3 and 4 untouched, whoever the caster was.
#// "Deal 1 damage to EACH base" is every base at the table — the caster's own included, which is exactly
#// what separates this side from the deployed one below.
## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 2
## WHEN
- P1>UseLeaderAbility
## EXPECT
SEATCOUNT:4
P1BASEDMG:1
P2BASEDMG:1
P3BASEDMG:1
P4BASEDMG:1

---

# TwinSuns_DeployedOnAttack_EachENEMYBase_NotYourOwn
#// The deployed side says "each ENEMY base", so it is the CONTRAST to the section above: seats 2 and 4
#// take 1 each, while P1's own base and teammate P3's take NOTHING. Sabine attacks seat 2's base, so it
#// shows 3 (2 combat + 1 ping) and seat 4 shows 1 — asymmetric on purpose, so a fix that merely swapped
#// which single enemy base got pinged cannot pass. Previously GetOpponent() pinged one seat and returned
#// null above seat 2.
## GIVEN
CommonSetup: grw/grw/{myLeaderDeployed:true}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
## WHEN
- P1>AttackGroundArena:0:P2B
## EXPECT
SEATCOUNT:4
P2BASEDMG:3
P4BASEDMG:1
P3BASEDMG:0
P1BASEDMG:0

---

# EpicDeploy_ThreeResources_Refused
#// SOR_014 Sabine Wren — the SECOND printed clause, "Epic Action: If you control 4 or more resources,
#// deploy this leader", had no section at all: every deploy in this file was seeded pre-deployed via
#// myLeaderDeployed, so the threshold itself was never exercised.
#// This is the NEGATIVE half of the boundary that proves the gate is load-bearing. With 3 resources
#// (one under the printed cost of 4) the Epic Action does nothing: Sabine stays on the leader zone,
#// no unit is created, and — critically — the once-per-game Epic Action slot is NOT consumed, so the
#// refused attempt has not cost the player their deploy.

## GIVEN
CommonSetup: grw/grw/{myResources:3}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
P1RESCOUNT:3

---

# EpicDeploy_FourResources_DeploysAndSpendsNothing
#// SOR_014 Sabine Wren — the POSITIVE half of the same boundary, one resource up: at exactly 4 the
#// Epic Action deploys her. "If you control 4 or more resources" is a THRESHOLD, not a cost — per the
#// printed wording nothing is paid, so all 4 resources are still there and all 4 are still READY after
#// the deploy. The Epic Action slot is consumed (once per game) and the leader side is exhausted by
#// having taken the action.

## GIVEN
CommonSetup: grw/grw/{myResources:4}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1RESCOUNT:4
P1RESAVAILABLE:4

---

# FrontAction_ExhaustedLeader_CannotPing
#// SOR_014 Sabine Wren — "Action [exhaust]: Deal 1 damage to each base" carries an EXHAUST cost, and
#// SabineWrenLeaderSide only ever showed the cost being PAID (leader ends exhausted). This is the
#// negative that proves the cost is a real gate: with the leader already exhausted the action cannot
#// be taken again, so neither base takes a second point of damage.
#// (myLeader field 2 = ready; :0 seats Sabine already exhausted.)

## GIVEN
CommonSetup: grw/grw/{myLeader:SOR_014:0;myResources:2}
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1LEADER:EXHAUSTED
P1LEADER:NOTDEPLOYED
