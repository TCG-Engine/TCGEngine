# OnAttack_GivesAnotherFriendlyHeroismUnitRaid2
#// IC27_113 Ezra Bridger (Ground 1/3, cost 2, [Aggression][Heroism]) — "Raid 2. On Attack: Give another friendly
#// Heroism unit Raid 2 for this phase." Ezra attacks P2's base for 1 + Raid 2 = 3; his On Attack gives the only
#// other friendly Heroism unit, the Battlefield Marine (Command/Heroism), Raid 2 — a single legal unit
#// auto-resolves. The Marine then attacks for 3 + 2 = 5. Total 8.
#// Fixture: CommonSetup rrw = Aggression base + Aggression/Heroism leader (Sabine).
#// COVERAGE: offer=Offer_FriendlyHeroismUnits_EzraVillainyAndEnemyExcluded (pending SELECTABLEEXACT: both arenas,
#//           a token, a deployed leader unit) · decline=N/A (mandatory — no "you may") ·
#//           boundary=N/A (no number to straddle; the printed amount is pinned by KEYWORDVALUE and the damage) ·
#//           control=StolenEzra_GrantsToHisControllersUnits ("friendly" = the controller's side) ·
#//           reqboundary=RequestBoundary_PickAnsweredInAFreshRequest
#//           modes=2P,TeamSuns ("friendly" = the team: TeamSuns_TeammatesHeroismUnitGainsRaid2, with
#//                 TwinSuns_AFarSeatHeroismUnitIsNotFriendly pinning "team", not "every seat")

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_113:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:8
P1GROUNDARENAUNIT:1:KEYWORDVALUE:Raid:2
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2
P1NODECISION

---

# Offer_FriendlyHeroismUnitsOnly_EzraVillainyAndEnemyExcluded
#// The pick is left PENDING. Offered: the Marine (ground), a Clone Trooper TOKEN (Heroism), the deployed Sabine
#// (a Heroism LEADER unit) and the Alliance X-Wing (space). Excluded: Ezra himself ("another"), P1's Imperial
#// Dark Trooper (Villainy) and P2's Battlefield Marine (Heroism, but an enemy).
#// P1 ground: Ezra 0 / Marine 1 / Dark Trooper 2 / Clone Trooper 3 / Sabine 4 (the deployed leader seats last).

## GIVEN
CommonSetup: rrw/rrk/{myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: IC27_113:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-3&myGroundArena-4&mySpaceArena-0
P1GROUNDARENAUNIT:4:ISLEADERUNIT

---

# NoOtherFriendlyHeroismUnit_NoPrompt_PrintedRaidStillApplies
#// The only other friendly unit is Villainy, so nothing is offered and nothing gains Raid. Ezra's own printed
#// Raid 2 is unaffected: he deals 3.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_113:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:NOTKEYWORD:Raid
P1NODECISION

---

# Raid2_ExpiresAtTheEndOfThePhase
#// "for this phase": after the regroup the Marine's granted Raid is gone (Ezra's printed Raid stays).

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_113:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P1GROUNDARENAUNIT:1:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2

---

# ASecondGrantStacks_Raid4
#// Raid stacks (every instance is summed — user ruling 2026-09-10). Ezra attacks (Marine gains Raid 2), SHD_182
#// Bravado readies Ezra, Ezra attacks again (Marine gains a SECOND Raid 2 — a second instance, not a refresh of
#// the first). The Marine then has Raid 4 and attacks for 3 + 4 = 7; Ezra dealt 3 twice. P2's base takes 13.
#// (A single shared token would be dropped as a duplicate by AddTurnEffect, leaving Raid 2 and 11 damage.)

## GIVEN
CommonSetup: rrw/rrk/{myResources:5;myhandCardIds:SHD_182}
P1OnlyActions: true
WithP1GroundArena: IC27_113:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:1:KEYWORDVALUE:Raid:4
P2BASEDMG:13

---

# RequestBoundary_PickAnsweredInAFreshRequest
#// Two legal units (the Marine on the ground, the X-Wing in space): the pick is answered after a fresh request.
#// Only the chosen X-Wing gains Raid.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_113:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:KEYWORDVALUE:Raid:2
P1GROUNDARENAUNIT:1:NOTKEYWORD:Raid
P1NODECISION

---

# BlankedEzra_NoRaidAndNoGrant
#// Ezra under SOR_138 Force Lightning ("loses all abilities this phase") has neither his printed Raid nor his On
#// Attack: he deals 1, and the Marine gains nothing.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_113:1:0:SOR_138
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:1:NOTKEYWORD:Raid
P1NODECISION

---

# StolenEzra_GrantsToHisControllersUnits
#// Ezra is OWNED by P2 and controlled by P1. "friendly" is the controller's side: P1's Marine gains Raid 2, P2's
#// own Marine (Heroism, the owner's side) does not.
#// P1 ground: Marine 0 / Ezra 1 (controlled units seat after plain ones).

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaControlled: IC27_113:2
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:IC27_113
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P2BASEDMG:3

---

# TeamSuns_TeammatesHeroismUnitGainsRaid2
#// Team game: seat 3 is P1's teammate. P1 controls no other unit; the teammate's Marine is the only other
#// friendly Heroism unit, so it auto-resolves and gains Raid 2. Seat 2's enemy Marine does not.

## GIVEN
CommonSetup: rrw/rrk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP1GroundArena: IC27_113:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1NODECISION

---

# TwinSuns_AFarSeatHeroismUnitIsNotFriendly
#// Three seats, no teams: seat 3 is an opponent, so its Marine is not friendly — nothing is offered or granted.

## GIVEN
CommonSetup: rrw/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1GroundArena: IC27_113:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0

## EXPECT
SEATCOUNT:3
P3GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1NODECISION
