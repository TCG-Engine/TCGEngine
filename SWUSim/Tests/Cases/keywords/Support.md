# ASH033_AttackEndReadySelf
#// ASH_033 Grand Admiral Thrawn (Ground, 5/7, Support) — When Attack Ends: if the defending unit was
#// defeated, ready this unit. Placed ready, Thrawn attacks SEC_080 (3/3) and kills it (deals 5); takes 3
#// counter (survives), and because the defender was defeated, readies itself.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_033:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_033
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:READY

---

# ASH036_AttackEndGiveThreeAdvantage
#// ASH_036 Rukh (Ground, 1/5, Support) — When Attack Ends: if the defending unit was defeated, you may
#// give 3 Advantage tokens to a unit. Rukh attacks SOR_128 (3/1) and kills it (deals 1); takes 3 counter
#// (survives, 5 HP), and since the defender was defeated, gives 3 Advantage tokens to itself.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_036:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_036
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3

---

# ASH037_CrossArenaAttack
#// ASH_037 Red Leader (Space, 6/6, Support) — "This unit may attack units in either arena." Red Leader (a
#// space unit) attacks an enemy GROUND unit (SEC_080 3/3) cross-arena, killing it (deals 6) and taking 3
#// counter. (Mirrors the SOR_212 cross-arena testing approach.)
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_037:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:G0
## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_037
P1SPACEARENAUNIT:0:DAMAGE:3

---

# DefenderDebuff
#// ASH_046 Scion Shuttle (Space, 1/3, Support) — "While this unit is attacking, the defending unit gets
#// -1/-1." Scion attacks SOR_237 (2/3); the defender becomes 1/2, so its counter is 1 (not 2): Scion takes
#// only 1 damage (proves the -1 power). SOR_237 takes Scion's 1 and survives.
## GIVEN
CommonSetup: bbk/bbk
WithP1SpaceArena: ASH_046:1:0
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_046
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:1

---

# LentDefenderDebuff
#// Support lending of a CONTINUOUS ability (ASH_046 Scion Shuttle's "-1/-1 to the defender"). ASH_046 is
#// played; the player supports with SOR_237 (the other ready space unit), which attacks SOR_225 (2/1). The
#// lent -1/-1 reduces SOR_225's counter from 2 to 1, so SOR_237 takes only 1 (proves the SUPPORT_GRANT
#// graft of the passive). SOR_225 is defeated by SOR_237's 2 damage.
## GIVEN
CommonSetup: bbk/bbk/{myResources:4;handCardIds:ASH_046}
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:1

---

# ASH050_WhenDefeatedDebuff
#// ASH_050 Morgan Elsbeth (Ground, 5/6, Support) — When Defeated: you may give a unit -2/-2 for this
#// phase. Pre-damaged to 1 HP, Morgan attacks SOR_046 (3/7, survives) and dies to the 3 counter; her
#// WhenDefeated gives -2/-2 to the bystander SEC_080 (3/3 → 1/1).
## GIVEN
CommonSetup: bbk/bbk
WithP1GroundArena: ASH_050:1:5
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:POWER:1
P2GROUNDARENAUNIT:1:HP:1

---

# ASH059_OnAttackSelfDamageHealBase
#// ASH_059 Leia Organa (Ground, 3/4, Support) — On Attack: you may deal 1 damage to this unit; if you do,
#// heal 2 damage from your base. P1's base starts at 3 damage; Leia attacks the enemy base, takes 1
#// self-damage, and heals 2 from her base (3 → 1). The enemy base takes Leia's 3.
## GIVEN
CommonSetup: bbw/bbk/{myBaseDamage:3}
WithP1GroundArena: ASH_059:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P1BASEDMG:1
P1GROUNDARENAUNIT:0:CARDID:ASH_059
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# OnAttackDrawIfHealthy
#// ASH_072 Doctor Pershing (Ground, 0/4, Support) — On Attack: if this unit has 3 or more remaining HP,
#// draw a card. Undamaged (4 HP ≥ 3), Pershing attacks the enemy base and draws a card.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_072:1:0
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0

---

# OnAttackNoDrawIfDamaged
#// ASH_072 Doctor Pershing (Ground, 0/4, Support) — On Attack draws ONLY with 3+ remaining HP. Pre-damaged
#// to 2 remaining HP (2 damage on 4), Pershing attacks the enemy base and draws NOTHING.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_072:1:2
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1

---

# ASH099_OnAttackGainsSentinel
#// ASH_099 Gozanti Assault Carrier (Space, 4/6, Support) — On Attack: this unit gains Sentinel for this
#// phase. Gozanti attacks the enemy base; afterward it has Sentinel.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_099:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:CARDID:ASH_099
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# ASH101_AttackEndDefeatDamaged
#// ASH_101 The Great Mothers (Ground, 6/7, Support) — When Attack Ends: if it dealt combat damage to 1+
#// non-leader units, defeat those units. Attacks SOR_046 (3/7): deals 6 (survives), takes 3 counter, then
#// the ability defeats SOR_046 (the non-leader unit it dealt combat damage to).
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_101:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_101
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# ASH156_OnAttackDefeatUpgrades
#// ASH_156 R5-D4 (Ground, 3/4, Support) — On Attack: defeat all upgrades on the defending unit. R5-D4
#// attacks SOR_046 (3/7) carrying SOR_120 (+2/+2 → 5/9); the On Attack defeats SOR_120 (back to 3/7), then
#// combat deals 3. SOR_046 survives at UPGRADECOUNT 0.
## GIVEN
CommonSetup: rrw/rrw
WithP1GroundArena: ASH_156:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# ASH189_OnAttackReadyResource
#// ASH_189 Emperor's Messenger (Ground, 0/3, Support) — On Attack: ready a resource. P1 has 1 ready + 2
#// exhausted resources; the Messenger attacks the enemy base and readies one exhausted resource (1 → 2).
## GIVEN
CommonSetup: yyk/yyk
WithP1Resources: 1:SOR_046:1,2:SOR_046:0
WithP1GroundArena: ASH_189:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1RESAVAILABLE:2

---

# ASH202_ShootFirst
#// ASH_202 Carson Teva (Ground, 1/4, Support) — "While attacking, this unit deals combat damage before the
#// defender." Carson attacks SOR_128 (3/1): deals 1 first, killing it, so it deals NO counter — Carson
#// takes 0. (Without deal-first, the 3-power counter would have hit Carson for 3.)
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_202:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_202
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# ExhaustLeaderBonus
#// ASH_203 Mando's N-1 Starfighter (Space, 1/3, Support) — On Attack: you may exhaust a friendly leader.
#// If you do, this unit gets +2/+0 for this attack. With the leader ready, the player exhausts it and the
#// Starfighter deals 3 (1 + 2) to the enemy base.
## GIVEN
CommonSetup: yyk/yyk
WithP1SpaceArena: ASH_203:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P1LEADER:EXHAUSTED

---

# NoReadyLeader_NoBonus
#// ASH_203 Mando's N-1 Starfighter — the +2/+0 is gated on a ready leader to exhaust. With the leader
#// already exhausted, no option is offered and the Starfighter deals only its base 1 to the enemy base.
## GIVEN
CommonSetup: yyk/yyk/{myLeader:SOR_016:0}
WithP1SpaceArena: ASH_203:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:1
P1LEADER:EXHAUSTED

---

# NotUpgraded_NoDebuff
#// ASH_209 Ezra Bridger — the -3/-0 On Attack fires ONLY while Ezra is upgraded. With no upgrade, Ezra
#// attacks the enemy base and the enemy SEC_080 keeps its full 3 power (no decision offered).
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_209:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:3

---

# UpgradedDebuff
#// ASH_209 Ezra Bridger (Ground, 6/6, Support) — On Attack: if this unit is upgraded, you may give a unit
#// -3/-0 for this phase. Ezra carries SOR_120; attacking the enemy base, the upgraded On Attack gives the
#// enemy SEC_080 (3/3) -3/-0 (power 0).
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_209:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:0

---

# ASH223_AttackEndShieldOnKill
#// ASH_223 Halo (Space, 4/4, Support) — When Attack Ends: if the defending unit was defeated, give a
#// Shield token to this unit. Halo attacks SOR_225 (2/1) and kills it (deals 4); takes 2 counter
#// (survives), and because the defender was defeated, gains a Shield token.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_223:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_223
P1SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# BonusVsDamaged
#// ASH_241 Marrok's Fiend Fighter (Space, 3/2, Support, Overwhelm) — "This unit gets +2/+0 while attacking
#// a damaged unit." Attacks a damaged JTL_069 (4/7, pre-damaged 1): ASH_241 deals 5 (3+2), so JTL_069 ends
#// at 6 damage. (Without the bonus it would be 4.)
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_241:1:0
WithP2SpaceArena: JTL_069:1:1
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:6

---

# NoBonusVsUndamaged
#// ASH_241 Marrok's Fiend Fighter — the +2/+0 applies ONLY while attacking a DAMAGED unit. Attacking an
#// undamaged JTL_069 (4/7), ASH_241 deals only its base 3.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_241:1:0
WithP2SpaceArena: JTL_069:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:3

---

# NotUpgraded_NoBaseDamage
#// ASH_253 Yellow Aces Bomber — the "deal 2 to a base" On Attack fires ONLY while upgraded. With no
#// upgrade, ASH_253 attacks the enemy base and deals only its 2 combat damage (no decision offered).
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_253:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:2

---

# UpgradedDealsToBase
#// ASH_253 Yellow Aces Bomber (Space, 2/4, Support) — On Attack: if this unit is upgraded, deal 2 damage
#// to a base. Carrying SOR_120 (+2/+2 → 4 power), ASH_253 attacks the enemy base: the On Attack deals 2 to
#// the enemy base, then combat deals 4 → 6 total.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_253:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:6

---

# ChooseAmongTwoAttackers
#// Support (ASH) — choosing among multiple eligible attackers. ASH_154 (Raid 1 + Support) is played with
#// two ready Marines eligible. The player picks index 1; that Marine gains Raid 1 and attacks the base for
#// 3 + 1 = 4. The non-chosen Marine (index 0) stays ready.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_154}
WithP1GroundArena: SOR_095:1:0   # Marine A (index 0)
WithP1GroundArena: SOR_095:1:0   # Marine B (index 1)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# ChooseAttacker_RaidGrant
#// Support (ASH) — keyword lending (single eligible attacker, auto-selected). ASH_154 Honorable Nite Owl
#// (Ground, 2/2, Raid 1 + Support) is played. The lone ready Marine gains Raid 1 (lent from ASH_154) and
#// attacks the base for 3 + 1 = 4.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_154}
WithP1GroundArena: SOR_095:1:0   # Battlefield Marine (3/3, ready)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Decline
#// Support (ASH) — declining the optional bonus attack. ASH_130 is played; player answers NO. No attack
#// happens: the Marine stays ready, P2's base is undamaged. ASH_130 still entered the space arena.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_130}
WithP1GroundArena: SOR_095:1:0   # Battlefield Marine (3/3, ready)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:READY

---

# OnAttackGrant_DealToDefender
#// Support (ASH) — triggered-ability lending (On Attack). ASH_168 Migs Mayfeld (Ground, 2/3, Support +
#// "On Attack: deal 1 to the defending unit") is played. The Marine is chosen and attacks an enemy 1/5
#// wall (ASH_036). The lent On Attack deals 1 to the wall, then combat deals 3 → 4 damage total; the wall
#// (5 HP) survives. ("this unit" in the lent ability = the Marine, which isn't upgraded → deal 1, not 2.)

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_168}
WithP1GroundArena: SOR_095:1:0   # Battlefield Marine (3/3, attacker)
WithP2GroundArena: ASH_036:2:0   # 1/5 wall (defender)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# PureBonusAttack_Base
#// Support (ASH) — pure keyword. ASH_130 Fang Fighter Squadron (Space, 5/5, Support only) is played.
#// The only ready friendly unit (Battlefield Marine, 3 power) is auto-selected and attacks P2's base
#// for 3. ASH_130 itself enters the space arena exhausted; the Marine exhausts from attacking.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_130}
WithP1GroundArena: SOR_095:1:0   # Battlefield Marine (3/3, ready)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# RestoreGrant_HealsBase
#// Support (ASH) — Restore lending. ASH_095 Remnant Interceptor (Space, 2/2, Restore 1 + Support) is
#// played. The Marine gains Restore 1 (lent) and attacks P2's base for 3; Restore heals 1 from P1's own
#// base (3 damage → 2).

## GIVEN
CommonSetup: yrw/grw/{myResources:9;myBaseDamage:3;handCardIds:ASH_095}
WithP1GroundArena: SOR_095:1:0   # Battlefield Marine (3/3, ready)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
P1BASEDMG:2

---

# ASH033_DefenderSurvives_NoReady
#// ASH_033 Grand Admiral Thrawn — the ready-self rider needs the DEFENDER DEFEATED. Attacking the wall
#// SOR_046 (3/7) which survives, Thrawn does not ready and stays exhausted.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_033:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ASH036_DefenderSurvives_NoAdvantage
#// ASH_036 Rukh — the Advantage rider needs the defender defeated. Attacking the surviving SOR_046, Rukh
#// gives no Advantage and leaves no pending decision.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_036:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# ASH223_DefenderSurvives_NoShield
#// ASH_223 Halo — the Shield rider needs the defender defeated. Attacking the surviving space wall ASH_081
#// (3/6), Halo gains no Shield token.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_223:1:0
WithP2SpaceArena: ASH_081:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENAUNIT:0:SHIELDCOUNT:0

---

# ASH059_OnAttack_Decline_NoSelfDamage
#// ASH_059 Leia Organa — the On Attack self-damage/heal is optional. Declining leaves Leia undamaged and
#// the base unhealed (stays at 3).
## GIVEN
CommonSetup: bbw/bbk/{myBaseDamage:3}
WithP1GroundArena: ASH_059:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3
P2BASEDMG:3

---

# ASH050_WhenDefeated_Decline_NoDebuff
#// ASH_050 Morgan Elsbeth — the When Defeated -2/-2 is optional. A near-dead Morgan dies attacking SOR_046
#// and P1 declines, so SOR_046 keeps its 3 power.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_050:1:5
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:POWER:3

---

# ASH156_OnAttack_DefeatsAllUpgrades
#// ASH_156 R5-D4 — On Attack defeats ALL upgrades on the defender (not just one). SOR_046 wears SOR_120 and
#// a Shield token; attacking it strips both (upgrade count → 0) before combat.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_156:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_T02
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# ASH241_OverwhelmExcessToBase
#// ASH_241 Marrok's Fiend Fighter — Overwhelm + "+2/+0 while attacking a damaged unit." Marrok (3 power)
#// attacks the pre-damaged SOR_237 (1 HP remaining): the +2 makes it 5 power, kills the 1-HP unit, and
#// Overwhelm spills the 4 excess to the enemy base.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_241:1:0
WithP2SpaceArena: SOR_237:1:2
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:4

---

# ASH209_Upgraded_Decline_NoDebuff
#// ASH_209 Ezra Bridger — even while upgraded, the -3/-0 is a "may". Ezra (carrying SOR_120) attacks the
#// base but P1 declines, so the enemy SEC_080 keeps its full 3 power.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_209:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:POWER:3

---

# ASH037_CrossArena_AttacksSentinelUnit
#// ASH_037 Red Leader — its cross-arena reach can target an enemy GROUND Sentinel from space. Red Leader
#// (6/6, space) attacks the enemy SOR_063 (2/4 Sentinel) cross-arena and defeats it.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_037:1:0
WithP2GroundArena: SOR_063:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:G0
## EXPECT
P2GROUNDARENACOUNT:0
