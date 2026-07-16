# Decline_TakesCounterNormally
#// SHD_090 Maul — declining the optional redirect leaves combat normal: Maul takes the 2 counter itself.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [SHD_090:1:0 LAW_124:1:0]
WithP2GroundArena: SOR_181:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_090
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:CARDID:LAW_124
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# NoUnderworldUnit_NoOffer
#// SHD_090 Maul — with no OTHER friendly Underworld unit, there's no redirect offer (no decision), and Maul
#// takes the counter normally. SOR_095 (Rebel, non-Underworld) is not a valid target.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [SHD_090:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_181:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_090
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# RedirectDefeatsChosenUnit
#// SHD_090 Maul — the redirected combat damage can DEFEAT the chosen unit. SOR_247 Underworld Thug (2/3)
#// is pre-damaged 1 (2 remaining). Maul attacks Jabba (counter 2); redirected to the Thug → 1+2 = 3 ≥ 3 HP,
#// so the Thug is defeated (to P1's discard) while Maul stays unharmed.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [SHD_090:1:0 SOR_247:1:1]
WithP2GroundArena: SOR_181:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_090
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1

---

# RedirectToShieldedUnit_Absorbed
#// SHD_090 Maul — redirected damage is COMBAT damage, so the chosen unit's own Shield absorbs it. LAW_124
#// has a Shield; the redirected 2 is absorbed (LAW_124 undamaged, shield gone), Maul takes 0.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [SHD_090:1:0 LAW_124:1:0]
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArena: SOR_181:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:LAW_124
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0

---

# RedirectToSurvivingUnit
#// SHD_090 Maul (Unit, Ground, cost ?, 7/6, Ambush, Overwhelm, Force/Underworld)
#//   "On Attack: You may choose another friendly Underworld unit. If you do, all combat damage that would be
#//    dealt to this unit during this attack is dealt to the chosen unit instead."
#// Maul (7 power) attacks SOR_181 Jabba (2/8, survives, deals 2 counter). P1 redirects the counter to the
#// friendly Underworld unit LAW_124 (4/7): Maul takes 0, LAW_124 takes the 2, Jabba takes Maul's 7.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [SHD_090:1:0 LAW_124:1:0]
WithP2GroundArena: SOR_181:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_090
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:LAW_124
P1GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:7
