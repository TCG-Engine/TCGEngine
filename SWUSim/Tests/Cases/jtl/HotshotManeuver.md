# NoOnAttack_JustAttacks
#// JTL_174 Hotshot Maneuver — "Choose a friendly unit. For each of its 'On Attack' abilities, deal 2
#// damage to a different enemy unit. Then, attack with the chosen unit." The chosen unit JTL_249
#// (Millennium Falcon, 3 power) has NO On Attack ability, so no damage is dealt; it just attacks the
#// P2 base for 3.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1SpaceArena: JTL_249:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1NODECISION

---

# OneOnAttack_Deal2ThenAttack
#// JTL_174 Hotshot Maneuver — the chosen unit JTL_243 (Quasar TIE Carrier, 5 power) has ONE On Attack
#// ability ("create a TIE"), so P1 deals 2 to one enemy unit (SOR_225, 2/1 → dies), THEN attacks with
#// JTL_243: its On Attack creates a TIE token and, with no enemy units left, it hits the P2 base for 5.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1SpaceArena: JTL_243:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:5
P1SPACEARENACOUNT:2

---

# OpponentControlsNoUnits_WhiffsThenAttacksBase
#// JTL_174 Hotshot Maneuver — when the opponent controls no units, the "deal 2 to a different enemy unit"
#// per On Attack ability has no target and is skipped (no prompt), but the chosen unit still attacks. JTL_243
#// (Quasar TIE Carrier, 5 power, one On Attack = create a TIE) attacks P2's base for 5 and its On Attack
#// makes a TIE token → arena has JTL_243 + the TIE.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1SpaceArena: JTL_243:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
P1SPACEARENACOUNT:2
P1NODECISION

---

# PlayedWithNoValidTarget
#// JTL_174 Hotshot Maneuver requires a friendly unit to choose; with none in play the event resolves with
#// no effect and goes to discard (the enemy unit is untouched, no attack happens).

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_174
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# TwoOnAttackAbilities_DealsToTwoUnits
#// JTL_174 Hotshot Maneuver — the chosen unit JTL_240 (Fett's Firespray, 4/4) carries a JTL_187 Bossk
#// PILOT upgrade (+2/+2 → 6/6) which grants a 2nd On Attack ability, so it now has TWO On Attack windows.
#// Hotshot therefore deals 2 to TWO DIFFERENT enemy units (SHD_200 Liberated Slaves + SHD_029 Pyke
#// Sentinel, each → DAMAGE:2). Then it attacks P2's base for 6 combat, and its own On Attack (Firespray
#// "1 indirect to a player", assigned to the base) adds 1 → P2 base = 7. Bossk's granted On Attack targets
#// the defender (a Base) and no-ops.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1SpaceArena: JTL_240:1:0
WithP1SpaceArenaPilot: 0:JTL_187
WithP2GroundArena: SHD_200:1:0
WithP2GroundArena: SHD_029:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>ResolveTrigger:OnAttack:JTL_240
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2
P2BASEDMG:7

---

# DoesNotDamageSameUnitTwice
#// JTL_174 Hotshot Maneuver — the chosen unit JTL_240 (Fett's Firespray) with a JTL_187 Bossk PILOT
#// upgrade has TWO On Attack abilities, but the opponent controls only ONE unit (SHD_200 Liberated
#// Slaves). Each of the 2 damage instances must hit a DIFFERENT enemy unit, so with a single enemy the
#// lone unit is dealt 2 only ONCE (DAMAGE:2, not 4). Then Firespray (6/6 with the pilot) attacks P2's
#// base for 6 combat + 1 indirect (its own On Attack, assigned to the base) = 7.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1SpaceArena: JTL_240:1:0
WithP1SpaceArenaPilot: 0:JTL_187
WithP2GroundArena: SHD_200:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>ResolveTrigger:OnAttack:JTL_240
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:7

---

# CountsGainedOnAttackAbilities
#// JTL_174 Hotshot Maneuver — On Attack abilities GAINED from upgrades are counted. TWI_096 Aayla Secura
#// (6/5) carries a SOR_054 Jedi Lightsaber (+3/+3 → 9/8) which grants a 2nd On Attack window, so choosing
#// Aayla yields 2 abilities → deal 2 to TWO different enemy units (SHD_200 Liberated Slaves + JTL_240
#// Fett's Firespray in space, each DAMAGE:2); the un-chosen SOR_207 Crafty Smuggler stays at 0. Then Aayla
#// attacks P2's base for 9. (Padmé/Aayla's Coordinate On Attacks and the lightsaber's defender-debuff all
#// no-op vs a base attack.)

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1GroundArena: TWI_192:1:0
WithP1GroundArena: TWI_096:1:0
WithP1GroundArenaUpgrade: 1:SOR_054
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SHD_200:1:0
WithP2GroundArena: SOR_207:1:0
WithP2SpaceArena: JTL_240:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0&theirSpaceArena-0
- P1>AnswerDecision:theirBase-0
- P1>ResolveTrigger:OnAttack:TWI_096

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:2
P2BASEDMG:9

---

# ConditionNotMet_NoDamage
#// JTL_174 Hotshot Maneuver counts only the On Attack abilities the chosen unit ACTUALLY HAS (condition
#// met), so a conditional-and-unmet On Attack contributes 0. Padmé Amidala (TWI_192, 1/4) is the LONE
#// friendly unit and carries a Jedi Lightsaber (SOR_054, +3/+3). Padmé's On Attack is Coordinate-gated
#// (needs 3+ friendly units → unmet with one), and the lightsaber's granted On Attack applies only while
#// the host is a Force unit (Padmé is NOT). Both are inactive → count 0 → NO damage to the enemy SHD_200;
#// Padmé (4 power with the lightsaber) just attacks the P2 base for 4. The enemy is placed in SPACE so
#// ground-Padmé auto-attacks the base (a count>0 bug would have dealt it 2). Contrast the passing
#// CountsGainedOnAttackAbilities: Aayla (a Force unit, Coordinate met) + the same lightsaber counts 2.
#// Closed by gating the counter on each ability's activation condition (_SWUOnAttackAbilityActive). The
#// enemy SHD_200 sits in P2's GROUND arena; count 0 means Hotshot deals it no pre-attack damage, then Padmé
#// attacks the P2 base (chosen over the unit) for 4 — SHD_200 ends undamaged.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1GroundArena: TWI_192:1:0
WithP1GroundArenaUpgrade: 0:SOR_054
WithP2SpaceArena: SHD_200:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:4

---

# TargetUnitThatCannotAttack
#// JTL_174 Hotshot Maneuver — you may choose an EXHAUSTED friendly unit. Its On Attack damage still
#// resolves (JTL_240 Fett's Firespray has one On Attack → deal 2 to the lone enemy SHD_200 Liberated
#// Slaves), but the "Then, attack" does nothing because the chosen unit is exhausted → P2 base takes 0.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1SpaceArena: JTL_240:0:0
WithP2GroundArena: SHD_200:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:0
