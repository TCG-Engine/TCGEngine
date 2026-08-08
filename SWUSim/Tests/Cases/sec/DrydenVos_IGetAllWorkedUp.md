# OnAttack_Decline
#// SEC_137 — the double-power is a "may". Declining → SEC_137 deals its base 2 to P2's base.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_137:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:2

---

# OnAttack_DoublePower
#// SEC_137 Dryden Vos (Ground, 2/5) — On Attack: you may double this unit's power for this attack. P1
#//   accepts → SEC_137 deals 2×2 = 4 to P2's base.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_137:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4

---

# DoublePower_NoReadyNextRegroup
#// SEC_137 Dryden Vos — the "if you do" rider: after doubling his power he does NOT ready during the
#//   NEXT regroup phase. He attacks (exhausted) and doubles; through the next regroup he stays exhausted;
#//   the regroup AFTER that he readies normally.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_137:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED


---

# DoublePower_IncludesRaidAndBuffs
#// SEC_137 Dryden Vos — "double this unit's power" doubles his FULL attacking power, including Raid (a
#//   "+X while attacking" value keyword) and stat buffs. With Hondo (SEC_140, grants each other friendly
#//   Raid 1) and Cody (TWI_114, Coordinate gives each other friendly +1/+1 while controlling 3+ units),
#//   Dryden's attacking power is 2 base + 1 (Cody) + 1 (Raid) = 4 → doubled to 8 on the base.
#//   (Regression: previously the double omitted Raid → 7.)

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: [SEC_137:1:0 SEC_140:1:0 TWI_114:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:8

---

# DoublePower_ReadiesAtSecondRegroup
#// SEC_137 Dryden Vos — "doesn't ready during the NEXT regroup" skips EXACTLY ONE regroup. After doubling
#//   he stays exhausted through the next regroup, then readies at the SECOND regroup (the flag is consumed
#//   once). Verifies the no-ready does not over-persist.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_137:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# ReadiedMidActionPhase_AttacksAndDoublesAgain_StillMissesTheRegroup
#// SEC_137 Dryden Vos — the rider only blocks the REGROUP ready step; it does not stop him being readied
#// during the action phase. He attacks and doubles (4 to base, now exhausted), P1 readies him with
#// SOR_169 Keep Fighting ("ready a unit with 3 or less power" — his printed power is 2), and he attacks
#// and doubles a SECOND time (8 total). The rider is still owed, so he stays exhausted through the next
#// regroup phase.

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 4
WithP1Hand: SOR_169
WithP1GroundArena: SEC_137:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Pass
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:8
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# AlreadyReadyWhenRegroupArrives_StaysReady
#// SEC_137 Dryden Vos — "this unit does not ready during the next regroup phase" SKIPS a ready step; it
#// does not exhaust him. If he is readied during the action phase (SOR_169 Keep Fighting) and is still
#// ready when the regroup phase arrives, the skipped ready step costs him nothing and he begins the next
#// action phase READY. Complements the section above, where he was exhausted going into regroup.

## GIVEN
CommonSetup: rrk/rrk
WithP1Resources: 4
WithP1Hand: SOR_169
WithP1GroundArena: SEC_137:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:READY

---

# DoublePower_SevenModifierStack_IncludesAttackOnlyBonuses
#// SEC_137 Dryden Vos — "double this unit's power" means his FULL attacking power at the moment the
#// ability resolves, however that total was assembled. Seven modifiers are stacked on him at once:
#//   base 2, Cody TWI_114 Coordinate +1, Make an Opening SOR_076 −2, First Light SHD_036 Grit +2 (he
#//   carries 2 damage), Surprise Strike SOR_220 +3 for this attack, Hondo SEC_140 Raid 1 and Benthic
#//   SOR_156 Raid 2 — the two Raids summing to 3 and then DOUBLED to 6 by Marchion Ro LOF_186.
#//   2 +1 −2 +2 +3 +6 = 12, doubled → 24 to the base.
#// Board math: Benthic's own opening attack puts 5 on P2's base (2 +1 Cody +2 doubled Raid), then Make
#// an Opening heals P2's base for 2 → 3, so the final total is 3 + 24 = 27.
#// Regression: the +3 "for this attack" bonus lives in a one-shot channel outside ObjectCurrentPower,
#// so a double that reads only ObjectCurrentPower + Raid silently under-counts it (21 instead of 24).

## GIVEN
CommonSetup: yyk/bbw
WithActivePlayer: 1
WithP1Resources: 2
WithP2Resources: 3
WithP1Hand: SOR_220
WithP2Hand: SOR_076
WithP1GroundArena: [LOF_186:1:0 SOR_156:1:0 SEC_140:1:0 TWI_114:1:0 SEC_137:1:2]
WithP1SpaceArena: SHD_036:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-4
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-4
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-4
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:27
