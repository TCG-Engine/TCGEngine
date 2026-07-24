# LeaderAction_Heal1Auto
#// SEC_005 Satine Kryze (leader) — with only 1 healable damage (unit has 1 damage), the max heal is 1, so
#// there is no amount choice: it heals 1 and deals 1 to your base automatically. Proves the maxHeal==1
#// auto path (no OPTIONCHOOSE).

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
#//   since it has only 1 damage the max heal is 1 (auto), so 1 is healed and 1 is dealt to P1's base.
#//   The friendly unit's damage is untouched.

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

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:1
P1LEADER:EXHAUSTED

---

# LeaderAction_HealZero_NoBaseDamage
#// SEC_005 Satine Kryze (leader) — the heal is "up to 2", so the player may choose to heal 0 by
#//   declining to pick a unit. No damage is healed and no damage is dealt to P1's base (both units
#//   keep their damage). The leader still exhausts.

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
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:0
P1LEADER:EXHAUSTED
