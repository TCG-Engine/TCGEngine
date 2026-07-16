# OnAttack_Deals2ToDamaged
#// JTL_151 Red Five — On Attack: You may deal 2 damage to a DAMAGED unit. Red Five attacks P2's base; on
#// attack it deals 2 to the damaged SOR_046 (2 → 4 damage). Undamaged units are not offered.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_151:1:0
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:3

---

# OnAttack_NoDamagedUnit_NoOp
#// JTL_151 Red Five — with no damaged unit in play, the On Attack offers nothing and Red Five simply
#// attacks the base. Proves the "damaged unit" restriction.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_151:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:3
P1NODECISION
