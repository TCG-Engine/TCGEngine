# 6Power_Deal2Base
#// LOF_163 Quinlan Vos (4 power) — On Attack: if this unit has 6 or more power, may deal 2 to an enemy
#// base. With Academy Training (+2/+2) he is 6 power, so attacking the base deals 6 combat + 2 ability = 8.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_163:1:0
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:8

---

# AttackUnit_StillDeal2Base
#// LOF_163 Quinlan Vos — the On-Attack "deal 2 to an enemy base" fires regardless of what he attacks. With
#// Academy Training (+2/+2) he is 6 power; he attacks the enemy SOR_046 (3/7) dealing 6 combat, then his
#// ability deals 2 to the enemy base.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_163:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P2BASEDMG:2

---

# Pass_Optional
#// LOF_163 Quinlan Vos — the "deal 2 to an enemy base" is optional. At 6 power he attacks the base for 6
#// combat but P1 declines the ability, so the base takes 6 (not 8).

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_163:1:0
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:6

---

# Under6_NoTrigger
#// LOF_163 Quinlan Vos — with only 4 power (no buff) the "6 or more power" gate fails, so the ability does
#// not trigger; attacking the base deals just 4 combat with no prompt.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_163:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
