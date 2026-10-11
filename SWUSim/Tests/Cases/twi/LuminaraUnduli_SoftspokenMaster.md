# Coordinate_GrantsGrit
#// TWI_050 Luminara Unduli (Unit 4/9, Ground) — "Coordinate - Grit." Guard for the already-wired
#// HasConditionalKeyword_Grit case. With 3 friendly units (Coordinate active) and 2 damage on her, Grit
#// gives +1/+0 per damage → power 4+2 = 6, and she reports HASKEYWORD Grit.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_050:1:2
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:6

---

# Coordinate_TwoUnits_NoGrit
#// Boundary partner of Coordinate_GrantsGrit: with only 2 friendly units Coordinate is off, so the same
#// 2-damage Luminara has no Grit and stays at her printed 4 power.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_050:1:2
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:4

---

# WhenPlayed_OffersEveryBase
#// TWI_050 (cost 7, Vigilance/Heroism — printed cost under a bbw Luke setup) — "When Played: Choose a base.
#// Heal 1 damage from it for each unit you control." Unqualified "a base", and mandatory: both bases are
#// offered, no decline.
#// COVERAGE: offer=WhenPlayed_OffersEveryBase, TwinSuns_OffersAndHealsAFarSeatsBase
#//           decline=N/A (no "may" — the base choice is mandatory and always has a target)
#//           boundary=Coordinate_GrantsGrit / Coordinate_TwoUnits_NoGrit (3 vs 2 units)
#//           control=WhenPlayed_StolenUnitCountsForTheController (owner≠controller in the count)
#//           reqboundary=WhenPlayed_HealsAcrossARequestBoundary
#//           modes=2P,TwinSuns ("a base" fans out to every live seat) · TeamSuns=N/A ("you control" is self-only)

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;myBaseDamage:5;theirBaseDamage:5}
P1OnlyActions: true
WithP1Hand: TWI_050

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# WhenPlayed_HealsOwnBase_OnePerUnit
#// 3 units after she enters (Luminara + 2 Clone Trooper tokens) → heal 3 from P1's base: 5 → 2.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: TWI_050
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2
P2BASEDMG:0
P1GROUNDARENACOUNT:3

---

# WhenPlayed_CanHealTheEnemyBase
#// "a base" includes the opponent's: choosing it heals THEIR base by the same count (2 units → 2) and
#// leaves P1's damage alone.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;myBaseDamage:5;theirBaseDamage:5}
P1OnlyActions: true
WithP1Hand: TWI_050
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1BASEDMG:5
P2BASEDMG:3

---

# WhenPlayed_AloneStillCountsHerself
#// Zero-other-units case: she is in play when the ability resolves, so "each unit you control" is 1, not 0.
#// Also pins that the When Played is SEPARATE from Coordinate (only the Grit is "Coordinate -"): with 1 unit
#// Coordinate is off and the heal still happens. Gating the heal on IsCoordinateActive reds this section and
#// every other sub-3-unit section in this file (mutation-checked 2026-10-09).

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: TWI_050

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:4

---

# WhenPlayed_CountsOnlyFriendlyUnits_AcrossBothArenas
#// Quantity discrimination: P1 controls Luminara + a ground token + a SPACE unit = 3; P2's three units do
#// not count. A "both arenas" miss would heal 2, an "all units" reading 6. Base 10 → 7.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;myBaseDamage:10}
P1OnlyActions: true
WithP1Hand: TWI_050
WithP1GroundArena: TWI_T02:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:7

---

# WhenPlayed_DeployedLeaderCounts
#// A deployed leader unit is a unit you control: Luke (deployed) + Luminara = 2 → base 5 → 3.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;myBaseDamage:5;myLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: TWI_050

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1GROUNDARENACOUNT:2
P1BASEDMG:3

---

# WhenPlayed_StolenUnitCountsForTheController
#// "you control" is control, not ownership: a P2-owned unit P1 controls counts for P1 (2 → heal 2), and the
#// owner gets nothing. Base 5 → 3.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: TWI_050
WithP1GroundArenaControlled: SEC_080:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:3

---

# WhenPlayed_HealClampsAtZero
#// 3 units but only 1 damage on the base → the base ends at 0, never negative.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;myBaseDamage:1}
P1OnlyActions: true
WithP1Hand: TWI_050
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:0

---

# WhenPlayed_HealsAcrossARequestBoundary
#// The base pick ends the request; the count is taken when the answer arrives in a fresh process.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: TWI_050
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2

---

# TwinSuns_OffersAndHealsAFarSeatsBase
#// Three-seat free-for-all: "a base" is every live seat's base, so seat 3's is offered and can be healed.
#// 2 units (Luminara + token) → seat 3's base 6 → 4; seat 1's own damage untouched.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;myBaseDamage:5}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP3Base: SOR_021:6
WithP1Hand: TWI_050
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3Base-0

## EXPECT
P3BASEDMG:4
P1BASEDMG:5
