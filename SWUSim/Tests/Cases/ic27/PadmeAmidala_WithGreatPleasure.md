# Aura_OtherFriendlyHeroismUnits_BothArenas_GetPlusThreePlusThree
#// IC27_083 Padmé Amidala — With Great Pleasure. Unit (Ground), cost 8, [Command][Heroism], 5/7, Naboo/Republic/
#// Official, unique. "Sentinel. Other friendly [Heroism] units get +3/+3."
#// IC27_034 Obi-Wan's Interceptor's aura shape, keyed on the HEROISM ASPECT ICON instead of a trait: recomputed on
#// every read, "other" by UniqueID (Padmé is Heroism herself and stays 5/7), "friendly" = her controller plus, in
#// Team Suns, their teammates, and counted per Padmé. Sentinel is the generated keyword.
#//
#// COVERAGE: offer=N/A (a continuous aura — nothing is chosen) · decline=N/A (no "you may") ·
#//           scope=NonHeroism_AndEnemyHeroism_GetNothing (aspect, side) · class=HeroismToken_GetsIt (a token
#//           with the Heroism icon) · ends=EndsWhenPadmeLeaves_TheUnitSheHeldUpDies (+ the HP sweep) ·
#//           blank=Blanked_GrantsNothing ·
#//           control=Control_StolenHeroismUnit_IsFriendly + Control_StolenPadme_BuffsHerNewControllersUnits ·
#//           reqboundary=N/A (nothing is stored — recomputed from the live board on every stat read) ·
#//           modes=2P, TeamSuns ("friendly": TeamSuns_TeammatesHeroismUnit_GetsIt, TeamSuns_TwoPadmes_Stack),
#//                 TwinSuns_FarSeatHeroismUnit_IsNotFriendly (a free-for-all seat is not friendly)

## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
WithP1GroundArena: IC27_083:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: JTL_251:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:10
P1SPACEARENAUNIT:0:POWER:9
P1SPACEARENAUNIT:0:HP:10

---

# NonHeroism_AndEnemyHeroism_GetNothing
#// TWI_057 (Vigilance) and SOR_128 (Aggression/Villainy) are friendly but have no Heroism icon; P2's SOR_046 has the
#// icon but is an enemy. None of them changes.

## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
WithP1GroundArena: IC27_083:1:0
WithP1GroundArena: TWI_057:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:HP:4
P1GROUNDARENAUNIT:2:POWER:3
P1GROUNDARENAUNIT:2:HP:1
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7

---

# HeroismToken_GetsIt
#// Value class: a token unit with the Heroism icon (TWI_T02 Clone Trooper, 2/2) is a Heroism unit → 5/5. A
#// Villainy token (TWI_T01 Battle Droid, 1/1) is not.

## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
WithP1GroundArena: IC27_083:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_T02
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:2:POWER:1
P1GROUNDARENAUNIT:2:HP:1

---

# EndsWhenPadmeLeaves_TheUnitSheHeldUpDies
#// The aura ends with her. Padmé (6 damage) attacks P2's SOR_046 and dies to its 3; P1's SOR_046 carries 8 damage
#// on 7 printed HP — alive only through her +3 — so it is defeated by the no-remaining-HP check.

## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_083:1:6
WithP1GroundArena: SOR_046:1:8
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# Blanked_GrantsNothing
#// SOR_138 Force Lightning (1 + 4 off-aspect = 5) makes Padmé lose all abilities: her SOR_046 is back to 3/7.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_138
WithP1GroundArena: IC27_083:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:7

---

# Control_StolenHeroismUnit_IsFriendly
#// "Friendly" is read off the CONTROLLER: a P2-owned SOR_046 that P1 controls gets +3/+3; P2's own does not.

## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
WithP1GroundArena: IC27_083:1:0
WithP1GroundArenaControlled: SOR_046:2
WithP2GroundArena: SOR_046:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:10
P2GROUNDARENAUNIT:0:POWER:3

---

# Control_StolenPadme_BuffsHerNewControllersUnits
#// A Padmé that P2 controls (P1 owns her) buffs P2's Heroism units and stops buffing P1's.

## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
WithP2GroundArenaControlled: IC27_083:1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:7
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:HP:10
P2GROUNDARENAUNIT:1:CARDID:IC27_083
P2GROUNDARENAUNIT:1:POWER:5

---

# TeamSuns_TeammatesHeroismUnit_GetsIt
#// Team Suns: "friendly" is the team. Seat 3 (P1's teammate) gets +3/+3; the enemy seats 2 and 4 do not.

## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_083:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:POWER:6
P3GROUNDARENAUNIT:0:HP:10
P2GROUNDARENAUNIT:0:HP:7
P4GROUNDARENAUNIT:0:HP:7

---

# TeamSuns_TwoPadmes_Stack
#// Uniqueness is per player, so P1 and teammate P3 may each have a Padmé. Each is an "other friendly Heroism unit"
#// to the other (5/7 → 8/10), and P1's SOR_046 gets both: 3/7 → 9/13.

## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_083:1:0
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: IC27_083:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HP:10
P3GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:1:POWER:9
P1GROUNDARENAUNIT:1:HP:13

---

# TwinSuns_FarSeatHeroismUnit_IsNotFriendly
#// Three seats, no teams: every other seat is an opponent, so seat 3's Heroism unit gets nothing.

## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1GroundArena: IC27_083:1:0
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:1:POWER:6
P3GROUNDARENAUNIT:0:POWER:3
P3GROUNDARENAUNIT:0:HP:7
