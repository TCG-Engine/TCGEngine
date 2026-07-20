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

---

# ReturnSomeUpgrades_RestDiscarded
#// JTL_232 Jump to Lightspeed — "any number" of the unit's upgrades: returning SOME leaves the rest with
#// no host, so they are defeated. SOR_237 carries two upgrades (SOR_120 + SOR_121); P1 returns the unit
#// and selects ONLY SOR_120 to return. Unit + SOR_120 → P1's hand (2 cards); SOR_121 (unselected) →
#// P1's discard alongside the Jump event.

## GIVEN
CommonSetup: gyw/bbk/{myLeader:JTL_016;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_232
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: [0:SOR_120 0:SOR_121]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:2
P1DISCARDCOUNT:2

---

# OnlyChosenUnitsUpgradesOffered
#// JTL_232 Jump to Lightspeed returns ONE chosen space unit and only THAT unit's upgrades — an upgrade
#// on a different unit is untouched. P1 has SOR_237 (no upgrades) and SHD_042 (carrying SOR_120). P1
#// chooses to return SOR_237; SHD_042 and its SOR_120 stay in play with no upgrade prompt.

## GIVEN
CommonSetup: gyw/bbk/{myLeader:JTL_016;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_232
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SHD_042:1:0
WithP1SpaceArenaUpgrade: 1:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_042
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:1
P1HANDCARD:0:SOR_237

---

# DifferentCardSameTitleNotFree
#// JTL_232 Jump to Lightspeed — the free-replay is keyed to the EXACT returned card, not its title.
#// P1 returns JTL_249 Millennium Falcon (Get Out and Push), arming a free replay of THAT card. Playing
#// SOR_193 Millennium Falcon (Piece of Junk) — same title, different card — gets NO discount and costs
#// its full 3. Resources: start 8, Jump −2 → 6, SOR_193 −3 → 3 (a free SOR_193 would have left 6).

## GIVEN
CommonSetup: gyw/bbk/{myLeader:JTL_016;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_232
WithP1Hand: SOR_193
WithP1Resources: 8
WithP1SpaceArena: JTL_249:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1RESAVAILABLE:3

---

# ReturnOpponentOwnedUpgradeToOpponent
#// JTL_232 Jump to Lightspeed can return an upgrade OWNED BY THE OPPONENT that is attached to the
#// returned unit — it goes to its OWNER's hand, not the caster's. P2 plays SHD_071 Top Target (a Bounty
#// upgrade, playable on an enemy unit) onto P1's SOR_237; P1 then Jumps SOR_237 and returns the Top
#// Target. The unit goes to P1's hand and the Top Target returns to its owner P2's hand.

## GIVEN
CommonSetup: gyw/bbk/{myLeader:JTL_016;myBase:JTL_022;theirBase:SOR_021;theirResources:4}
SkipPreGame: true
WithActivePlayer: 2
WithP1Hand: JTL_232
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0
WithP2Hand: SHD_071

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_237
P2HANDCOUNT:1
P2HANDCARD:0:SHD_071

---

# ReturnUpgradeOnTokenUnit
#// JTL_232 Jump to Lightspeed on a TOKEN space unit — the token host has no owner's hand to return to,
#// so it is removed from the game, but a real (non-token) upgrade on it still returns to hand. An X-Wing
#// token (JTL_T02) carries SOR_120 Academy Training; Jump returns the pair: the token is gone entirely
#// and SOR_120 goes to P1's hand.

## GIVEN
CommonSetup: gyw/bbk/{myLeader:JTL_016;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_232
WithP1Resources: 2
WithP1SpaceArena: JTL_T02:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_120

---

# LeaderPilotDefeatedNotReturned
#// JTL_232 Jump to Lightspeed — a LEADER attached as a Pilot can't be returned to hand, so when its host
#// is returned the leader is defeated instead (it goes back to its base exhausted). Asajj Ventress
#// (JTL_001) is deployed as a Pilot on SOR_237; P1 Jumps SOR_237 → the unit returns to hand and Asajj
#// returns to base, undeployed and exhausted.

## GIVEN
CommonSetup: gyw/bbk/{myLeader:JTL_001;myLeaderDeployedPilot:true;myBase:SOR_029;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_232
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_237
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED

---

# NoDiscountFollowingTurn
#// JTL_232 Jump to Lightspeed — the free replay is good only for THE PHASE it was played. P1 Jumps
#// SOR_237 (arming the free replay), then a full round passes (the free-replay charge clears at the
#// regroup phase). Replaying SOR_237 in the NEXT action phase costs its full 2. Resources: 6 ready →
#// (Jump −2, all readied at regroup) → 6 → SOR_237 −2 → 4. A persisted free replay would have left 6.

## GIVEN
CommonSetup: gyw/bbk/{myLeader:JTL_016;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_232
WithP1Resources: 6
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_237

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1RESAVAILABLE:4
