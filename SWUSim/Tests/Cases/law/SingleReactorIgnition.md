# DefeatAllDamageBase
#// LAW_044 Single Reactor Ignition (Vigilance,Aggression,Villainy event, cost 8) — "Defeat all units.
#// For each enemy unit defeated this way, deal 1 damage to its controller's base." P1 has 1 own unit,
#// P2 has 2 -> all 3 defeated; 2 enemy units => 2 damage to P2's base.

## GIVEN
CommonSetup: rrk/bgw/{myResources:10}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_044

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P2BASEDMG:2
P1DISCARDCOUNT:2
P2DISCARDCOUNT:2

---

# UpgradeNotCounted
#// LAW_044 Single Reactor Ignition — only defeated UNITS count for base damage, not their upgrades. P2 has
#// one Wampa wearing Nimble Prowess; both are defeated but the base takes just 1 damage (the unit).

## GIVEN
CommonSetup: rrk/bgw/{myResources:10}
WithP2GroundArena: SOR_164:1:0
WithP2GroundArenaUpgrade: 0:SEC_069
WithP1Hand: LAW_044

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1
P2DISCARDCOUNT:2

---

# PilotUpgradeNotCounted
#// LAW_044 Single Reactor Ignition — a Pilot attached as an upgrade is not a unit, so it does not add base
#// damage. P2's Cartel Spacer carries a piloting Indoctrinated Conscript; the spacer is defeated for 1 base
#// damage and the pilot upgrade is discarded without counting.

## GIVEN
CommonSetup: rrk/bgw/{myResources:10}
WithP2SpaceArena: SOR_178:1:0
WithP2SpaceArenaUpgrade: 0:JTL_236
WithP1Hand: LAW_044

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:1
P2DISCARDCOUNT:2

---

# CancelledUnitNotCounted
#// LAW_044 Single Reactor Ignition — a unit that can't be defeated by enemy card abilities (Lurking TIE
#// Phantom SHD_187) is not defeated by this event, so it does not count for base damage. P2 also has a
#// Wampa (SOR_164) which IS defeated: base takes just 1 damage and the TIE Phantom survives.

## GIVEN
CommonSetup: rrk/bgw/{myResources:10}
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SHD_187:1:0
WithP1Hand: LAW_044

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2BASEDMG:1

---

# TwinSuns_EachControllersOwnBaseTakesItsOwnCount
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — §1b, and this card carried THREE defects at once:
#//   1. "enemy" was OtherPlayer($player), i.e. seat 2 — a seat-4 unit was not an enemy at all;
#//   2. the defeat pool was my+their, and in a TEAM game 'their' EXCLUDES a teammate, so "defeat ALL
#//      units" left P3's board standing;
#//   3. "deal 1 damage to ITS CONTROLLER's base" is a determined PER-UNIT seat, but every point went to
#//      the single $opp base.
#//
#// Board: P1 (caster) 1 unit · teammate P3 1 unit · enemy P2 2 units · enemy P4 1 unit.
#// ALL FIVE units are defeated — the teammate's included, which is the clause-2 assertion.
#// P2's base takes exactly 2 and P4's exactly 1 — asymmetric on purpose, so a fix that pooled the count
#// or sent it to one base cannot pass. P3 is a teammate, so its defeated unit deals NO base damage.

## GIVEN
CommonSetup: rrk/bgw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 10
WithP1Hand: LAW_044
WithP1GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP4GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P4GROUNDARENACOUNT:0
P2BASEDMG:2
P4BASEDMG:1
P3BASEDMG:0
P1BASEDMG:0
