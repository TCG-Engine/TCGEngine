# FreeAttach_StrictNoPilot
#// JTL_100 Poe Dameron — free-attach strict "0 pilots" rule.
#// A Vehicle with a Pilot already on it is NOT offered as a free-attach target.
#// Even though the Piloting capacity rule would allow attaching (JTL_249 MF capacity
#// raise), the free-attach clause strictly requires "without a Pilot on it."
#//
#// Setup: SOR_237 (Alliance X-Wing, Space) already piloted by JTL_108 Clone Pilot (a NON-unique pilot —
#// so it occupies the X-Wing without colliding with Poe under the uniqueness rule) + JTL_249 (Millennium
#// Falcon, Space, capacity 2, no pilot).
#//
#// Play JTL_100 Poe (idx 0) AS A UNIT (cost 4):
#//   canUnit = true (4 >= 4); canPilot: SOR_237 count=1 < capacity=1 → false;
#//   JTL_249 count=0 < capacity=2 → canPilot = true for JTL_249 target.
#//   BOTH canUnit and canPilot are true → Unit/Pilot prompt. Player picks "Unit" → Poe enters ground.
#//   WhenPlayed fires:
#//     1. X-Wing token (JTL_T02) created → space idx 2.
#//     2. Free-attach target collection (strict "freeattach" context):
#//        - SOR_237: SWUVehiclePilotCount=1 (JTL_108), NOT === 0 → excluded.
#//        - JTL_249: SWUVehiclePilotCount=0, === 0 → eligible.
#//        - JTL_T02 (Vehicle,Fighter): SWUVehiclePilotCount=0, === 0 → eligible.
#//        Targets = [JTL_249, JTL_T02].
#//     Player declines: AnswerDecision:-
#//
#// This proves the occupied SOR_237 is excluded from free-attach targets while
#// JTL_249 (unoccupied) and JTL_T02 (token, unoccupied) ARE offered.
#//
#// Final state:
#//   Ground: JTL_100 (unit) at idx 0.
#//   Space: SOR_237 (idx 0, upgradeCount=1 — JTL_108 pilot, unchanged), JTL_249 (idx 1, no pilot),
#//          JTL_T02 (idx 2, no pilot).
#//   JTL_249 still has 0 upgrades (free-attach declined).

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 4
WithP1Hand: JTL_100
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: JTL_249:1:0
WithP1SpaceArenaPilot: 0:JTL_108

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unit
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_100
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_108
P1SPACEARENAUNIT:1:CARDID:JTL_249
P1SPACEARENAUNIT:1:UPGRADECOUNT:0
P1SPACEARENAUNIT:2:CARDID:JTL_T02
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# PlayAsPilot_NoToken
#// JTL_100 Poe Dameron — played as a PILOT: no X-Wing token, no pending decision.
#//
#// This guards the no-op WhenPlayedAsUpgrade suppressor:
#// HasWhenPlayedAsUpgradeAbility(JTL_100)=true fires the no-op stub, which prevents
#// the WhenPlayedAsUpgrade->WhenPlayed fallback from ever running the token logic.
#//
#// JTL_100: unit cost 4, piloting cost 2, aspects Command+Heroism.
#// Leader SOR_009 Leia (Command+Heroism) + Base SOR_024 (Command) → 0 aspect penalty.
#// With exactly 2 resources: canUnit=false (2 < 4), canPilot=true (2 >= 2, SOR_237 present).
#// → Pilot-only short-circuit: auto-attaches to the only Vehicle (SOR_237) immediately.
#//
#// WhenPlayedAsUpgrade fires: no-op stub → returns without action.
#// WhenPlayed does NOT fire (JTL_100 entered as Upgrade, not Unit).
#//
#// Final state:
#//   Space arena count: 1 (SOR_237 only — no X-Wing token spawned).
#//   SOR_237 upgradeCount: 1 (JTL_100 as Pilot, IsPilot=true).
#//   SOR_237 power: 2 (base) + 2 (JTL_100 upgradePower) = 4.
#//   SOR_237 hp:    3 (base) + 3 (JTL_100 upgradeHp)    = 6.
#//   No pending decision (P1NODECISION).
#//   JTL_100 is an upgrade on SOR_237, NOT a unit in any arena.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1Hand: JTL_100
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_100
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:6
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1NODECISION

---

# WhenPlayedUnit_TokenAndAttach
#// JTL_100 Poe Dameron — "When played as a unit": creates X-Wing token then player
#// accepts the optional free-attach onto a friendly Vehicle with 0 pilots.
#//
#// JTL_100: cost 4, Command+Heroism, Ground, power 3/hp 3, upgradePower +2, upgradeHp +3.
#// Leader SOR_009 Leia (Command+Heroism) + Base SOR_024 (Command) → 0 aspect penalty.
#//
#// canUnit = true (4 >= 4), canPilot = true (4 >= 2, SOR_237 empty) → Unit/Pilot prompt.
#// Player picks "Unit": JTL_100 enters ground arena at idx 0.
#// WhenPlayed fires (single trigger, no two-trigger ordering):
#//   1. X-Wing token (JTL_T02, Space) created → space arena: SOR_237 (idx 0), JTL_T02 (idx 1).
#//   2. Free-attach MZMAYCHOOSE: SOR_237 is a Vehicle with 0 pilots → 1 target.
#//      Player accepts: AnswerDecision:mySpaceArena-0.
#//   3. JTL_100 is removed from ground arena and attached as Pilot subcard on SOR_237.
#//
#// Final state:
#//   Ground arena count: 0 (JTL_100 left the arena to become an upgrade).
#//   Space arena count: 2 (SOR_237 at idx 0, JTL_T02 at idx 1).
#//   SOR_237 upgradeCount: 1 (JTL_100 as pilot, IsPilot=true).
#//   SOR_237 power: 2 (base) + 2 (JTL_100 upgradePower) = 4.
#//   SOR_237 hp:    3 (base) + 3 (JTL_100 upgradeHp)    = 6.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 4
WithP1Hand: JTL_100
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unit
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_100
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:6
P1SPACEARENAUNIT:1:CARDID:JTL_T02
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# WhenPlayedUnit_TokenDecline
#// JTL_100 Poe Dameron — "When played as a unit": creates X-Wing token, player DECLINES
#// the optional free-attach. JTL_100 stays as a unit; token still created.
#//
#// JTL_100: cost 4, Command+Heroism, Ground, power 3/hp 3.
#// Leader SOR_009 Leia (Command+Heroism) + Base SOR_024 (Command) → 0 aspect penalty.
#//
#// canUnit = true (4 >= 4), canPilot = true (4 >= 2, SOR_237 empty) → Unit/Pilot prompt.
#// Player picks "Unit": JTL_100 enters ground arena at idx 0.
#// WhenPlayed fires:
#//   1. X-Wing token (JTL_T02) created → space: SOR_237 (idx 0), JTL_T02 (idx 1).
#//   2. Free-attach MZMAYCHOOSE with SOR_237 as target.
#//      Player declines: AnswerDecision:-
#//
#// Final state:
#//   Ground arena count: 1 (JTL_100 stays as unit at idx 0).
#//   Space arena count: 2 (SOR_237 at idx 0, JTL_T02 at idx 1).
#//   SOR_237 has no upgrade (free-attach declined).

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 4
WithP1Hand: JTL_100
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unit
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_100
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:JTL_T02
P1HANDCOUNT:0
P1RESAVAILABLE:0
