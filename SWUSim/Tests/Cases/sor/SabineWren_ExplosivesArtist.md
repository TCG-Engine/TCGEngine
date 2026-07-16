# BelowThreshold_CanBeAttacked
#// SOR_142 Sabine Wren — boundary: with only 2 aspects among other friendly units (Heroism + Villainy),
#// the protection is OFF and Sabine can be attacked normally. P2's SEC_080 (3 power) attacks and
#// defeats her (2/3).

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 2
WithP1GroundArena: SOR_142:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0

---

# OnAttack_BaseAlwaysPings
#// SOR_142 Sabine Wren — when attacking a BASE, she always pings that base (no choice, the defender IS
#// the base): 1 (ping) + 2 (combat) = 3 to the enemy base.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0

## WHEN
- P1>AttackGroundArena:0

## EXPECT
P2BASEDMG:3
P1NODECISION

---

# OnAttack_DeclinePing
#// SOR_142 Sabine Wren — the On Attack ping is optional ("you may"): declining deals no extra damage,
#// only the 2 combat damage to the defender.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:0

---

# OnAttack_PingBase
#// SOR_142 Sabine Wren — On Attack vs a unit, she may ping a BASE instead of the defender. Attacking
#// SOR_063, she pings the enemy base for 1; SOR_063 takes only the 2 combat damage.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# OnAttack_PingDefender
#// SOR_142 Sabine Wren — On Attack vs a unit: "You may deal 1 to the defender or a base." Sabine
#// attacks SOR_063 (2/4) and pings the DEFENDER → 1 (ping) + 2 (combat) = 3 damage. Sabine takes 2
#// counter and survives (2/3).

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# Protected_CantBeAttacked
#// SOR_142 Sabine Wren — "While there are at least 3 aspects among other friendly units, this unit
#// can't be attacked." Sabine is alone in the ground arena; 3 friendly space units (Heroism + Villainy
#// + Vigilance = 3 aspects) protect her. P2's ground attacker has no legal unit target → its attack
#// auto-redirects to P1's base; Sabine is untouched.

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 2
WithP1GroundArena: SOR_142:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: JTL_069:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_142
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3
