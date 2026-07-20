# RegroupSelfDamage
#// JTL_198 Fireball — When the regroup phase starts: Deal 1 damage to this unit. P1 passes to end the
#// action phase; at regroup start Fireball takes 1 damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_198:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1

---

# Ambush_AttackOnPlay
#// JTL_198 Fireball has Ambush — when played, it may immediately attack. P1 plays Fireball (3 power) and
#// ambushes the enemy SOR_237 (2/3): it deals 3 (defeating SOR_237) and takes 2 counter damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_198
WithP1Resources: 8
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:JTL_198
P1SPACEARENAUNIT:0:DAMAGE:2
