# BuffPerDistinctName
#// JTL_106 Unity of Purpose (event) — For each friendly unit with a different name, give each unit you
#// control +1/+1 this phase. Three distinctly-named units (SOR_095, SOR_046, SOR_237) → N=3, so every
#// friendly unit gets +3/+3.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_106
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:10
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:5

---

# SameNameDedup
#// JTL_106 Unity of Purpose — two units with the SAME name count as one distinct name. The +1/+1 applies
#// for every DIFFERENT friendly unit name. P1 controls two "Millennium Falcon"
#// units (SOR_193 and JTL_249, same title / different subtitle) plus SOR_095 Battlefield Marine. Distinct
#// names = {Millennium Falcon, Battlefield Marine} = 2, so every friendly unit gets +2/+2 (NOT +3). Each
#// Falcon 3/4 → 5/6; Battlefield Marine 3/3 → 5/5.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_106
WithP1Resources: 6
WithP1SpaceArena: SOR_193:1:0
WithP1SpaceArena: JTL_249:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:6
P1SPACEARENAUNIT:1:CARDID:JTL_249
P1SPACEARENAUNIT:1:POWER:5
P1SPACEARENAUNIT:1:HP:6
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# OpponentUnitsNotBuffed
#// JTL_106 Unity of Purpose — only units "you control" are buffed; the enemy unit stays at its printed
#// stats. P1 controls one friendly unit (SOR_095, distinct names = 1 → +1/+1),
#// while P2 controls SOR_164 Wampa. P1's SOR_095 becomes 4/4; the enemy Wampa is untouched at its printed
#// 4/5.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_106
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:5

---

# SnapshotLaterUnitNoBuff
#// JTL_106 Unity of Purpose — the buff is a SNAPSHOT at resolution; the modifier does not change with the
#// number of units. P1 controls SOR_237 (distinct names = 1 → +1/+1 → 3/4)
#// when the event resolves. P1 then plays SOR_095 Battlefield Marine AFTER: it receives NO buff (stays
#// 3/3), and SOR_237 keeps its +1/+1 even though a second distinct name now exists (the snapshot does not
#// recompute). Aspects ggw cover JTL_106 (Command) + SOR_095 (Command/Heroism).

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [JTL_106 SOR_095]
WithP1Resources: 10
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# BuffExpiresNextPhase
#// JTL_106 Unity of Purpose — the +1/+1 lasts only "for this phase"; on the next action phase the stats
#// revert. P1 controls SOR_095 (distinct names = 1 → +1/+1 → 4/4). After
#// both players pass (action phase ends → regroup runs the centralized turn-effect expiry), the buff is
#// gone and SOR_095 is back to its printed 3/3.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_106
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# SimulateRequestBoundary_SnapshotBuffSurvives
#// JTL_106 Unity of Purpose — the event raises no decision, but production ends the request at every PLAY
#// action, so the phase-scoped +N/+N snapshot is written by one action and read by the next in a fresh
#// process. Mirrors SnapshotLaterUnitNoBuff with the boundary inserted between the two plays: SOR_237 must
#// still carry its +1/+1 (3/4) after the round-trip, and the later SOR_095 must still receive nothing.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [JTL_106 SOR_095]
WithP1Resources: 10
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
