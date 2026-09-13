#// On Attack abilities that read THE ATTACKER'S OWN POWER must see the attack-time bonuses: Raid and every
#// "+N/+0 for this attack". Both are active from Begin attack (CR 3.3; memory "while-attacking-starts-at-begin-
#// attack"), which is before On Attack abilities resolve. Official ruling on ASH_009 Ahsoka Tano – Trust in the
#// Force (07/21/2026): "Abilities that refer to a card's power include temporary modifiers."
#//
#// FIXED 2026-09-13 (all RED sections green; full suite 11895/0): the four handlers read the attacker's own power
#//   with ObjectCurrentPowerInAttack.
#//
#// FOUND BY: sweep retro #6 of run 2 (2026-09-13): ORDER ASH_009:SupportOnAttack + ASH_203:OnAttack (49×), Ahsoka
#//   yellow. Mando's N-1 Starfighter paid its "+2/+0 for this attack" and Ahsoka's granted On Attack then offered
#//   nothing. A source scan found three more handlers with the same miss.
#//
#// ★ WAS RED (4 sections, one per card) — candidate engine bug. The engine keeps attack-time bonuses OUT of
#//   ObjectCurrentPower on purpose (SWUAddAttackPowerBonus: a SWU_ATK_POWER_N marker that SWUCombatDamage adds at
#//   damage time; Raid the same) and provides ObjectCurrentPowerInAttack (GameLogic.php) for "a card that reads
#//   power during an attack"; its comment tells the SHD_004 Rey story. Only Rey uses it. These four On Attack
#//   handlers read ObjectCurrentPower on themselves and so see the pre-bonus number:
#//     ASH_009 Ahsoka Tano (deployed, also granted to her Support attacker): "…a unit with less power than this unit"
#//     LOF_163 Quinlan Vos: "If this unit has 6 or more power, you may deal 2 damage to an enemy base."
#//     LAW_064 Zuckuss: "…you may deal damage equal to this unit's power to a ground unit."
#//     TWI_198 Enfys Nest: "…return an enemy non-leader unit with less power than this unit…"
#//   Fix shape (APPLIED 2026-09-13): read the attacker's own power with
#//   ObjectCurrentPowerInAttack in these On Attack handlers.
#//
#// Cards: ASH_203 Mando's N-1 Starfighter 1/3 ("On Attack: You may exhaust a friendly (non-upgrade) leader. If you
#//   do, this unit gets +2/+0 for this attack.") · SOR_220 Surprise Strike ("Attack with a unit. It gets +3/+0 for
#//   this attack.") · SEC_028 Trayus Acolyte 2/4 · SHD_052 Sugi (Bounty Hunter) · SOR_046 Consular Security Force
#//   3/7 · SEC_118 6/5 vanilla · SOR_095 Battlefield Marine 3/3 · SOR_T01 Experience token.
#//
# RED_Ahsoka_SupportAttackerN1PaysForPlusTwo_TheAcolyteIsNowWeaker_AndIsOffered
#// N-1 first: Ahsoka (the deployed leader unit) is exhausted to pay, and N-1 attacks as 3. Ahsoka's granted On
#//   Attack then compares against 3: the 2-power Acolyte qualifies. Before the fix nothing was offered (it compared to 1).
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1SpaceArena: ASH_203:1:0
WithP1GroundArena: SEC_028:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:mySpaceArena-0
- P1>ResolveTrigger:OnAttack
- P1>AnswerDecision:YES
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Give_+2/+0_to_a_unit_with_less_power_than_this_unit
P1SELECTABLEHAS:myGroundArena-0

---

# RED_QuinlanVos_SurpriseStrikeMakesHimSeven_HisSixOrMoreClauseIsOffered
## GIVEN
CommonSetup: rrk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_220
WithP1GroundArena: LOF_163:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Deal_2_to_an_enemy_base?

---

# CONTROL_QuinlanVos_SixPowerFromTwoExperience_TheClauseIsOffered
#// A PERSISTENT +2 (two Experience) is visible, so the clause itself works; only the attack-time bonus is missed.
## GIVEN
CommonSetup: rrk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_163:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Deal_2_to_an_enemy_base?

---

# RED_Zuckuss_SurpriseStrikeMakesHimSix_HeDealsSix
## GIVEN
CommonSetup: rrk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_220
WithP1GroundArena: LAW_064:1:0
WithP1GroundArena: SHD_052:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# RED_EnfysNest_SurpriseStrikeMakesHerEight_ASixPowerEnemyCanBeReturned
## GIVEN
CommonSetup: yyw/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_220
WithP1GroundArena: TWI_198:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_118:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
