# WhenPlayedForceReaction
#// LOF_101 Yoda — When Played: You may use the Force. If you do, heal 5 from a base. AND When you use the
#// Force: You may deal damage to a unit equal to twice the units you control. P1 plays Yoda (controls the
#// Force), uses it, and the use-Force reaction deals 2 (2 × 1 unit = Yoda) to SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_101
WithP1Resources: 14
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NOFORCE

---

# ForceUse_HealsAndDealsTwiceUnits
#// LOF_101 Yoda — a single Force use triggers BOTH his When-Played heal (5 from base) AND his reactive
#// "when you use the Force: deal damage equal to twice the units you control." With Yoda + SOR_095 (2 units)
#// → 4 damage to the enemy; P1's base (5 damage) is fully healed.
## GIVEN
CommonSetup: gbw/bbk/{myLeader:LOF_050;myBase:SOR_021:5;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Resources: 10
WithP1Hand: LOF_101
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# WhenPlayed_SkipForce
#// LOF_101 Yoda — the When-Played "You may use the Force" is optional. P1 plays Yoda but DECLINES the Force
#// use, so no base healing happens and the "when you use the Force" reaction never fires. P1's base keeps its
#// 5 damage and P1 still holds the Force token. Ref: "should allow skipping using the Force".

## GIVEN
CommonSetup: bbk/bbk/{myBase:SOR_021;theirBase:SOR_021;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_101
WithP1Resources: 14
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1BASEDMG:5
P1HASFORCE

---

# ForceUseFromOtherSource_DealsTwiceUnits
#// LOF_101 Yoda — his reactive "When you use the Force: deal damage equal to twice the units you control"
#// fires from ANY Force use, not just his own When-Played. Yoda is already in play alongside SOR_095 and
#// SOR_146 (3 units). P1 plays Yoda's Lightsaber (LOF_102) onto Yoda; the upgrade's "use the Force" triggers
#// Yoda's reaction → 2 x 3 units = 6 damage to the enemy SOR_046. Ref: the "When You Use the Force" suite.

## GIVEN
CommonSetup: gbw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Resources: 10
WithP1Hand: LOF_102
WithP1GroundArena: LOF_101:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1NOFORCE

---

# ForceReaction_Skipped
#// LOF_101 Yoda — the reactive "when you use the Force: you MAY deal damage" is optional. P1 plays Yoda and
#// uses the Force (healing his base), but DECLINES the deal-damage reaction. The enemy SOR_046 takes no
#// damage while the base is still healed. Ref: "should allow skipping the ability".

## GIVEN
CommonSetup: bbk/bbk/{myBase:SOR_021:5;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_101
WithP1Resources: 14
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:NO

## EXPECT
P1BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NOFORCE
