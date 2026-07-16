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
