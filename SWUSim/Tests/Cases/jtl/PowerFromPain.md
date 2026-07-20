# BuffPerDamage
#// JTL_042 Power from Pain (event) — Give a unit +1/+0 this phase for each damage on it. SOR_046 (3/7)
#// has 3 damage, so it gets +3/+0 → power 6.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_042
WithP1Resources: 3
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:7

---

# SnapshotAtPlay_LaterDamageDoesNotIncrease
#// JTL_042 Power from Pain — the +1/+0-per-damage is locked in when the event resolves; damage dealt AFTER
#// does not increase the buff. SOR_232 AT-ST (6/7) has 3 damage → power 9. P2 then deals 2 more with Daring
#// Raid (TWI_170); the AT-ST reaches 5 damage but its power stays 9 (not 11).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_042
WithP1Resources: 3
WithP2Hand: TWI_170
WithP2Resources: 3
WithP1GroundArena: SOR_232:1:3
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_232
P1GROUNDARENAUNIT:0:POWER:9
P1GROUNDARENAUNIT:0:DAMAGE:5
