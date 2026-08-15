# LeaderAction_Heal1_MaxHealIsOne
#// SEC_005 Satine Kryze (leader) — with only 1 healable damage the amount choice is Heal0/Heal1 (never
#// Heal2). P1 takes Heal1: 1 healed, 1 dealt to their own base.
#// (Only the AMOUNT is answered: the unit pick is mandatory and the lone damaged unit auto-resolves.
#// The amount is now ALWAYS offered — including Heal0 — rather than being applied automatically at
#// maxHeal==1. Per the USER RULING on "up to N": the target is mandatory, the soft pass is amount zero.)

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:1

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Heal1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# LeaderAction_Heal2DealBase2
#// SEC_005 Satine Kryze (leader) — Action [Exhaust]: Heal up to 2 damage from a unit. If you do, deal
#// that much damage to your base. Friendly SEC_080 has 2 damage → heal 2 (DAMAGE:0), then deal 2 to P1's
#// own base. Player chooses Heal2 (the up-to amount). No resource cost.
#// (Only the AMOUNT is answered: the unit pick is mandatory and there is exactly one damaged unit, so
#// it auto-resolves. A mandatory single-target choose still auto-resolves — that shortcut was only
#// removed for OPTIONAL offers.)

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:2

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Heal2

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:2
P1LEADER:EXHAUSTED

---

# LeaderAction_HealEnemyUnit1
#// SEC_005 Satine Kryze (leader) — "Heal up to 2 damage from a unit" targets ANY unit, including an
#//   enemy. Friendly SEC_080 has 3 damage; enemy Wampa has 1. P1 chooses to heal the enemy Wampa;
#//   since it has only 1 damage the amount choice is Heal0/Heal1; P1 takes Heal1, so 1 is healed and 1
#//   is dealt to P1's base. The friendly unit's damage is untouched.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:3
WithP2GroundArena: SOR_164:1:1

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:Heal1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:1
P1LEADER:EXHAUSTED

---

# LeaderAction_HealZero_NoBaseDamage
#// SEC_005 Satine Kryze (leader) — the heal is "up to 2", so the player may choose to heal 0. Per the
#//   USER RULING (2026-08-14) the soft pass is the AMOUNT, not the target: P1 must still name a unit,
#//   then takes Heal0. No damage is healed and none is dealt to P1's base (both units keep their
#//   damage). The leader still exhausts.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:3
WithP2GroundArena: SOR_164:1:1

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Heal0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:0
P1LEADER:EXHAUSTED

---

# TargetChoiceIsMandatory_ZeroIsTheSoftPass
#// SEC_005 Satine Kryze (leader) — USER RULING (2026-08-14): for an "up to N" effect the TARGET choice
#// is mandatory and the soft pass is an amount of zero. So the unit pick offers only the damaged units,
#// with no decline among them, and the decision is left PENDING here to assert that pool. The zero
#// outcome itself is covered by LeaderAction_HealZero_NoBaseDamage (target chosen, then Heal0).
#// This replaces a section that asserted the opposite — that the target pick could be declined.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:1
WithP2GroundArena: SOR_164:1:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Heal_up_to_2_damage_from_a_unit
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
