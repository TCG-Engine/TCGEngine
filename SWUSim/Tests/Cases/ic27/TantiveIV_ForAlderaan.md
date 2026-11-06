# Aura_OtherFriendlyRebels_GainRaid2AndSaboteur_BothArenas
#// IC27_160 Tantive IV — For Alderaan (5, 3/5 Space, Cunning/Heroism, Rebel·Vehicle·Capital Ship, unique)
#// "Each other friendly Rebel unit gains Raid 2 and Saboteur."
#// PREVIEW card (no official rulings). One continuous clause read live from the board by _SWUIc27160Count, which
#// feeds BOTH keyword readers (GetConditionalKeyword_Raid_Value: +2 per Tantive; HasConditionalKeyword_Saboteur:
#// > 0) so the halves cannot disagree. Shape of IC27_101 General Hux.
#//
#// COVERAGE: offer=N/A (a continuous aura — nothing is chosen, no pool exists) ·
#//           decline=N/A (no "you may") ·
#//           boundary=N/A (no numeric threshold; the trait/side/"other" negatives are
#//           NonRebel_AndEnemyRebel_GetNothing + this section's Tantive-itself assertions) ·
#//           quantity=TeamSuns_TwoTantives_RaidStacks (two sources → Raid 4; Saboteur does not stack) ·
#//           control=Control_StolenRebel_IsFriendly_OwnRebelStolen_IsNot (read by CONTROLLER, not owner) ·
#//           ending=AuraEnds_WhenTantiveIsDefeated · blank=Blanked_Tantive_GrantsNothing ·
#//           reqboundary=N/A (nothing is stored — recomputed from the live board on every read;
#//           EntersLater_PlayedRebel_GetsIt still crosses one SimulateRequestBoundary as a cheap guard) ·
#//           modes=2P,TeamSuns (text says "friendly") · TwinSuns=TwinSuns_FarSeatRebel_IsNotFriendly
#//
#// Tantive (space idx 0) + Alliance X-Wing (2/3 space, Rebel) + Battlefield Marine (3/3 ground, Rebel). Both
#// others gain Raid 2 and Saboteur; Tantive — itself a Rebel — gains neither ("other").

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithP1SpaceArena: IC27_160:1:0    # idx 0 — Tantive IV
WithP1SpaceArena: SOR_237:1:0     # idx 1 — Alliance X-Wing 2/3, Rebel
WithP1GroundArena: SOR_095:1:0    # idx 0 — Battlefield Marine 3/3, Rebel

## WHEN

## EXPECT
P1SPACEARENAUNIT:1:KEYWORDVALUE:Raid:2
P1SPACEARENAUNIT:1:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1SPACEARENAUNIT:0:NOTKEYWORD:Raid
P1SPACEARENAUNIT:0:NOTKEYWORD:Saboteur

---

# NonRebel_AndEnemyRebel_GetNothing
#// Trait and side negatives. P1's Imperial Dark Trooper (Imperial) and P2's Battlefield Marine (Rebel, but ENEMY)
#// gain nothing.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithP1SpaceArena: IC27_160:1:0
WithP1GroundArena: SEC_080:1:0    # Imperial Dark Trooper 3/3
WithP2GroundArena: SOR_095:1:0    # enemy Battlefield Marine

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P2GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# Raid2_AddsToTheAttack
#// The Raid half is real in combat: the Marine (3) attacks the base for 3 + 2 = 5.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_160:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3

---

# Saboteur_IgnoresSentinel_AttacksTheBase
#// The ignore-Sentinel half: P2's Niima Outpost Constables (2/6, Sentinel) carries a Shield. The Marine attacks
#// the BASE straight past her for 5; Niima keeps her Shield (Saboteur only defeats the DEFENDER's Shields).
#// Without Saboteur the base is not a legal target and nothing is dealt.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_160:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_062:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Saboteur_DefeatsTheDefendersShield
#// The shield half: attacking Niima herself, Saboteur defeats her Shield before damage, so all 5 lands (Raid
#// included — 3 + 2) and she survives on 6 HP; her 2 counter-damage hits the Marine.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_160:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_062:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# ValueClass_DeployedRebelLeader_GetsIt
#// A deployed LEADER unit is a friendly Rebel unit too: SOR_005 Luke Skywalker (Force·Rebel) deployed.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:SOR_005:1:1}
SkipPreGame: true
WithP1SpaceArena: IC27_160:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_005
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# AuraEnds_WhenTantiveIsDefeated
#// The ENDING. P1 plays Vanquish (SOR_078, "defeat a non-leader unit") on their own Tantive IV; the Marine loses
#// both keywords.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:SOR_078}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_160:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# Blanked_Tantive_GrantsNothing
#// A Tantive IV that has lost all abilities (SOR_138 marker) grants neither keyword.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithP1SpaceArena: IC27_160:1:0:SOR_138
WithP1GroundArena: SOR_095:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# EntersLater_PlayedRebel_GetsIt
#// A Rebel that enters AFTER Tantive is covered (the aura is not a snapshot). The Marine is played from hand,
#// then read across a request boundary.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_160:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# Control_StolenRebel_IsFriendly_OwnRebelStolen_IsNot
#// "friendly" = CONTROLLED. A Marine OWNED by P2 but controlled by P1 gains both keywords; a Marine OWNED by P1
#// but controlled by P2 gains neither.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithP1SpaceArena: IC27_160:1:0
WithP1GroundArenaControlled: SOR_095:2   # P1 idx 0 — owned by P2, controlled by P1
WithP2GroundArenaControlled: SOR_095:1   # P2 idx 0 — owned by P1, controlled by P2

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P2GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# TeamSuns_TeammatesRebel_GetsIt
#// Team Suns: "friendly" is the TEAM (user ruling 2026-08-25). P1's Tantive covers teammate P3's Marine; the
#// enemies P2 and P4 get nothing.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: IC27_160:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2
P3GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P4GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P4GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# TeamSuns_TwoTantives_RaidStacks
#// Two SOURCES. Uniqueness is per player, so teammates P1 and P3 can each control a Tantive IV. Each is its own
#// "gains Raid 2", and Raid values add (CR 7.5.8.b), so P1's Marine has Raid 4; Saboteur does not stack. Each
#// Tantive is an "other friendly Rebel unit" to the OTHER, so each has Raid 2 + Saboteur from its partner.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: IC27_160:1:0
WithP1GroundArena: SOR_095:1:0
WithP3SpaceArena: IC27_160:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1SPACEARENAUNIT:0:KEYWORDVALUE:Raid:2
P1SPACEARENAUNIT:0:HASKEYWORD:Saboteur
P3SPACEARENAUNIT:0:KEYWORDVALUE:Raid:2
P3SPACEARENAUNIT:0:HASKEYWORD:Saboteur

---

# TwinSuns_FarSeatRebel_IsNotFriendly
#// Twin Suns (4 seats, no teams): seat 3 shares P1's team PARITY but is an opponent. Its Marine gets nothing —
#// "friendly" is the team only in a TEAM game.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: IC27_160:1:0
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2
P3GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P3GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
