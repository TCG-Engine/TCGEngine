# ReturnSpaceUnitAndUpgrades
#// JTL_232 Jump to Lightspeed (event) — Return a friendly space unit and any number of its non-leader
#// upgrades to owners' hands. P1 returns SOR_237 and chooses to return its upgrade SOR_120 too; both go to
#// P1's hand.

## GIVEN
CommonSetup: gyw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_232
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:2

---

# ReturnMultipleUpgrades
#// JTL_232 Jump to Lightspeed — the player may return MULTIPLE of the unit's non-leader upgrades. SOR_237
#// carries two upgrades (SOR_120 + SOR_069); P1 chooses to return both, so all three cards go to P1's hand.

## GIVEN
CommonSetup: gyw/bbk/{myLeader:JTL_016;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_232
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: [0:SOR_120 0:SOR_069]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:3

---

# ReturnNoneOfTheUpgrades
#// JTL_232 Jump to Lightspeed — "any number" includes NONE. P1 returns SOR_237 but declines to return its
#// upgrade SOR_120; with no host to remain on, SOR_120 is defeated to its owner's discard while SOR_237
#// returns to hand.

## GIVEN
CommonSetup: gyw/bbk/{myLeader:JTL_016;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_232
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:2

---

# TokenUpgradesRemovedFromGame
#// JTL_232 — token upgrades (which have no owner's hand to return to) are REMOVED from the game rather than
#// returned. SOR_237 carries a real upgrade (SOR_120) and a Shield token (SOR_T02): the unit + SOR_120 go
#// to hand (2 cards), while the Shield token is gone entirely.

## GIVEN
CommonSetup: gyw/bbk/{myLeader:JTL_016;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_232
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: [0:SOR_120 0:SOR_T02]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:2

---

# PlayCopyForFreeThisPhase
#// JTL_232 Jump to Lightspeed — "The next time you play a copy of that unit this phase, you may play it for
#// free." P1 returns SOR_237 (cost 2) with its last 2 resources (0 left), then replays the returned SOR_237
#// from hand FOR FREE this phase — it enters the space arena with 0 resources spent.

## GIVEN
CommonSetup: gyw/bbk/{myLeader:JTL_016;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_232
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# OpponentCannotPlayCopyForFree
#// JTL_232 — the free-replay charge belongs to the caster, not the opponent. P1 returns SOR_237 (arming the
#// free-replay for itself), then P2 tries to play its OWN copy of SOR_237 with only 1 resource: P2 gets no
#// discount, so the cost-2 unit cannot be played and stays in P2's hand.

## GIVEN
CommonSetup: gyw/gyw/{myLeader:JTL_016;myBase:JTL_022;theirBase:SOR_021;theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_232
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0
WithP2Hand: SOR_237

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2HANDCOUNT:1
P2RESAVAILABLE:1
