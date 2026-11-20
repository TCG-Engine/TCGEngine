# A1_UnpreventableAttacker_IntoShield
#// CR 8.36 / CR 7.4.13: "If damage is unpreventable, then that damage must be dealt. When dealing unpreventable damage,
#// ignore any abilities that would prevent that damage." ASH_196 Gorian Shard's Corsair: "Damage dealt by friendly
#// Underworld cards is unpreventable."
#// This file walks EVERY combat-damage prevention against unpreventable combat damage, on both sides of an attack:
#//   A — the ATTACKER deals unpreventable damage: P1 controls the Corsair (space) and LAW_124 Industrious Team (Underworld,
#//       4/7), which attacks a PROTECTED P2 defender. The defender must take all 4.
#//   B — the DEFENDER deals unpreventable damage: P2 controls the Corsair and LAW_124 (4/7), and a PROTECTED P1 unit attacks
#//       it. The attacker must take the full 4-damage counter-hit.
#//   A10/A11, C1–C4 — the other two combat orderings (Shoot First, defender-first), each with its own copy of both checks:
#//       the attacker's unpreventable damage (A10/A11) and the counter-hit (C1/C2 through Brothers' flag, C3/C4 through a
#//       Shield — the flag sections cannot see the Shield-path branch, and vice versa).   D — TWI_135 Maul's two-defender path.
#//   K — controls: the same protections still prevent ordinary (preventable) damage.
#// The two interactive preventions (SEC_101 Queen Amidala, ASH_062 The Mandalorian) cost something to use, so with
#// unpreventable incoming damage they must not be offered at all — paying could only fizzle.
#// COVERAGE: offer=A2/A3/B6/B7 (no prompt) + K2 (still offered on preventable damage) · decline=N/A (no choice is made) ·
#//           boundary=N/A (all-or-nothing: unpreventable vs not, pinned by the K controls) · control=N/A (seat-fixed boards) ·
#//           reqboundary=N/A (each section is one synchronous attack) · modes=2P only (no player reference)
#// A1: a Shield token on the defender is ignored — the Consular Security Force takes 4 and keeps its Shield.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# A2_UnpreventableAttacker_IntoQueenAmidala_NoOffer
#// SEC_101 Queen Amidala (5/3) defends; her controller holds a trait-sharing friendly (SEC_118 Raxian Assembly, Official).
#// Her prevention could only fizzle, so it is NOT offered: no unit is sacrificed, and she takes 4 and is defeated.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_118:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2NODECISION
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_118

---

# A3_UnpreventableAttacker_IntoTheMandalorianProtection_NoOffer
#// ASH_062 The Mandalorian protects "another friendly unit" by spending his Shield. With unpreventable damage incoming that
#// can only fizzle, so it is NOT offered: the Security Force takes 4 and the Mandalorian keeps his Shield.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: ASH_062:1:0
WithP2GroundArenaUpgrade: 1:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# A4_UnpreventableAttacker_IntoShienFlurryPrevent2
#// LOF_220 Shien Flurry's "the next time it would be dealt damage this phase, prevent 2" (marker PREVENT_DMG_2): ignored.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0:PREVENT_DMG_2

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# A5_UnpreventableAttacker_IntoUmbaranMobileCannon
#// SEC_067 Umbaran Mobile Cannon (7/3): "The first time this unit would take damage each phase, prevent that damage."
#// Ignored — it takes 4 and is defeated.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SEC_067:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0

---

# A6_UnpreventableAttacker_IntoVigilsPreventOne
#// SEC_050 Vigil: "If damage would be dealt to another friendly unit, prevent 1 of that damage." Ignored — 4, not 3.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_050:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# A7_UnpreventableAttacker_IntoBobaFettsArmor
#// SHD_224 Boba Fett's Armor on IC27_146 Boba Fett (4/7): "prevent 2 of that damage." Ignored — 4, not 2.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: IC27_146:1:0
WithP2GroundArenaUpgrade: 0:SHD_224

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# A8_UnpreventableAttacker_IntoNuma
#// HMW_088 Numa (4/4): "If this unit would be dealt damage, prevent 1 of that damage." Ignored — she takes 4 and is
#// defeated.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: HMW_088:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0

---

# A9_UnpreventableAttacker_IntoFinnsPreventOne
#// TWI_053 Finn's "for this phase, prevent 1 of damage dealt to that unit" (marker TWI_053): ignored — 4, not 3.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0:TWI_053

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# A10_ShootFirst_UnpreventableAttacker_IntoShield
#// Shoot First ordering: the Industrious Team (SHOOT_FIRST marker) deals its 4 first, unpreventably, through the Security
#// Force's Shield; the Security Force survives and counters for 3 (preventable — nothing protects the Team anyway).

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: LAW_124:1:0:SHOOT_FIRST
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# A11_DefenderFirst_UnpreventableAttacker_IntoShield
#// Defender-first ordering: IC27_142 Greedo (Underworld, 5/3) deals his damage AFTER the defender. The shielded Cloud City
#// Wing Guard (2/4) hits him for 2 (he survives), then his 5 lands unpreventably through the Shield and defeats it.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: IC27_142:1:0
WithP2GroundArena: SOR_063:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# B1_UnpreventableCounter_IntoAaylaSecura
#// TWI_096 Aayla Secura (6/5) with Coordinate active (three units): "prevent all combat damage that would be dealt to this
#// unit for this attack." She attacks P2's Industrious Team; its counter-hit is unpreventable — she takes 4.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: TWI_096:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# B2_UnpreventableCounter_IntoIHaveYouNow
#// JTL_193 I Have You Now: "Attack with a Vehicle unit. Prevent all damage that would be dealt to it during this attack."
#// P1's Alliance X-Wing (2/3) attacks Gorian Shard's Corsair itself (Underworld, 6/5 — its own damage is unpreventable):
#// the X-Wing takes 6 and is defeated.

## GIVEN
CommonSetup: yyk/ggw/{myResources:1;myhandCardIds:JTL_193}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: ASH_196:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENAUNIT:0:DAMAGE:2

---

# B3_UnpreventableCounter_IntoBrothers
#// TS26_59 Brothers' "prevent all combat damage that would be dealt to each of them for these attacks" (marker
#// TS26_59, attack duration): the Security Force takes the 4 counter-hit.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0:TS26_59@attack
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# B4_UnpreventableCounter_IntoYodaMarkedAttacker
#// IC27_035 Yoda's marker on the attacker: combat damage TO it is prevented — except unpreventable damage, so it takes 4.
#// Damage BY it is still prevented (it is not Underworld): the Industrious Team takes 0.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0:IC27_035
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# B5_UnpreventableCounter_IntoShield
#// A Shield token on the attacker is ignored by the counter-hit: 4 damage, Shield kept.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# B6_UnpreventableCounter_IntoQueenAmidala_NoOffer
#// Queen Amidala (5/3) attacks; her prevention could only fizzle against the counter-hit, so it is NOT offered: SEC_118
#// survives and Amidala takes 4 and is defeated.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SEC_101:1:0
WithP1GroundArena: SEC_118:1:0
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_118
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# B7_UnpreventableCounter_IntoTheMandalorianProtection_NoOffer
#// P1's Security Force attacks; P1's Mandalorian could spend his Shield to protect it, but only to fizzle — NOT offered.
#// The Security Force takes 4, the Mandalorian keeps his Shield.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# B8_UnpreventableCounter_IntoShienFlurryPrevent2

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0:PREVENT_DMG_2
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# B9_UnpreventableCounter_IntoUmbaranMobileCannon
#// The Umbaran Mobile Cannon (7/3) attacks: its first-damage prevention is ignored, it takes 4 and is defeated (its 7
#// also defeats the Industrious Team — simultaneous).

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SEC_067:1:0
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# B10_UnpreventableCounter_IntoVigilsPreventOne

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SEC_050:1:0
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# B11_UnpreventableCounter_IntoBobaFettsArmor

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: IC27_146:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# B12_UnpreventableCounter_IntoNuma
#// Numa (4/4) attacks: her prevent-1 is ignored, she takes 4 and is defeated.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: HMW_088:1:0
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0

---

# B13_UnpreventableCounter_IntoFinnsPreventOne

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0:TWI_053
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# C1_ShootFirst_UnpreventableCounter_IntoBrothers
#// Shoot First ordering (SHOOT_FIRST marker): the Security Force deals its 3 first, the Industrious Team survives and
#// counters — unpreventably, through the Brothers protection: 4.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0:SHOOT_FIRST~TS26_59@attack
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# C2_DefenderFirst_UnpreventableCounter_IntoBrothers
#// Defender-first ordering: IC27_142 Greedo (5/3) attacking deals his damage AFTER the defender. The Industrious Team's
#// 4 is unpreventable, goes through the Brothers protection and defeats Greedo first — so he deals nothing.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: IC27_142:1:0:TS26_59@attack
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# C3_ShootFirst_UnpreventableCounter_IntoShield
#// Shoot First ordering with a Shield on the attacker (the Shield is prevented in the shared Shield path, which C1's
#// Brothers protection never reaches): the Industrious Team survives the first strike and its 4 lands through the Shield.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0:SHOOT_FIRST
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# C4_DefenderFirst_UnpreventableCounter_IntoShield
#// Defender-first ordering with a Shield on the attacker: the Industrious Team's 4 lands first through Greedo's (5/3)
#// Shield and defeats him, so he deals nothing.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: IC27_142:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# D1_MaulTwoDefenders_UnpreventableCounters_IntoShield
#// TWI_135 Darth Maul (5/6) with a Shield attacks two Underworld defenders (Industrious Team 4/7, Underworld Thug 2/3)
#// while their controller has the Corsair: the combined 6 counter is unpreventable — the Shield does not stop it and
#// Maul is defeated. (He deals 5 to each: the Thug dies, the Team survives.)

## GIVEN
CommonSetup: rrk/ggw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: [LAW_124:1:0 SOR_247:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# K1_Control_BrothersStillPreventsAPreventableCounter
#// Same as B3 without the Corsair: the counter-hit is preventable, so the Brothers protection holds — 0.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0:TS26_59@attack
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# K2_Control_AmidalaStillOfferedAgainstPreventableDamage
#// Same as A2 without the Corsair: the Industrious Team's damage is preventable, so Amidala's prevention IS offered.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_118:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2HASDECISION
P2GROUNDARENACOUNT:2
