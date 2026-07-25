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
