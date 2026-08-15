# DefeatSpace_GivesExp
#// JTL_055 You're All Clear, Kid (event) — Defeat an enemy space unit with 3 or less remaining HP. If you
#// do and an opponent controls no space units, you may give an Experience token to a unit. P1 defeats the
#// only enemy space unit (SOR_225); the opponent now has no space units, so P1 gives an Experience token
#// (+1/+1) to SOR_095 (3/3 → 4/4).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_055
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4

---

# OpponentStillHasSpace_NoExp
#// JTL_055 You're All Clear, Kid (event) — the Experience rider only applies if the opponent controls NO
#// space units afterward. P1 defeats SOR_225 (1 remaining HP), but JTL_069 (4/7, 7 remaining HP — not a
#// legal target) remains, so no Experience is offered.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_055
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_069
P1GROUNDARENAUNIT:0:POWER:3
P1NODECISION


---

# Offer_OnlyEnemySpaceUnitsWithThreeOrLessRemainingHP
#// JTL_055 You're All Clear, Kid — "Defeat an ENEMY SPACE unit with 3 or less REMAINING HP." Three axes
#// are enforced at once: controller (P1's own 1-HP TIE fighter must not be offered), arena (the enemy's
#// 3-HP ground Battlefield Marine must not be offered) and REMAINING (not printed) HP — the undamaged
#// 4/7 Munificent Frigate is out at 7 remaining, while the 6/9 Redemption carrying 6 damage is IN at 3
#// remaining. Answering can only ever prove one branch, so the decision is left PENDING and the offer
#// itself is asserted.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_055
WithP1Resources: 2
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
WithP2SpaceArena: SOR_052:1:6
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirSpaceArena-0&theirSpaceArena-1&theirSpaceArena-3
