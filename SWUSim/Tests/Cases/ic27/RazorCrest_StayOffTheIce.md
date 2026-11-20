# EnemyUnitDefeated_ItsWhenDefeatedDoesNotFire
#// IC27_172 Razor Crest — Stay Off the Ice. Unit (Space), cost 3, [Cunning], 2/5, Mandalorian/Vehicle/Transport,
#// unique. "Each unit (including friendly units) loses all "When Defeated" abilities."
#// A constant ability over the whole table. CollectWhenDefeatedTriggers reads it once per defeat batch and treats
#// every defeated unit as having no When Defeated: printed, upgrade-granted, phase-granted, field-granted (ASH_063,
#// TWI_001, SHD_001) and Support-lent alike. It is Last Known Information: a Razor Crest that leaves play in the SAME
#// defeat (a trade, a wipe) still applies (the Bo-Katan's Gauntlet ruling, 07/21/2026, in reverse).
#// CR 7.6.x.2: a "When Played/When Defeated" ability IS a When Defeated ability, so it is lost whole — a unit with one
#// entering under a Razor Crest gets no When Played either. Bounty is a keyword that resolves in the same window as
#// When Defeated abilities (CR 13.c) and is not one; the engine already treats it so (JTL_002 Thrawn, JTL_032).
#// TWI_032 (1/3, "When Defeated: Create a Battle Droid token") is the probe; P2's base is rrk.
#//
#// COVERAGE: offer=N/A (no choice) · decline=N/A (no "you may") ·
#//           negative=WithoutRazorCrest_ItFires + WithoutRazorCrest_TheWhenPlayedFires (the fixture's controls) ·
#//           scope=FriendlyUnitToo · ends=RazorCrestGone_WhenDefeatedFiresAgain · blank=BlankedRazorCrest_ItFires ·
#//           LKI=DefeatedWithRazorCrest_SameCombat + Wipe_RazorCrestDefeatedFirst ·
#//           granted=UpgradeGranted_GrimValor + FieldGranted_BoKatansGauntlet ·
#//           slash=WhenPlayedWhenDefeated_EntersUnderRazorCrest_NoWhenPlayed · bounty=Bounty_StillCollected ·
#//           control=N/A ("each unit" — controller-independent; FriendlyUnitToo + the enemy section cover both sides) ·
#//           reqboundary=N/A (nothing stored — read at each defeat from the live board / the defeat batch) ·
#//           modes=2P, TwinSuns ("each unit" is the whole table: TwinSuns_RazorCrestOnSeatThree);
#//                 TeamSuns N/A — no "friendly"/"enemy" scoping, the same whole-table read

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P2OnlyActions: true
WithP1SpaceArena: IC27_172:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: TWI_032:1:2

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2NODECISION

---

# WithoutRazorCrest_ItFires
#// The control for every section: the same trade with no Razor Crest gives P2 its Battle Droid. (P2 attacks with
#// its own TWI_032 throughout: the When Defeated resolves on its controller's queue, so it is P2 who must be acting
#// for the section to see it resolve.)

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P2OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: TWI_032:1:2

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TWI_T01

---

# FriendlyUnitToo
#// "(Including friendly units)": P1's own TWI_032 dies attacking and makes no Battle Droid for P1.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_172:1:0
WithP1GroundArena: TWI_032:1:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# RazorCrestGone_WhenDefeatedFiresAgain
#// The ability ends with it. Razor Crest (4 damage) dies attacking P2's JTL_069; then P1's TWI_032 dies and its When
#// Defeated is back.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_172:1:4
WithP1GroundArena: TWI_032:1:2
WithP2SpaceArena: JTL_069:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AttackGroundArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01

---

# DefeatedWithRazorCrest_SameCombat_StillSuppressed
#// Last Known Information: P2's TWI_079 (2/1, "When Defeated: Create a Battle Droid token") attacks P1's Razor Crest
#// (3 damage, 2 HP left) and they trade. Razor Crest was in play when TWI_079 was defeated, so TWI_079 had no When
#// Defeated.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P2OnlyActions: true
WithP1SpaceArena: IC27_172:1:3
WithP2SpaceArena: TWI_079:1:0

## WHEN
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
P2NODECISION

---

# Wipe_RazorCrestDefeatedFirst_StillSuppressed
#// SEC_078 Hyperspace Disaster (Vigilance, 7 + 2 = 9) defeats every space unit, one at a time. Razor Crest sits first
#// in P1's space arena, so it leaves before P1's TWI_079 — which still lost its When Defeated, because the wipe is one
#// simultaneous defeat. No Battle Droid.

## GIVEN
CommonSetup: yyw/rrk/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_078
WithP1SpaceArena: IC27_172:1:0
WithP1SpaceArena: TWI_079:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P1NODECISION

---

# BlankedRazorCrest_ItFires
#// A Razor Crest that lost all abilities (SOR_138 Force Lightning, 1 + 4 off-aspect = 5) takes nothing away: P1's
#// TWI_032 dies attacking and makes its Battle Droid.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_138
WithP1SpaceArena: IC27_172:1:0
WithP1GroundArena: TWI_032:1:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01

---

# UpgradeGranted_GrimValor_Suppressed
#// A GAINED When Defeated is lost too: SOR_046 wears JTL_073 Grim Valor ("Attached unit gains: When Defeated: You may
#// exhaust a unit"). It dies attacking, and no exhaust is offered.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_172:1:0
WithP1GroundArena: SOR_046:1:6
WithP1GroundArenaUpgrade: 0:JTL_073
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1NODECISION
P2GROUNDARENAUNIT:0:READY

---

# FieldGranted_BoKatansGauntlet_Suppressed
#// ASH_063 Bo-Katan's Gauntlet: "Each other friendly non-token unit gains: When Defeated: Create a Mandalorian
#// token." Under a Razor Crest the gained ability is lost, so P1's SOR_046 dies and no Mandalorian appears.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_063:1:0
WithP1SpaceArena: IC27_172:1:0
WithP1GroundArena: SOR_046:1:6
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0

---

# WhenPlayedWhenDefeated_EntersUnderRazorCrest_NoWhenPlayed
#// CR: a "When Played/When Defeated" ability is both a When Played and a When Defeated ability. TWI_229 (Villainy,
#// 3 + 2 = 5, "When Played/When Defeated: Create a Battle Droid token") enters under a Razor Crest without it, so
#// its When Played makes no Battle Droid.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TWI_229
WithP1SpaceArena: IC27_172:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_229

---

# WithoutRazorCrest_TheWhenPlayedFires
#// Its control: with no Razor Crest, TWI_229's When Played creates the Battle Droid.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TWI_229

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2

---

# Bounty_StillCollected
#// Bounty is not a "When Defeated" ability. Razor Crest defeats P2's SHD_195 Cartel Turncoat (2/3 with 2 damage,
#// "Bounty - Draw a card"), and P1 collects: one card drawn.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_172:1:0
WithP2SpaceArena: SHD_195:1:2
WithP1Deck: [SEC_080 SEC_080]

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:1
P1NODECISION

---

# TwinSuns_RazorCrestOnSeatThree
#// Three seats, no teams. "Each unit" is the whole table: a Razor Crest on seat 3 takes away seat 2's TWI_032 When
#// Defeated when it dies attacking seat 1. A scan of seats 1 and 2 only would miss the Razor Crest.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP3SpaceArena: IC27_172:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: TWI_032:1:2

## WHEN
- P2>AttackGroundArena:0:p1GroundArena-0

## EXPECT
SEATCOUNT:3
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2NODECISION
