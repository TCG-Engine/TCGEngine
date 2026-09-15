# Omega_HerOwnPlayIsTheFirstCloneUnit
#// CR 8.8.1: "the first" occurrence in a round is the very first one, "not the first after an ability
#// becomes active". SHD_198 Omega is herself a Clone unit, so playing her IS the first Clone unit P1 plays
#// this round — the Coruscant Guard (TWI_106, Command Clone) played next is the second and pays its aspect
#// penalty (2 + 2 = 4). P1 is yyw (Cunning base + Han Solo), so Omega costs her printed 2: 6 → 4 → 0.
#// The flag was only recorded while Omega was already in play, so the Guard came out at 2. (Her search
#// finds no Clone in a deck of Marines and is passed.)
## GIVEN
CommonSetup: yyw/rrk/{myResources:6;handCardIds:SHD_198,TWI_106}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:4

---

# Omega_TheCloneAfterHerPaysThePenalty
#// The same board continued: after Omega's own play, the Coruscant Guard costs 4 (penalty NOT ignored).
## GIVEN
CommonSetup: yyw/rrk/{myResources:6;handCardIds:SHD_198,TWI_106}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0

---

# Omega_AlreadyInPlay_TheFirstCloneIgnoresThePenalty
#// Control for the two sections above: with Omega already in play at the start of the round, the first
#// Clone unit P1 plays (the Guard) does ignore its penalty — 2, not 4.
## GIVEN
CommonSetup: yyw/rrk/{myResources:4;handCardIds:TWI_106}
P1OnlyActions: true
WithP1GroundArena: SHD_198:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:2

---

# Codebreaker_AGambitPlayedBeforeHimWasTheFirst
#// CR 8.8.1 again, for LAW_229 The Master Codebreaker ("The first Gambit card you play each round costs 1
#// resource less"). P1 plays You're All Clear, Kid (JTL_055, a Gambit event — no enemy space unit, so it
#// does nothing; 2 + 2 Vigilance penalty = 4), THEN the Codebreaker (2), then a second JTL_055. The second
#// Gambit is not the first this round, so it costs the full 4: 10 → 6 → 4 → 0. The "first Gambit" flag was
#// only recorded while a Codebreaker was in play, so the second came out at 3.
## GIVEN
CommonSetup: yyw/rrk/{myResources:10;handCardIds:JTL_055,LAW_229,JTL_055}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:0

---

# Congress_AnUpgradePlayedBeforeItWasTheFirst
#// CR 8.8.1 for SEC_064 Congress of Malastare ("The first upgrade you play each phase costs 1 resource
#// less"). P1 (bbw: Vigilance base + Luke) plays Academy Training (SOR_120, Command: 2 + 2 = 4) on its
#// Battlefield Marine, THEN Congress (5), then a second Academy Training. The second upgrade is not the
#// first this phase, so it costs the full 4: 13 → 9 → 4 → 0. The flag was only set while a Congress was
#// in play, so it came out at 3.
## GIVEN
CommonSetup: bbw/rrk/{myResources:13;handCardIds:SOR_120,SEC_064,SOR_120}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1RESAVAILABLE:0

---

# Congress_TwoCopiesStack
#// CR 1.8.3: "Any modifiers to a card's cost are cumulative." Congress of Malastare is not unique; with two
#// in play, each one's "first upgrade costs 1 less" applies to the same first upgrade. Academy Training
#// (4 for bbw) costs 2: 4 → 2. One discount was applied however many copies there were (→ 1).
## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:SOR_120}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_064:1:0
WithP1GroundArena: SEC_064:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:2

---

# PitDroidTeam_TwoCopiesStack
#// CR 1.8.3 for ASH_075 Pit Droid Team ("The first upgrade you play on another friendly unit each phase
#// costs 1 resource less"), not unique. Two in play, the upgrade goes on the Marine (another friendly unit
#// for BOTH): Academy Training costs 4 - 2 = 2.
## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:SOR_120}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: ASH_075:1:0
WithP1GroundArena: ASH_075:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:2

---

# PitDroidTeam_OnOneOfThem_OnlyTheOtherApplies
#// The "another friendly unit" half, per copy: the upgrade goes on Pit Droid Team A, so A's own discount
#// does not apply (A is not "another" unit to itself) but B's does — 4 - 1 = 3.
## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:SOR_120}
P1OnlyActions: true
WithP1GroundArena: ASH_075:1:0
WithP1GroundArena: ASH_075:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:1

---

# DeathStarPlans_TwoCopiesStack
#// CR 1.8.3 for JTL_260 Death Star Plans (attached unit gains "The first unit you play each round costs 2
#// resources less"). Two friendly units each carry a copy, so two instances apply to the first unit: the
#// Battlefield Marine (2 + 2 Command penalty = 4 for bbw) costs 0. One discount was applied (→ 2 spent).
## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:JTL_260
WithP1GroundArenaUpgrade: 1:JTL_260
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:4
