#// ASH_062 The Mandalorian: "If damage would be dealt to another friendly unit, you may defeat a Shield token on
#// this unit. If you do, prevent that damage." For COMBAT damage the engine asks early: CombatLogic.php adds an
#// ASH_062_PREVENT trigger at the Begin-attack step (with the On Attack triggers), so the offer can be answered
#// before SWUCombatDamage. SEC_101 Queen Amidala's prevention uses the same route (SEC_101_PREVENT).
#//
#// FIXED 2026-09-13 (all RED sections green; full suite 11895/0): Ash062PreventTrigger / SEC101PreventTrigger
#//   (CombatLogic.php) re-check _SWUCombatDamageStillReaches when the offer resolves. The timing question it
#//   raised was then ruled (below): the prevention is offered after the On Attack triggers.
#//
#// FOUND BY: sweep retro #5 of run 2 (2026-09-13): ORDER ASH_062:ASH_062_PREVENT + ASH_248:OnAttack /
#//   LAW_095:OnAttack / LOF_046:OnAttack, ASH_009:SupportOnAttack + ASH_062_PREVENT, and ASH_253:SupportOnAttack
#//   + SEC_101:SEC_101_PREVENT (1–2× each; Lando blue and Ahsoka blue lists).
#//
#// ★ WAS RED (1 section) — THE OFFER OUTLIVES THE COMBAT DAMAGE IT WAS RAISED FOR. The gate ("the defender has power,
#//   so counter-damage would occur") is checked once, when the trigger is bagged. If an On Attack in the same
#//   pool then DEFEATS the defender, there is no combat damage any more, yet the prevention still asks "Defeat a
#//   Shield on The Mandalorian to prevent combat damage to this unit?", and YES spends the Shield on nothing.
#//   Probe 2026-09-13: Red Five's "2 damage to a damaged unit" defeats the damaged defender; the offer follows.
#//   Correct: re-check at resolution (the defender still in play with power > 0) and offer nothing otherwise.
#//
#// ★ OWNER RULING 2026-09-13 (answers the question this file used to leave open): "whenever the unit is damaged,
#//   it fires. If an On Attack triggers first, then it must resolve before combat damage. However, if an On
#//   Attack damages those units, they must resolve it before combat damage as well." So the COMBAT-damage
#//   prevention is not an entry in the On Attack ordering pool: the On Attack triggers resolve first (and an
#//   On Attack that damages a protected unit gets ITS prevention offer on the spot), then the combat prevention
#//   is offered, still before combat damage. The Ruling_* sections pin that.
#//
#// Cards: JTL_151 Red Five 3/4 space ("On Attack: You may deal 2 damage to a damaged unit.") · SOR_237 Alliance
#//   X-Wing 2/3 · SOR_T02 Shield token on ASH_062.
#//
# RED_RedFiveDefeatsTheDamagedDefender_NoCombatDamage_MandoMustNotOfferHisShield
## GIVEN
CommonSetup: ggw/brk/{myLeader:SOR_005}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1SpaceArena: JTL_151:1:0
WithP2SpaceArena: SOR_237:1:1
## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P1NODECISION
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# CONTROL_TheDefenderSurvives_TheOfferIsReal_AndPreventsTheCounter
#// The X-Wing is undamaged, so Red Five's On Attack has no target and the X-Wing survives to strike back. The
#//   prevention offer is real: YES defeats Mando's Shield and Red Five takes none of the 2.
## GIVEN
CommonSetup: ggw/brk/{myLeader:SOR_005}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1SpaceArena: JTL_151:1:0
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0
- P1>AnswerDecision:YES
## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2SPACEARENACOUNT:0

---

# Ruling_NoOrderingPrompt_NeelsOnAttackResolves_ThenTheCombatPreventionIsOffered
#// ASH_248 Neel's On Attack (non-interactive) and nothing else trigger on the attack. The prevention is not in
#//   that pool, so there is no ordering prompt: Neel's On Attack resolves and the first question is Mando's.
## GIVEN
CommonSetup: ggw/brk/{myLeader:SOR_005}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: ASH_248:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Defeat_a_Shield_on_The_Mandalorian_to_prevent_combat_damage_to_this_unit?

---

# Ruling_NeelAttacks_YesPreventsTheCounter_TheMarineStillTakesNeelsDamage
## GIVEN
CommonSetup: ggw/brk/{myLeader:SOR_005}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: ASH_248:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_248
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION

---

# Ruling_MaulsOnAttackComesFirst_NotAnOrderingPrompt
#// LOF_009 Darth Maul (deployed) attacks the Marine beside Mando. His On Attack pings are the first question.
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Deal_1_damage_to_a_unit

---

# Ruling_MaulPingsHimself_ThatDamageIsPreventableOnTheSpot_ThenTheCombatPreventionBeforeDamage
#// Maul pings the Marine, then HIMSELF (another friendly unit to Mando): the ability-damage prevention is offered
#//   right then (declined, Maul takes 1). After the On Attack, the combat prevention for Maul's counter-damage
#//   is offered before combat damage (accepted: Mando's Shield goes, Maul takes none of the Marine's 3). The
#//   Marine, 1 + 5, is defeated.
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:NO
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_009
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENACOUNT:0
P1NODECISION
