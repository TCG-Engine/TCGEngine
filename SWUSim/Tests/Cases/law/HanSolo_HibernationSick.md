# OnAttackExp
#// LAW_037 Han Solo (1/1, Shielded) — On Attack: give an Experience token to this unit. He attacks the
#// base; the OnAttack Exp makes him 2/2.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_037:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_037
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2

---

# ShieldedGivesHimAShieldWhenPlayed
#// LAW_037 Han Solo — the printed Shielded keyword ("When you play this unit, give a Shield token to
#// him") is a separate clause from the On Attack Experience and had no section at all. Playing him from
#// hand leaves him with exactly one upgrade, the Shield, before he has ever attacked.

## GIVEN
CommonSetup: bgw/bgw/{myResources:1}
P1OnlyActions: true
WithP1Hand: LAW_037

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_037
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# SeatedWithoutBeingPlayed_NoShield
#// LAW_037 Han Solo — Shielded triggers on being PLAYED, so a Han seeded straight into the arena has no
#// Shield. This is the negative that makes ShieldedGivesHimAShieldWhenPlayed mean something, and it is
#// also the state every other section here starts from.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_037:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# OnAttackExperienceAlsoFiresAttackingAUNIT
#// LAW_037 Han Solo — "On Attack" is not gated on what he attacks. Attacking a 1/1 Battle Droid token
#// (which he trades with: 1 power each way, and he is a 1/1 before the token lands) still gives him the
#// Experience first, so he survives at 2/2 with 1 damage. The existing section only ever attacks the base.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_037:1:0
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:LAW_037
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:1
