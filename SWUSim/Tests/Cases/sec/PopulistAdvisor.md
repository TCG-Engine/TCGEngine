# EnemyBaseHit_GainsSentinel
#// SEC_041 (Ground, 1/4) — When an enemy unit deals combat damage to your base: this unit gains
#//   Sentinel for this phase. P1 has SEC_041; P2's SOR_046 (3 power) attacks P1's base → SEC_041
#//   reacts and gains Sentinel.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# OverwhelmDamageToBase_GainsSentinel
#// SEC_041 — the trigger fires on Overwhelm excess dealt to your base too. P2's Wampa (SOR_164, 4/5
#//   Overwhelm) attacks P1's SpecForce Soldier (SOR_140, 2/2): the 2/2 dies and 2 excess damage spills
#//   to P1's base. That base combat damage from an enemy unit triggers SEC_041 → it gains Sentinel.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SOR_140:1:0
WithP2GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:1

## EXPECT
P1BASEDMG:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# YouAttack_NoSentinel
#// SEC_041 — the trigger requires an ENEMY unit to deal the base damage. When P1 is the attacker
#//   (SOR_095 Battlefield Marine hits P2's base), SEC_041 does NOT gain Sentinel.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
