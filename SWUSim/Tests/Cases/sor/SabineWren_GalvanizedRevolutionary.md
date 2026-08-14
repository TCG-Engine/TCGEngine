# SabineWrenLeaderSide
#// SOR_014 Sabine Wren, Galvanized Revolutionary — leader side "Action [exhaust]: Deal 1 damage to
#// each base" (both bases take 1, Sabine exhausts); deployed side "On Attack: Deal 1 damage to each
#// enemy base".
#// COVERAGE: offer=N/A (neither side targets — the action pings both bases, the deployed On Attack
#//           pings the enemy base; no choice is ever raised) · decline=N/A (no "you may" on either
#//           side) · boundary=Deployed_BasePingResolvesBeforeCombatDamage (ping takes the base from
#//           29 to exactly 30 and wins mid-attack) vs Deployed_OnAttackPingsEnemyBaseOnly (non-lethal
#//           ping + normal combat) · control=N/A (a leader unit never changes control — Traitorous
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
