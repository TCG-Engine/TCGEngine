# OnAttackUnderworldDiscount
#// LAW_158 Khetanna (2/4) — When Played/On Attack: the next Underworld unit you play this phase costs 1
#// resource less. Khetanna attacks the base (arming the discount), then LAW_134 (Underworld, cost 2)
#// plays for 1: with 1 ready resource it leaves hand and ends at 0 ready (proving the discount).

## GIVEN
CommonSetup: grk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: LAW_158:1:0
WithP1Hand: LAW_134

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0


---

# OnAttack_UnderworldDiscount_AppliesToSmuggle
#// LAW_158 Khetanna's "next Underworld unit costs 1 less" also reduces an Underworld unit played via
#// SMUGGLE (regression: the Smuggle payment path formerly bypassed all next-unit discounts — Underworld is
#// Smuggle's home faction, so this is the strongest real intersection). Khetanna attacks (arms -1); SHD_113
#// (Privateer Crew, Underworld) has an effective Smuggle cost of 8 here, so on exactly 7 ready resources it
#// plays ONLY because the -1 brings it to 7. (Without the armed discount, 7 resources cannot pay 8.)

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1GroundArena: LAW_158:1:0
WithP1Resources: 6:SOR_046:1,1:SHD_113:1

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SmuggleResource:6

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_113

---

# SmuggleConsumesDiscount_NotLeftArmedForLaterPlay
#// LAW_158 Khetanna arms "next Underworld unit -1"; a SMUGGLE play of an Underworld unit must CONSUME the
#// charge so a later NORMAL Underworld play pays full price (session-88 regression: the Smuggle path left
#// the charge armed to wrongly discount a later play). Khetanna attacks (arms -1); P1 smuggles SHD_113
#// Privateer Crew (Underworld, Smuggle [6 Command]; under grk/bgw its effective Smuggle cost is 8, discounted to 7). P1 has 8
#// ready resources, so after paying 7 exactly 1 remains. Then P1 tries to play LAW_134 Bib Fortuna
#// (Underworld, cost 2) from hand: the charge is spent, so it costs the full 2 > 1 ready → it CANNOT be
#// played and stays out of the arena (only Khetanna + SHD_113 = 2 units). Were the charge wrongly still
#// armed, LAW_134 would cost 1 ≤ 1 and enter (3 units) — so a 2-count here proves consumption.

## GIVEN
CommonSetup: grk/bgw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1GroundArena: LAW_158:1:0
WithP1Resources: 1:SHD_113:1,7:SOR_251:1
WithP1Hand: LAW_134

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SmuggleResource:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_113

---

# WhenPlayed_UnderworldDiscount
#// LAW_158 Khetanna — the SAME "next Underworld unit costs 1 less" also triggers on its WHEN PLAYED (not
#// just On Attack). Khetanna is played from hand (cost 3), arming the discount; then LAW_134 Bib Fortuna
#// (Underworld, cost 2) plays for 1. With exactly 4 ready resources both enter and 0 remain — proving the
#// -1 (without it, LAW_134 would cost 2 > 1 left and could not be played).

## GIVEN
CommonSetup: grk/bgw/{myResources:4}
P1OnlyActions: true
WithP1Hand: [LAW_158 LAW_134]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LAW_134
P1RESAVAILABLE:0

---

# WhenPlayed_NoDiscountNonUnderworld
#// LAW_158 Khetanna — the When Played discount only applies to an UNDERWORLD unit. Khetanna plays (cost 3)
#// arming the charge; then SOR_164 Wampa (Aggression, NOT Underworld, cost 4) plays at full price. Starting
#// from 7 ready resources, 3 are spent on Khetanna and 4 on Wampa, leaving 0 — so Wampa was not discounted
#// (a wrongly-applied -1 would leave 1 ready).

## GIVEN
CommonSetup: grk/bgw/{myResources:7}
P1OnlyActions: true
WithP1Hand: [LAW_158 SOR_164]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_164
P1RESAVAILABLE:0
