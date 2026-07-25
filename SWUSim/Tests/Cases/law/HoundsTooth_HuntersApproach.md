# AttackEndDefeatLowerPower
#// LAW_033 Hound's Tooth (8/8, space) — When Attack Ends: if this unit survived, you may defeat a unit
#// with less power than this unit. Attacks the base and survives; defeat the enemy SOR_046 (power 3 < 8).

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_033:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# AttackEnd_MayDeclineDefeat
#// LAW_033 Hound's Tooth (4/8, space) — the after-attack defeat is optional ("you may"). Hound's Tooth
#// attacks the enemy base and survives; the player DECLINES the defeat, so every lower-power unit stays.
#// Board: friendly SOR_141 Green Squadron A-Wing (1) + Hound's Tooth in space, SOR_164 Wampa (4) on ground;
#// enemy SOR_232 AT-ST (6) / SOR_045 Yoda (2) ground, SEC_213 A-Wing (1) / SOR_040 Avenger (8) / SOR_185
#// Chimaera space. Nothing is defeated.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArena: LAW_033:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_045:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_040:1:0
WithP2SpaceArena: SOR_185:1:0

## WHEN
- P1>AttackSpaceArena:1:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2BASEDMG:4
P1SPACEARENACOUNT:2
P2SPACEARENACOUNT:3
P2GROUNDARENACOUNT:2

---

# AttackEnd_DefeatLowerPower_TargetUnit
#// LAW_033 Hound's Tooth (4/8) — attacks an enemy UNIT (SEC_213 A-Wing, 1/2), survives (takes 1), and its
#// after-attack ability defeats a unit with less power than its 4: SOR_045 Yoda (2). The combat-defeated
#// A-Wing and Yoda both leave; Hound's Tooth remains. (SOR_164 Wampa at power 4 is NOT eligible — 4 is not
#// less than 4 — nor are AT-ST/Avenger/Chimaera.)

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArena: LAW_033:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_045:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_040:1:0
WithP2SpaceArena: SOR_185:1:0

## WHEN
- P1>AttackSpaceArena:1:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1SPACEARENACOUNT:2
P2SPACEARENACOUNT:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232

---

# AttackEnd_NoDefeatIfKilledWhileAttacking
#// LAW_033 Hound's Tooth (4/8) — the ability requires the attacker to survive. Hound's Tooth attacks
#// SOR_040 Avenger (8/8): it deals 4 (Avenger survives) but takes 8 and is defeated, so no after-attack
#// defeat happens and no decision is offered.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArena: LAW_033:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_045:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_040:1:0
WithP2SpaceArena: SOR_185:1:0

## WHEN
- P1>AttackSpaceArena:1:1

## EXPECT
P1NODECISION
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:3
P2GROUNDARENACOUNT:2

---

# AttackEnd_NoDefeatIfKilledEvenAfterKillingDefender
#// LAW_033 Hound's Tooth (4/8) — even when Hound's Tooth defeats its target in combat, it does NOT get the
#// after-attack defeat if it is itself defeated. It attacks a pre-damaged SOR_185 Chimaera (8/7 with 6
#// damage = 1 HP left): Hound's Tooth deals 4 (Chimaera defeated) and takes 8 (Hound's Tooth defeated).
#// Both leave; no after-attack defeat, no decision.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArena: LAW_033:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_045:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_040:1:0
WithP2SpaceArena: SOR_185:1:6

## WHEN
- P1>AttackSpaceArena:1:2

## EXPECT
P1NODECISION
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:2

---

# AttackEnd_DefeatThreshold_PowerFromUpgrades
#// LAW_033 Hound's Tooth (4/8) — the "less power than this unit" threshold uses its LIVE power. With two
#// TWI_155 Twice the Pride (+4/+0 each), Hound's Tooth is power 12, so it can now defeat SOR_040 Avenger
#// (power 8 < 12) after attacking the base and surviving. (SEC_213 A-Wing at power 1 is also eligible.)

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_033:1:0
WithP1SpaceArenaUpgrade: 0:TWI_155
WithP1SpaceArenaUpgrade: 0:TWI_155
WithP2SpaceArena: SOR_040:1:0
WithP2SpaceArena: SEC_213:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:12
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SEC_213

---

# AttackEnd_DefeatThreshold_PowerFromAbilityThisAttack
#// LAW_033 Hound's Tooth (4/8) — the threshold tracks a temporary attack-only power boost. TWI_012 Anakin
#// Skywalker's front Action (exhaust, deal 2 to your base: attack with a unit; +2/+0 if attacking a unit)
#// sends Hound's Tooth at SEC_213 A-Wing, raising its power to 6 for this attack. The after-attack ability
#// can now defeat SOR_050 The Ghost (power 5 < 6) — which it could NOT at its base power 4. Proves the
#// boost feeds the "less power than this unit" check.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_012}
P1OnlyActions: true
WithP1SpaceArena: LAW_033:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_050:1:0
WithP2SpaceArena: SEC_213:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirSpaceArena-1
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1BASEDMG:2
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
