# Front_PlaysAPilotFromResources_OnAVehicleThatEnteredThisPhase
#// Live report (2026-10-06): after Admiral Ackbar (ASH_110) played space units, ASH_001 The Armorer's Action
#// skipped the play-from-resources effect when the only candidates in resources were Pilots. Official ruling
#// (The Armorer - Steel Shapes Us, 07/21/2026): "You can use The Armorer's ability to play any card that can be
#// played as an upgrade, including Pilots." A Pilot played through a "play an upgrade" ability is played AS AN
#// UPGRADE (Piloting ruling, 03/06/2025). The offer filtered resources by printed type 'Upgrade', and a Pilot's
#// printed type is Unit, so it was never offered and the Action soft-passed.
#// P1 plays SEC_213 A-Wing (a Vehicle; it entered play this phase), then uses The Armorer: JTL_098 Snap Wexley
#// (unit 3 / Piloting 2) goes from resources onto the A-Wing as a Pilot, and the deck's top card is resourced.
#// P1RESAVAILABLE pins that Snap was charged his PILOTING cost: priced as a unit (3) instead, the same play leaves
#// fewer ready resources — the only observable difference, since the harness has no Pilot-flag assertion.

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:JTL_098:1,7:SOR_095:1
WithP1Hand: SEC_213
WithP1Deck: [SOR_063]

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_098
P1GROUNDARENACOUNT:0
P1DECKCOUNT:0
P1RESAVAILABLE:1

---

# Front_Pilot_NotOfferedWhenNoVehicleEnteredThisPhase
#// CONTROL: the only unit that entered play this phase is SOR_095 Battlefield Marine (ground, not a Vehicle), so
#// the Pilot has no legal host and is not offered. The Action soft-passes: nothing is attached, Snap stays a
#// resource, and the deck is not touched ("If you do" never happened).

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:JTL_098:1,7:SOR_095:1
WithP1Hand: SOR_095
WithP1Deck: [SOR_063]

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENACOUNT:0
P1DECKCOUNT:1

---

# Deployed_AttackEnd_PlaysAPilotOnAFriendlyVehicle
#// The deployed side has the same filter: "When Attack Ends: You may play an upgrade from your resources on a
#// friendly unit." The Armorer attacks the base, then plays a vanilla Pilot (JTL_108) from resources onto the friendly A-Wing as
#// a Pilot, and resources the deck's top card. JTL_108 is a vanilla Pilot (Piloting 2, no other text), so this section
#// isolates the Pilot route from any "when played as an upgrade" ability. P1RESAVAILABLE pins the PILOTING price
#// (2) rather than the unit price.

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_001:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_213:1:0
WithP1Resources: 3:SOR_046:1,1:JTL_108:1
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myResources-3
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_108
P1DECKCOUNT:0
P1RESAVAILABLE:2

---

# Deployed_SnapWexleySearch_DoesNotResurrectTheRampedCard
#// The deployed play with JTL_098 Snap Wexley, whose "When played as an upgrade: Search the top 5 cards of your deck
#// for a Resistance card" resolves after The Armorer's ramp has resourced the deck's ONLY card. The ramp leaves that
#// deck object marked removed until the next compaction, and the search used to splice the raw deck array — it
#// "peeked" the already-resourced card and its finalize put it back on the bottom: SOR_237 was a resource AND in the
#// deck. Correct: the search finds an empty deck. 3 basics + Snap; Snap leaves, SOR_237 arrives → 4 resources.

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_001:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_213:1:0
WithP1Resources: 3:SOR_046:1,1:JTL_098:1
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myResources-3

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_098
P1RESCOUNT:4
P1DECKCOUNT:0
P1HANDCOUNT:0
P1NODECISION
