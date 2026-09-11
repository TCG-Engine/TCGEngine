# AbilityDamage_NamesItsSource
#// Game-log sweep, phase 2 — EFFECT lines (2026-09-11). Before this, no effect funnel wrote to the log:
#// ability damage, heals, defeats, bounces, captures, token upgrades, token units and control changes all
#// happened silently. Each line now names the ability that caused it (the log-source context, set by the
#// trigger dispatchers / event dispatch / Actions / card-named continuations), or reads passively when no
#// ability is resolving. Damage is hooked in SWUQueueDamageAnim — the universal damage hook — so every
#// damage path (ability, indirect, split, base) is covered by one line of code.
#// SOR_172 Open Fire (Aggression, 3): "Deal 4 damage to a unit." A 3/7 survives it.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
LOGCONTAINS:P1's [[SOR_172|Open Fire]] dealt 4 damage to P2's [[SOR_046|Consular Security Force]]
LOGCOUNT:1:dealt 4 damage

---

# LethalAbilityDamage_DamageThenDefeat
#// The same event on a 3/3: the damage line, then the defeat it causes.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
LOGCONTAINS:P1's [[SOR_172|Open Fire]] dealt 4 damage to P2's [[SOR_095|Battlefield Marine]]
LOGCONTAINS:P1's [[SOR_172|Open Fire]] defeated P2's [[SOR_095|Battlefield Marine]]

---

# DirectDefeat_Takedown
#// SOR_077 Takedown (Vigilance, 4): "Defeat a unit with 5 or less remaining HP." — a defeat with no damage.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_077
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
LOGCONTAINS:P1's [[SOR_077|Takedown]] defeated P2's [[SOR_095|Battlefield Marine]]
LOGCOUNT:1:defeated P2's

---

# ShieldToken_Given
#// SOR_073 Moment of Peace (Vigilance, 1): "Give a Shield token to a unit." Token upgrades are named by
#// KIND ("a Shield token"), never by the reprint CardID.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_073
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
LOGCONTAINS:P1's [[SOR_073|Moment of Peace]] gave a Shield token to P1's [[SOR_095|Battlefield Marine]]

---

# Heal_LogsTheAmountActuallyHealed
#// IBH_013 Recovery (Heroism, 3): "Heal 5 damage from a unit." The unit has only 3 damage, so the line
#// says 3 — the amount healed, not the printed 5.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_013
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
LOGCONTAINS:P1's [[IBH_013|Recovery]] healed 3 damage from P1's [[SOR_046|Consular Security Force]]

---

# Bounce_Waylay
#// SOR_222 Waylay (Cunning, 3): "Return a non-leader unit to its owner's hand."

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_222
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:1
LOGCONTAINS:P1's [[SOR_222|Waylay]] returned P2's [[SEC_080|Imperial Dark Trooper]] to its owner's hand

---

# TakeControl_ChangeOfHeart
#// SOR_224 Change of Heart (Cunning, 6): "Take control of a non-leader unit."

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SOR_224
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
LOGCONTAINS:P1's [[SOR_224|Change of Heart]] took control of P2's [[SEC_080|Imperial Dark Trooper]]

---

# UpgradeDefeated_Confiscate
#// SOR_251 Confiscate (neutral, 1): "Defeat an upgrade."

## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_251
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
LOGCONTAINS:P1's [[SOR_251|Confiscate]] defeated [[SOR_120|Academy Training]] on P2's [[SEC_080|Imperial Dark Trooper]]

---

# Capture_TakeCaptive
#// SHD_131 Take Captive (Command, 3): "A friendly unit captures an enemy non-leader unit in the same arena."

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_131
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
LOGCONTAINS:P1's [[SOR_046|Consular Security Force]] captured P2's [[SEC_080|Imperial Dark Trooper]] ([[SHD_131|Take Captive]])

---

# TokenUnit_Created_ByALeaderAction
#// JTL_016 Admiral Ackbar's leader Action: exhaust a non-leader unit; its controller creates an X-Wing.
#// The source here comes from the ACTION dispatch, not a trigger.

## GIVEN
CommonSetup: gyw/rrk/{myLeader:JTL_016;myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
LOGCONTAINS:P1's [[JTL_016|Admiral Ackbar]] created an X-Wing token

---

# DeckOut_NoAbilityResolving_ReadsPassively
#// Regroup draw on an empty deck: nothing is resolving, so the line is passive — and it is ONE line of 6
#// (the deck-out ruling: one event per draw instruction). Moment of Peace is played FIRST so a source IS
#// set earlier in the round: the line must not inherit it ("P1's Moment of Peace dealt 6 damage…"). Two
#// clears guard that — at the end of every action and at the regroup boundary — so a mutation has to
#// remove BOTH to see this section red.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_073
WithP1GroundArena: SOR_095:1:0
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1BASEDMG:6
LOGCONTAINS:P1's base took 6 damage
LOGCOUNT:1:base took
LOGCOUNT:0:dealt 6 damage

---

# Attack_Base_TheSummaryCarriesTheDamage
#// COMBAT damage is not a separate line: the ATTACK summary (written after combat resolves) carries it,
#// so the log never shows a hit before the attack that caused it.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
LOGCONTAINS:P1's [[SOR_095|Battlefield Marine]] attacked P2's base for 3 damage
LOGCOUNT:0:took 3 damage
LOGCOUNT:0:dealt 3 damage

---

# Attack_Unit_TheSummaryCarriesBothSides
#// A unit attack: what the attacker dealt and took, then the defeats. 3/3 into a 3/7 — the attacker dies.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
LOGCONTAINS:P1's [[SOR_095|Battlefield Marine]] attacked P2's [[SOR_046|Consular Security Force]] — dealt 3, took 3 — [[SOR_095|Battlefield Marine]] defeated
LOGCOUNT:0:took 3 damage

---

# TwinSuns_Attack_NamesTheRightBase
#// Found by the sweep: the ATTACK line labelled the base 'P' . (3 - attacker), a two-seat shortcut, so
#// attacking seat 3's base was logged as "P2's base". The owner now comes from the attacked mzID.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:p3Base-0

## EXPECT
SEATCOUNT:3
LOGCONTAINS:attacked P3's base for 3 damage
LOGCOUNT:0:attacked P2's base

---

# ContinuationSource_TheMaraudersOwnPicks
#// A card-named CUSTOM continuation re-establishes its card as the source (GameBeforeCustomHandler). HMW_125
#// The Marauder's damage picks resolve in HMW_125#0, BEFORE any trigger dispatcher runs for it (it has no
#// When Played), so this is the only thing naming it: without the hook the two hits read passively ("took 1
#// damage"), or — played through Crix Madine — would be credited to Crix.
#// 7 resources, two friendly units chosen: 7 − 2 = 5 paid.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: HMW_125
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1SPACEARENACOUNT:1
LOGCONTAINS:P1's [[HMW_125|The Marauder]] dealt 1 damage to P1's [[SOR_046|Consular Security Force]]
LOGCONTAINS:P1's [[HMW_125|The Marauder]] dealt 1 damage to P1's [[SOR_095|Battlefield Marine]]
