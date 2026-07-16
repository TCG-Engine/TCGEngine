# BothAffordable_Prompts
#// JTL_035 Tam Ryvora: both Unit and Pilot affordable → OPTIONCHOOSE Unit/Pilot prompt.
#// With 3 resources (covers both unit cost 3 and pilot cost 2) and an empty Vehicle,
#// PlayHand raises the Unit/Pilot OPTIONCHOOSE. Answering Pilot then picks the vehicle.
#// Leader SOR_001 (Vigilance+Villainy) + Base SOR_019 (Vigilance) = no aspect penalty.
#// SOR_225 TIE/ln Fighter: 2/1. JTL_035 upgradePower=+2, upgradeHp=+2. Host becomes 4/3.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SOR_001;
  myBase:SOR_019;
  theirLeader:SOR_001;
  theirBase:SOR_019
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 3
WithP1Hand: JTL_035
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_035
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:3
P1HANDCOUNT:0
P1RESAVAILABLE:1

---

# Glow_PilotOnlyWithVehicle
#// JTL_035 (Tam Ryvora) in hand: pilot cost (2) affordable but unit cost (3) is not.
#// Verifies that the card appears in pilotPlayableHand (pilot-glow list) even though it is
#// not affordable as a unit.
#// JTL_035: unit cost 3, piloting cost 2, aspects Vigilance+Villainy.
#// Leader SOR_001 (Vigilance+Villainy) + Base SOR_019 (Vigilance) = no aspect penalty.
#// SOR_225 TIE/ln Fighter: Vehicle in space, no existing Pilot upgrade → eligible target.
#// WithP1Resources: 2 → 2 ready: pilot cost 2 affordable, unit cost 3 NOT.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SOR_001;
  myBase:SOR_019;
  theirLeader:SOR_001;
  theirBase:SOR_019
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1Hand: JTL_035
WithP1SpaceArena: SOR_225:1:0

## WHEN

## EXPECT
P1HANDPILOTPLAYABLE:0

---

# NoGlow_PilotOnlyNoVehicle
#// JTL_035 (Tam Ryvora) in hand: pilot cost (2) affordable but unit cost (3) is not, NO Vehicle present.
#// Verifies that the card does NOT appear in pilotPlayableHand when there is no eligible
#// Vehicle target, even though the pilot cost is affordable.
#// JTL_035: unit cost 3, piloting cost 2, aspects Vigilance+Villainy.
#// Leader SOR_001 (Vigilance+Villainy) + Base SOR_019 (Vigilance) = no aspect penalty.
#// No units in either arena → SWUGetPilotValidTargets returns empty → no glow.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SOR_001;
  myBase:SOR_019;
  theirLeader:SOR_001;
  theirBase:SOR_019
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1Hand: JTL_035

## WHEN

## EXPECT
P1HANDPILOTPLAYABLENOT:0

---

# PlayAsPilot_Attaches
#// JTL_035 Tam Ryvora played as a Pilot onto a friendly Vehicle.
#// With exactly the piloting cost (2) in resources but not enough for unit cost (3),
#// and one empty Vehicle, PlayHand short-circuits straight to Vehicle pick (no Unit option).
#// JTL_035: unit cost 3, piloting cost 2, aspects Vigilance+Villainy.
#// Leader SOR_001 (Vigilance+Villainy) + Base SOR_019 (Vigilance) = no aspect penalty.
#// Pilot cost = 2. canUnit = false (2 < 3). canPilot = true (2 >= 2, vehicle present).
#// → Pilot-only short-circuit: MZCHOOSE vehicle directly.
#// SOR_225 TIE/ln Fighter: 2/1. JTL_035 upgradePower=+2, upgradeHp=+2. Host becomes 4/3.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SOR_001;
  myBase:SOR_019;
  theirLeader:SOR_001;
  theirBase:SOR_019
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1Hand: JTL_035
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_035
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:3
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# PlayAsUnit_NoVehicle
#// JTL_035 Tam Ryvora played as a Unit when no empty Vehicle is present.
#// With 3 resources (enough for unit cost) and no Vehicle, PlayHand plays it as a unit
#// with no additional decision prompt. canPilot = false (no valid vehicles).
#// JTL_035: unit cost 3, arena Ground, aspects Vigilance+Villainy.
#// Leader SOR_001 (Vigilance+Villainy) + Base SOR_019 (Vigilance) = no aspect penalty.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SOR_001;
  myBase:SOR_019;
  theirLeader:SOR_001;
  theirBase:SOR_019
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 3
WithP1Hand: JTL_035

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_035
P1NODECISION

---

# TwoVehicles_ExplicitPick
#// JTL_035 Tam Ryvora: two empty Vehicles in play — MZCHOOSE picker is shown.
#// With 2 resources (pilot cost 2, not enough for unit cost 3) and TWO empty Vehicles,
#// SWUQueuePilotVehiclePick must show the MZCHOOSE picker (not auto-attach).
#// Player answers with mySpaceArena-1 (the second vehicle); the pilot attaches there.
#// The first vehicle (mySpaceArena-0) remains unmodified (no upgrade).
#// SOR_225 TIE/ln Fighter: 2/1. JTL_035 upgradePower=+2, upgradeHp=+2. Chosen host → 4/3.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SOR_001;
  myBase:SOR_019;
  theirLeader:SOR_001;
  theirBase:SOR_019
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1Hand: JTL_035
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:SOR_225
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_035
P1SPACEARENAUNIT:1:POWER:4
P1SPACEARENAUNIT:1:HP:3
P1HANDCOUNT:0
P1RESAVAILABLE:0
