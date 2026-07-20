# DefeatShieldsDeal3
#// JTL_180 Piercing Shot — Defeat all Shield tokens on a unit, then deal 3 damage to it. SOR_046's shield
#// is defeated first, so the 3 damage lands (not absorbed).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_180
WithP1Resources: 8
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# NoShield_Deal3
#// JTL_180 Piercing Shot — with no Shield tokens on the target, the "defeat all Shields" part is a no-op
#// and the unit simply takes the 3 damage. SOR_232 AT-ST (6/7, unshielded) takes 3 and survives.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_180
WithP1Resources: 8
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
