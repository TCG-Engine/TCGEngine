# MoffGideon_Deployed_LowCostBuffAndOverwhelm
#// SHD_007 Moff Gideon (deployed) — "Each friendly unit that costs 3 or less gets +1/+0 and gains
#// Overwhelm while attacking an enemy unit." Deployed (5 resources), SOR_095 (cost 2, power 3 → 4 with
#// the buff) attacks SOR_160 (2 HP): 4 − 2 = 2 excess spills to P2's base via the granted Overwhelm.
#// The base damage of 2 confirms both the +1 (without it, 3 vs 2 HP = 1 excess) and the Overwhelm grant.

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_007;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_160:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2

---

# MoffGideon_Front_AttackLowCost_VsUnitBuff
#// SHD_007 Moff Gideon (front Action [Exhaust]) — "Attack with a unit that costs 3 or less. If it's
#// attacking a unit, it gets +1/+0 for this attack." SOR_095 (cost 2, power 3) is the lone ≤3 attacker;
#// it attacks the enemy SOR_046 and deals 3 + 1 = 4.

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_007}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# MoffGideon_Front_VsBase_NoBuff
#// SHD_007 Moff Gideon — the +1/+0 only applies when attacking a UNIT. With no enemy units, the ≤3-cost
#// attacker (SOR_095, power 3) hits the base for 3 (no +1).

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_007}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
