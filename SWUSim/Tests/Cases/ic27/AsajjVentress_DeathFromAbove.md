# Accept_SheDealsTheDamageInstead
#// IC27_147 Asajj Ventress — Death From Above (Ground 5/7, cost 6, Cunning/Villainy) — "If a friendly non-leader
#// ground unit would deal combat damage while attacking a base, you may reveal this unit from your hand and pay
#// [5 resources Cunning Villainy]. If you do, return the attacker to its owner's hand and put this unit into play as
#// the attacker. She deals combat damage to that base instead."
#// OWNER DESIGN (2026-11-03) — "set it up at declaration, re-check at damage": when a friendly non-leader ground unit
#// declares an attack on a base while you hold Asajj and can pay, you choose Attack / Asajj_Ventress. Choosing her
#// only ARMS the replacement — nothing is paid or revealed. At the combat-damage commit (after On Attack / On
#// Defense) it is re-checked: attacker still in play and would deal > 0 combat damage, Asajj still in hand, still
#// affordable. If so: reveal, pay, bounce the attacker, Asajj enters exhausted as the attacker and deals HER power.
#// If not, it lapses silently: nothing paid, nothing revealed. (A damage-time prompt would leak a held Asajj through
#// the pause it adds to every base attack.)
#// Battlefield Marine (3) attacks P2's base (its only target); P1 pays 5 of 5 (Cunning/Villainy on-aspect).
#// COVERAGE: offer=Offer_IsAChoiceAtDeclaration · decline=Decline_ANormalAttack ·
#//   gates (not offered)=NotOffered_AttackingAUnit / _OverwhelmSpill / _SpaceUnit / _DeployedLeader / _NotInHand /
#//   _EnemyAttacksYourBase / _OpponentHoldsAsajj / _SentinelBlocksTheBase · offered via=Saboteur_PastASentinel +
#//   Commence_SaboteurAttack_TheTwoFollowsHer_SheDealsSeven + FollowMe_EventAttack_AndItsRewardStillFires ·
#//   the modified attack's riders move to her (owner rulings 2026-11-03)=Riders_* (+N, HP-as-damage, granted ability,
#//   the post-attack self-defeats; snapshot across a request boundary) · scope exclusions (lasting effects stay)=
#//   Riders_CondemnDisclosed_* + Riders_DrydenVos_* + Riders_CommenceOnDryden_* + Riders_APhaseBuffStaysWithTheUnit +
#//   Riders_CondemnsBlankingStaysWithTheUpgrade + Damage_RaidStaysWithTheAttacker (printed Raid is the unit's own) ·
#//   cost boundary=this (5 of 5) + Cost_OneShort_NotOffered + Cost_OffAspect_NinePaid + Cost_OffAspect_EightIsShort ·
#//   alt payment=Cost_CreditsHelpPay · result=Result_* (owner's hand, token set aside, upgrades) · damage=Damage_* ·
#//   observers=Observers_* · edges=TwoInHand_* + Uniqueness_* + HerDamageCanWinTheGame ·
#//   lapse (re-checked at damage)=Lapse_CondemnDropsTheAttackerToZero (no damage; 6-power boundary=
#//   Lapse_CondemnDropsASixPowerAttackerToExactlyZero) + Lapse_PaidAwayBeforeDamage (can't pay) + Lapse_DiscardedFromHandBeforeDamage (not in hand) · action close=ActionClosesOnce_* ·
#//   reqboundary=Lapse_CondemnNotDisclosed_SwapAcrossARequestBoundary · control=Result_StolenAttacker_* ·
#//   modes=TwinSuns_TheAttackedBaseTakesIt + TeamSuns_ATeammatesAttack_YouAreOffered ("friendly" spans the team)

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1RESAVAILABLE:0
LOGCONTAINS:revealed [[IC27_147|Asajj Ventress]]
P1NODECISION

---

# Offer_IsAChoiceAtDeclaration
#// The choice is raised when the base is chosen as the target — before anything resolves or is paid.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECISIONTYPE:OPTIONCHOOSE
P2BASEDMG:0
P1RESAVAILABLE:5
P1HANDCARD:0:IC27_147

---

# Decline_ANormalAttack

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Attack

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1HANDCARD:0:IC27_147
P1RESAVAILABLE:5
P1NODECISION

---

# NotOffered_AttackingAUnit
#// "while attacking a BASE": attacking a unit is never offered.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1HANDCARD:0:IC27_147
P1RESAVAILABLE:5
P1NODECISION

---

# NotOffered_OverwhelmSpillIsNotAttackingABase
#// Saw Gerrera's front (+2/+0, Overwhelm) sends Imperial Dark Trooper (5) at a 2/2: 3 excess reaches the base, but the
#// unit was attacking a UNIT, so Asajj is never offered.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:LAW_001;myResources:9}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_110:1:0
WithP1Hand: IC27_147

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P1HANDCARD:0:IC27_147
P1RESAVAILABLE:9
P1NODECISION

---

# NotOffered_SpaceUnit
#// "ground unit": a space unit's base attack is never offered.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P1HANDCARD:0:IC27_147
P1NODECISION

---

# NotOffered_DeployedLeader
#// "non-leader": deployed IC27_007 Asajj Ventress (a leader unit, 5/6) attacks the base — nothing offered. (Her own
#// On Attack "ready a resource" asks nothing: no resource is exhausted.)

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007;myLeaderDeployed:1;myResources:5}
P1OnlyActions: true
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1HANDCARD:0:IC27_147
P1RESAVAILABLE:5
P1NODECISION

---

# NotOffered_NotInHand
#// "reveal this unit from your HAND": in the discard pile she does nothing.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Discard: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1NODECISION

---

# NotOffered_EnemyAttacksYourBase
#// "friendly": P2's unit attacking P1's base is not P1's to replace.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
WithActivePlayer: 2
WithP2GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P1HANDCARD:0:IC27_147
P1NODECISION
P2NODECISION

---

# NotOffered_OpponentHoldsAsajj
#// P2 holds Asajj with resources to spare; P1's attack on P2's base is not friendly to P2.

## GIVEN
CommonSetup: yyk/yyk/{theirResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P2HANDCARD:0:IC27_147
P1NODECISION
P2NODECISION

---

# NotOffered_SentinelBlocksTheBase
#// P2's Cell Block Guard (Sentinel): Battlefield Marine can only attack the Sentinel, so there is no base attack to
#// set up, however many resources P1 has. (The 3/3s trade.)

## GIVEN
CommonSetup: yyk/bbw/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_229:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:0
P1HANDCARD:0:IC27_147
P1NODECISION

---

# Saboteur_PastASentinel
#// Jawa Scavenger (Saboteur, 2/1) ignores the Sentinel and attacks the base: offered, and Asajj deals 5.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_205:1:0
WithP2GroundArena: SOR_229:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1HANDCARD:0:SOR_205
P1NODECISION

---

# Commence_SaboteurAttack_TheTwoFollowsHer_SheDealsSeven
#// LAW_202 Commence the Festivities (1 + 2 off-aspect = 3) grants Battlefield Marine Saboteur (past the Sentinel) and
#// +2/+0 — P1 controls fewer resources than P2. OWNER RULING (2026-11-03): she is put into play "as the attacker", so
#// the +2/+0 "for this attack" moves to her — 5 + 2 = 7. 8 resources: 3 for the event, 5 for Asajj.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8;theirResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_229:1:0
WithP1Hand: [LAW_202 IC27_147]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1RESAVAILABLE:0
P1NODECISION

---

# FollowMe_EventAttack_AndItsRewardStillFires
#// ASH_184 Follow Me (1 + 2 = 3) — "Attack with a unit. After completing the attack, give 3 Advantage tokens to a
#// unit." The attack is completed (by Asajj), so its reward still comes: 3 Advantage tokens to Asajj herself.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: [ASH_184 IC27_147]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Asajj_Ventress
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1NODECISION

---

# Cost_OneShort_NotOffered
#// 4 resources for a cost of 5: no choice is raised and the Marine attacks normally.

## GIVEN
CommonSetup: yyk/bbw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1HANDCARD:0:IC27_147
P1NODECISION

---

# Cost_OffAspect_NinePaid
#// [5 resources Cunning Villainy] carries the aspect penalty: under a Vigilance/Heroism leader and base P1 lacks both,
#// 5 + 2 + 2 = 9. 9 resources pays it, leaving 0.

## GIVEN
CommonSetup: bbw/yyk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1RESAVAILABLE:0

---

# Cost_OffAspect_EightIsShort

## GIVEN
CommonSetup: bbw/yyk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1HANDCARD:0:IC27_147
P1NODECISION

---

# Cost_CreditsHelpPay
#// 3 resources + 2 Credit tokens cover the 5 (CR 3.13: Credits pay costs).

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
P1OnlyActions: true
WithP1Credits: 2
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1RESAVAILABLE:0
P1NODECISION

---

# Result_StolenAttacker_GoesToItsOwnersHand
#// "return the attacker to its OWNER's hand": P1 controls P2's Consular Security Force; it goes to P2's hand.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_046:2
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1HANDCOUNT:0
P2HANDCARD:0:SOR_046

---

# Result_TokenAttacker_IsSetAside
#// A Battle Droid token returned to hand is set aside instead (tokens can't enter the hand): nothing reaches either
#// hand or discard.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1HANDCOUNT:0
P1DISCARDCOUNT:0

---

# Result_AttackersUpgrades_DiscardedOrSetAside
#// The bounced Marine's Academy Training goes to the discard pile; its Shield token is set aside.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1HANDCARD:0:SOR_095
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Damage_BigAttacker_SheDealsFiveNotEight
#// Kanan Jarrus (8) attacks: Asajj deals HER 5 — the replacement can lose damage, and that is the player's call.
#// (Kanan is 6 + 4 off-aspect, but he is fixtured in play.)

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: IC27_121:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1HANDCARD:0:IC27_121

---

# Damage_WeakAttacker_SheDealsFiveNotOne
#// Warrior Drone (1) attacks: Asajj turns its 1 into 5.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: TWI_057:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1HANDCARD:0:TWI_057

---

# Damage_RaidStaysWithTheAttacker
#// Cantina Braggart is 0/3 with Raid 2 — it would deal 2 while attacking, so the swap goes ahead, and Asajj deals 5
#// (not 5 + 2: Raid is the Braggart's).

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_157:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147

---

# Observers_TheOriginalsDealsDamageToABase_DoesNotFire
#// CR 7.5.d: the replacement replaces ALL of the combat damage, so Valiant Commando's "When this unit deals combat
#// damage to a base: you may defeat this unit…" never triggers — no prompt, and 5 damage, not 5 + 3.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SEC_150:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1HANDCARD:0:SEC_150
P1NODECISION

---

# Observers_FriendlyAttackEnds_SeesAsajjsDamage
#// ASH_144 Vane's Snub Fighter — "When a friendly unit's attack ends: If it dealt combat damage to a base, give an
#// Advantage token to this unit." Asajj dealt it, so the Snub Fighter gets its token.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: ASH_144:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1SPACEARENAUNIT:0:UPGRADECOUNT:1

---

# Observers_LeaderRider_FollowsTheNewAttacker
#// OWNER RULING (2026-11-03): IC27_007 Asajj Ventress (leader front) — "After completing this attack, if the attacker
#// survived, you may ready a resource." After the swap Death From Above Asajj IS the attacker, and she survives, so the
#// ready is offered. Resources: 5 ready + 1 exhausted; Asajj costs 5 → 0 ready; the rider readies 1.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147
WithP1Resources: 5:SOR_095:1,1:SOR_095:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Asajj_Ventress
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1RESAVAILABLE:1
P1NODECISION

---

# Riders_SawsTwoAndDefeatIt_FollowHer
#// LAW_001 Saw Gerrera (front) — "Attack with a unit. It gets +2/+0 and gains Overwhelm for this attack. After
#// completing this attack, defeat it." OWNER RULINGS (2026-11-03): everything the attack put on the attacker moves to
#// Asajj — she deals 5 + 2 = 7, and after the attack SHE is defeated. Imperial Dark Trooper sits in hand.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:LAW_001;myResources:9}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: IC27_147

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:7
P1GROUNDARENACOUNT:0
P1HANDCARD:0:SEC_080
P1DISCARDCOUNT:1

---

# Riders_FlashTheVents_TheTwoAndTheBaseDefeatFollowHer
#// LAW_205 Flash the Vents (1 + 2 off-aspect = 3) — "It gets +2/+0 … After completing this attack, if that unit damaged
#// a base, defeat that unit." Both halves move to Asajj: she deals 7, she damaged the base, so she is defeated.
#// 8 resources: 3 for the event, 5 for Asajj. Discard: the event + Asajj.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: [LAW_205 IC27_147]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:7
P1GROUNDARENACOUNT:0
P1HANDCARD:0:SOR_095
P1DISCARDCOUNT:2
P1NODECISION

---

# Riders_HeroicSacrifice_ItsGrantedDefeatFollowsHer
#// SOR_150 Heroic Sacrifice (1 + 4 off-aspect = 5) — "For this attack, it gets +2/+0 and gains: 'When this unit deals
#// combat damage: Defeat it.'" A GRANTED ABILITY for this attack moves to her too: she deals 7, then is defeated.
#// 10 resources: 5 for the event, 5 for Asajj. The draw takes the one deck card.

## GIVEN
CommonSetup: yyk/bbw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_237
WithP1Hand: [SOR_150 IC27_147]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:7
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P1NODECISION

---

# Riders_BabuFrik_HPAsDamageFollowsHer
#// LOF_206 Babu Frik — "attack with a friendly Droid unit. For this attack, it deals damage equal to its remaining HP
#// instead of its power." Chopper (1/3) would deal 3; the swap goes ahead and Asajj deals HER remaining HP, 7.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [LOF_206:1:0 SOR_188:1:0]
WithP1Hand: IC27_147

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:1:CARDID:IC27_147
P1HANDCARD:0:SOR_188

---

# Riders_DrydenVos_HisOwnDoubleStaysWithHim
#// SEC_137 Dryden Vos (2/5) — "On Attack: You may double this unit's power for this attack." That is a LASTING EFFECT
#// his own ability puts on him, not part of a modified "attack with a unit" (owner + judge ruling 2026-11-03, the
#// Condemn line), so it stays with him and Asajj deals her own 5.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArena: SEC_137:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147

---

# Riders_CommenceOnDryden_TheAttacksTwoMoves_HisDoubleStays
#// Both kinds on one attack. LAW_202 Commence's +2/+0 is the modified attack's (stamped before the base is declared) →
#// moves; Dryden's own On Attack double comes after → stays. Asajj deals 5 + 2 = 7 — not 14 (both moved) or 5 (neither).

## GIVEN
CommonSetup: yyk/bbw/{myResources:8;theirResources:10}
P1OnlyActions: true
WithP1GroundArena: SEC_137:1:0
WithP1Hand: [LAW_202 IC27_147]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Asajj_Ventress
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:CARDID:IC27_147

---

# Riders_TheSnapshotSurvivesARequestBoundary
#// The declaration-time riders are saved in the gamestate, not a PHP global: Commence's +2 still moves to Asajj when
#// her choice is answered in a later request.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8;theirResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: [LAW_202 IC27_147]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:CARDID:IC27_147

---

# Riders_CondemnDisclosed_OnABigAttacker_SheStillDealsFive
#// Kanan Jarrus (8) wears SEC_038 Condemn; P2 discloses (Death Trooper) and he gets -6/-0 → 2, still > 0, so the swap
#// goes ahead. OWNER + JUDGE RULING (2026-11-03): Condemn's -6/-0 is a LASTING EFFECT on that unit, not part of a
#// modified "attack with a unit" (the Commence case), so it stays with Kanan and Asajj deals her full 5.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArena: IC27_121:1:0
WithP1GroundArenaUpgrade: 0:SEC_038
WithP1Hand: IC27_147
WithP2Hand: SOR_033

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress
- P2>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1HANDCARD:0:IC27_121
P1RESAVAILABLE:0

---

# Riders_APhaseBuffStaysWithTheUnit
#// Scope exclusion: only what lasts FOR THIS ATTACK moves. SOR_124 Tactical Advantage (1 + 2 off-aspect = 3) gives the
#// Marine +2/+2 "for this phase" — the unit's, not the attack's — so it returns to hand with it and Asajj deals 5.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: [SOR_124 IC27_147]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1HANDCARD:0:SOR_095

---

# Riders_CondemnsBlankingStaysWithTheUpgrade
#// Condemn's "loses all other abilities" while attacking comes from the UPGRADE, which is discarded with the Marine — it
#// does NOT move to Asajj. Observable through Hondo Ohnaka ("Each other friendly unit gains Raid 1"): P2 declines to
#// disclose, and Asajj keeps Hondo's Raid → 6. A blanked Asajj would deal 5.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArena: [SOR_095:1:0 SEC_140:1:0]
WithP1GroundArenaUpgrade: 0:SEC_038
WithP1Hand: IC27_147
WithP2Hand: SOR_033

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress
- P2>AnswerDecision:-

## EXPECT
P2BASEDMG:6
P1HANDCARD:0:SOR_095

---

# TwoInHand_OneSwapOnly
#// CR 7.5.e/f: once the damage is replaced it can't be replaced again — with two Asajjs in hand there is ONE choice
#// and one swap; the second copy stays in hand, and the Marine joins it.

## GIVEN
CommonSetup: yyk/bbw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: [IC27_147 IC27_147]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:1
P1HANDCOUNT:2
P1RESAVAILABLE:5
P1NODECISION

---

# Uniqueness_KeepTheNewAsajj_SheDealsTheDamage
#// P1 already controls a Death From Above Asajj. The new copy enters as the attacker and the uniqueness rule resolves
#// first: P1 defeats the OLD one, and the new Asajj deals 5.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: IC27_147:0:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:Asajj_Ventress
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1DISCARDCOUNT:1
P1HANDCARD:0:SOR_095

---

# Uniqueness_KeepTheOldAsajj_NoDamage
#// Same board, but P1 defeats the NEW copy: the attacker has left play before dealing damage, so the base takes
#// nothing — and the Marine is already back in hand.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: IC27_147:0:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:Asajj_Ventress
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1
P1HANDCARD:0:SOR_095

---

# Lapse_CondemnDropsTheAttackerToZero
#// The owner's example: Battlefield Marine (3) wears SEC_038 Condemn. It attacks with Asajj armed; Condemn's On Attack
#// lets P2 disclose Vigilance+Villainy (Death Trooper) and the Marine gets -6/-0 → 0 power, so it would deal NO combat
#// damage. The setup lapses: nothing paid, Asajj never revealed, the Marine stays in play.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_038
WithP1Hand: IC27_147
WithP2Hand: SOR_033

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress
- P2>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1HANDCARD:0:IC27_147
P1RESAVAILABLE:5

---

# Lapse_CondemnDropsASixPowerAttackerToExactlyZero
#// The boundary: SOR_232 AT-ST (6/7) wears Condemn and P2 discloses — 6 - 6 = exactly 0, so it would deal no combat
#// damage and the setup lapses as above. (Condemn blanks its Overwhelm, which a base attack never uses anyway.)

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArena: SOR_232:1:0
WithP1GroundArenaUpgrade: 0:SEC_038
WithP1Hand: IC27_147
WithP2Hand: SOR_033

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress
- P2>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:CARDID:SOR_232
P1HANDCARD:0:IC27_147
P1RESAVAILABLE:5

---

# Lapse_CondemnNotDisclosed_SwapAcrossARequestBoundary
#// Same board, P2 declines to disclose: the Marine keeps its 3, so the armed swap goes through at damage — and the
#// arming survived the request boundary of P2's prompt.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_038
WithP1Hand: IC27_147
WithP2Hand: SOR_033

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress
- P2>SimulateRequestBoundary
- P2>AnswerDecision:-

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1HANDCARD:0:SOR_095
P1RESAVAILABLE:0

---

# TwinSuns_TheAttackedBaseTakesIt
#// Three seats: P1 attacks P3's base — "that base" is P3's; P2's base is untouched.

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1Resources: 5
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:p3Base-0
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
SEATCOUNT:3
P3BASEDMG:5
P2BASEDMG:0
P1GROUNDARENAUNIT:0:CARDID:IC27_147

---

# TeamSuns_ATeammatesAttack_YouAreOffered
#// OWNER RULING (2026-11-03): "friendly" spans the team. Teams 1+3 vs 2+4. Seat 3's Battlefield Marine attacks seat
#// 2's base; seat 1 holds Asajj and is the one asked. She enters under SEAT 1's control, pays from seat 1's
#// resources; the Marine returns to seat 3's hand.

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 5
WithP3GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P3>AttackGroundArena:0:p2Base-0
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
SEATCOUNT:4
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1RESAVAILABLE:0
P3HANDCARD:0:SOR_095
P3GROUNDARENACOUNT:0
TURNPLAYER:4

---

# ActionClosesOnce_TheTurnPasses
#// A real turn order (no P1OnlyActions): the swapped attack is still ONE action — after it the turn passes to P2.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147
TURNPLAYER:2
P1NODECISION
P2NODECISION

---

# ActionClosesOnce_ThroughTheUniquenessChoice
#// The uniqueness choice re-queues the damage step behind it; the action still closes once, after the damage.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArena: IC27_147:0:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:Asajj_Ventress
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:1
TURNPLAYER:2
P1NODECISION
P2NODECISION

---

# HerDamageCanWinTheGame
#// P2's base has 5 HP left (25 damage of 30): the Marine's 3 would leave it at 2, Asajj's 5 defeats it.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5;theirBaseDamage:25}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEREMAINING:0
GAMEWINNERS:1

---

# Lapse_PaidAwayBeforeDamage
#// Re-checked at damage: LAW_214 Boba Fett (6/5) — "On Attack: You may pay 1 resource. If you do, deal 3 damage to a
#// ground unit." With exactly 5, Asajj was affordable at declaration; Boba's On Attack spends 1, so at damage she is
#// not — the setup lapses and Boba deals his own 6.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_214:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:CARDID:LAW_214
P1HANDCARD:0:IC27_147
P1RESAVAILABLE:4
P1NODECISION

---

# Lapse_DiscardedFromHandBeforeDamage
#// Re-checked at damage: LOF_160 Merrin (2/5) — "On Attack: You may discard a card from your hand. If you do, deal 2
#// damage to a unit." P1 discards Asajj herself, so at damage she is not in hand — the setup lapses, nothing is paid,
#// and Merrin deals her own 2.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LOF_160:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Asajj_Ventress
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:CARDID:LOF_160
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1RESAVAILABLE:5
P1NODECISION

---

# MaulsBaseOrUnits_TheBasePickIsOfferedToo
#// TWI_135 Darth Maul ("can attack 2 units instead of 1") with 2 legal enemy units first asks Base or Units; picking
#// Base is a base attack like any other — the third way a base becomes the target — so Asajj is offered there too.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: IC27_147

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Base
- P1>AnswerDecision:Asajj_Ventress

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_147
P1HANDCARD:0:TWI_135
P1NODECISION
