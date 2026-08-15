# PreDamagedTarget
#// JTL_144 No Disintegrations (event) — the amount uses REMAINING HP, not max. SOR_046 (3/7) already has
#// 2 damage → 5 remaining HP → takes 5−1=4 more → total 6 damage (left at 1). Distinguishes
#// remaining-HP from max-HP.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_144
WithP1Resources: 3
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P2GROUNDARENACOUNT:1

---

# UndamagedTarget
#// JTL_144 No Disintegrations (event) — Deal damage to a non-leader unit equal to 1 less than its
#// remaining HP. SOR_046 (3/7, undamaged) has 7 remaining HP, so it takes 6 (left at 1 HP). Auto-resolves
#// (only one non-leader unit in play).

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_144
WithP1Resources: 3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6
P2GROUNDARENACOUNT:1

---

# Offer_AnyNonLeaderUnit_BothControllers_LeaderExcluded
#// JTL_144 No Disintegrations — "Deal damage to a NON-LEADER UNIT ..." is unrestricted by controller and by
#// arena, but excludes leaders. Board seeds a friendly ground unit (SOR_046), an enemy ground unit (SOR_095),
#// an enemy SPACE unit (SOR_225) — all three must be offered — plus P2's DEPLOYED leader JTL_002
#// (theirGroundArena-1, proven present by P2GROUNDARENACOUNT:2), which must NOT be offered.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021;
  theirLeader:JTL_002:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_144
WithP1Resources: 3
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:JTL_002
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0
