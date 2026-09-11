# SmuggledUnit_HasAPlayLine_AndItsAbilityIsAttributed
#// Game-log follow-up (2026-09-11). Only ActivateCard wrote a play line, and a smuggled UNIT is placed
#// inline (_SWUSmugglePlaceUnit) — it entered play with no line at all, and its "When played using Smuggle"
#// closure (fired directly, never through OnWhenPlayed) had no source. (Fixture from shd/PrivateerCrew.md.)
#// SHD_113 Privateer Crew: "When played using Smuggle: Give 3 Experience tokens to this unit."

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1Resources: 6:SOR_046:1,1:SHD_113:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:6

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_113
LOGCONTAINS:P1 played [[SHD_113|Privateer Crew]] using Smuggle
LOGCOUNT:3:P1's [[SHD_113|Privateer Crew]] gave an Experience token to itself

---

# SmuggledEvent_OneLine_SaysSmuggle
#// A smuggled EVENT delegates to ActivateCard, which writes the line — tagged "using Smuggle", and only once.
#// (Fixture from shd/Commission.md.) SHD_127 Commission: search the top 10 for a card with a listed trait.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1Resources: 1:SHD_127:0,4:SOR_095:1
WithP1Deck: [SOR_095 SOR_204 SEC_080]

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:SOR_204

## EXPECT
P1HANDCARD:0:SOR_204
LOGCONTAINS:P1 played [[SHD_127|Commission]] using Smuggle
LOGCOUNT:1:played [[SHD_127

---

# Plot_OneLine_SaysPlot
#// Plot used to write TWO lines ("P1 plays X using Plot" at the pick, then ActivateCard's "P1 played X").
#// Now ActivateCard's single line carries "using Plot". (Fixture from sec/OneInAMillion.md.)

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SOR_005;myResources:5}
P1OnlyActions: true
WithP1Resources: 1:SEC_053:1
WithP2GroundArena: SOR_037:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-5
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
LOGCONTAINS:P1 played [[SEC_053|One in a Million]] using Plot
LOGCOUNT:1:[[SEC_053|One in a Million]] using Plot
LOGCOUNT:1:played [[SEC_053

---

# OwnDiscardUnit_HasAPlayLine
#// A unit played from its owner's discard is also placed inline (_SWUOwnDiscardPlayAsUnit) — no line before.
#// JTL_221 Stolen AT-Hauler: "When Defeated: an opponent may play this unit from your discard pile for
#// free this phase." (Fixture from jtl/StolenAthauler.md, StealBackAndForth.)

## GIVEN
CommonSetup: grw/yrw
WithP1SpaceArena: JTL_221:1:3
WithP1SpaceArena: JTL_153
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P2>PlayFromOpponentDiscard:0
- P1>AttackSpaceArena:0:0
- P2>Claim
- P1>PlayFromDiscard:0

## EXPECT
LOGCONTAINS:P2 played [[JTL_221|Stolen AT-Hauler]] from P1's discard pile
LOGCONTAINS:P1 played [[JTL_221|Stolen AT-Hauler]] from their discard pile

---

# Upgrade_NamesItsHost
#// The play line is written when the play commits, BEFORE the host is chosen — so an upgrade's host was
#// never named. _SWUFinalizeUpgradeAttach now adds where it went. SOR_053 Luke's Lightsaber (upgrade; one host
#// auto-resolves). (Fixture from sor/LukesLightsaber.md.)

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_053}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
LOGCONTAINS:P1 played [[SOR_053|
LOGCONTAINS:[[SOR_053|Luke's Lightsaber]] was attached to P1's [[SEC_080|Imperial Dark Trooper]]
LOGCOUNT:1:played [[SOR_053

---

# Pilot_NamesItsVehicle
#// A Pilot played from HAND never reached ActivateCard's commit point (the Unit-vs-Pilot fork leaves before
#// it), so it had NO play line at all. The attach step writes it — once — with the Vehicle.
#// JTL_084 Wingman Victor Two (Piloting). (Fixture from jtl/WingmanVictorTwo_MaulerMithel.md.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_084
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
LOGCONTAINS:P1 played [[JTL_084|Wingman Victor Two]] as a pilot on P1's [[SOR_225|
LOGCOUNT:1:played [[JTL_084
