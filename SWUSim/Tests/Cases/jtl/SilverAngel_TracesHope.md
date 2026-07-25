# WhenHealed_Deal1ToSpaceUnit
#// JTL_062 Silver Angel — When 1 or more damage is healed from this unit: You may deal 1 damage to a
#// space unit. JTL_062 (a Vehicle, 2 damage) attacks P2's base (marking it as having attacked), then
#// Rose Tico's leader action heals 2 from it; the heal triggers JTL_062's reactive, dealing 1 to SOR_237.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_062:1:2
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_062
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:1
P2BASEDMG:2

---

# WhenHealed_Declined
#// JTL_062 Silver Angel — the "deal 1 to a space unit" is a MAY. After Rose Tico heals it, P1 declines
#// (Pass): SOR_237 takes no damage.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_062:1:2
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility
- P1>AnswerDecision:PASS

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_062
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:2

---

# WhenHealed_HealZero_NoTrigger
#// JTL_062 Silver Angel — the reactive fires only on "1 or more damage healed". Here JTL_062 has 0 damage;
#// it attacks P2's base (marking it as a Vehicle that attacked), then Rose Tico's leader action heals it.
#// 0 damage is actually healed, so the "you may deal 1 to a space unit" NEVER triggers — SOR_237 is
#// untouched and the turn passes to P2 with no pending decision.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_062:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_062
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:2
P1NODECISION
