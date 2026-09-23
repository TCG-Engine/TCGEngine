#// ASH_060 Cobb Vanth (2/6, Grit): "When you play another unit: You may deal 2 damage to this unit. If you do, give
#// a Shield token to that unit." It triggers with the played unit's own When Played / Shielded, and P1 orders
#// them (CR 7.6.9). The order matters whenever the played unit heals, or protects Cobb.
#//
#// FIXED 2026-09-13 (all RED sections green; full suite 11895/0): EffectStack layers (GameLogic.php, _SWUEsLayers —
#//   CR 7.6.11); see GreefKarga_StaleExhaustGate_And_NestedPlayLayer.md.
#//
#// FOUND BY: sweep retros #5–#6 of run 2 (2026-09-13), lando_blue: ORDER ASH_044:WhenPlayed + ASH_060
#//   (92×) · ASH_060 + ASH_062:Shielded (67×) · ASH_060 + ASH_065:WhenPlayed (40×) · ASH_060 + LAW_018:WhenPlayed
#//   + SEC_046:WhenPlayed (23×), plus PLOT LAW_018 → SEC_046 (148×).
#//
#// RULING USED: Malakili (07/14/2025): "If an ability preceding 'if you do' would deal damage to a friendly unit,
#//   but Malakili prevents that damage from being dealt to that unit, the effect following 'if you do' still
#//   resolves." So Cobb's Shield still comes when The Mandalorian prevents Cobb's 2.
#//
#// ★ WAS RED (2 sections) — the nested-layer merge again (first found in GreefKarga_StaleExhaustGate_And_
#//   NestedPlayLayer.md with Kelleran Beq), here reached through a PLOT. Lando's deploy triggers his When
#//   Deployed and the Plot window together. Resolving the Plot window plays Galen, and Galen's When Played and
#//   Cobb's trigger fire DURING it, so they are nested (CR 7.6.11; CR 19.b: "Any abilities triggered by playing
#//   a card with Plot must be resolved before playing the next card with Plot"). The engine pools all three
#//   with Lando's still-pending When Deployed.
#//
#// Cards: ASH_044 Barriss Offee ("When Played: Heal up to 2 damage from a unit. Give an Advantage token to it
#//   for each damage healed this way.") · ASH_062 The Mandalorian (Shielded; prevents damage to another friendly
#//   unit by defeating a Shield on himself) · ASH_065 Home One (space; "When Played: Heal all damage from each
#//   friendly unit.") · LAW_018 Lando Calrissian (deployed: "When Deployed: You may defeat a friendly Credit
#//   token. If you do, create 3 Credit tokens.") · SEC_046 Galen Erso (Plot; "When Played: Name a card…").
#//
# Barriss_CobbFirst_BarrissHealsCobbsTwo_AndGivesHimTwoAdvantage
## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1GroundArena: ASH_060:1:0
WithP1Hand: ASH_044
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_060
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_060
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# Barriss_BarrissFirst_NothingToHealYet_CobbKeepsHisTwo
## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1GroundArena: ASH_060:1:0
WithP1Hand: ASH_044
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# Mando_ShieldedFirst_HePreventsCobbsTwo_AndCobbsShieldStillComes
#// Shielded first: Mando has a Shield when Cobb deals himself 2, so Mando may defeat it to prevent that damage.
#//   Per the Malakili ruling the "if you do" still resolves: Mando ends with Cobb's Shield (one in all).
## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1GroundArena: ASH_060:1:0
WithP1Hand: ASH_062
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:Shielded
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:ASH_062
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1NODECISION

---

# Mando_CobbFirst_NoShieldYet_NothingToPreventWith_TwoShieldsAfter
## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1GroundArena: ASH_060:1:0
WithP1Hand: ASH_062
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_060
- P1>AnswerDecision:YES
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:SHIELDCOUNT:2

---

# HomeOne_CobbFirst_HomeOneHealsAllOfCobbsDamage
## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1GroundArena: ASH_060:1:0
WithP1Hand: ASH_065
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_060
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:CARDID:ASH_065
P1SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# HomeOne_HomeOneFirst_ThereIsNothingToHealYet_CobbKeepsHisTwo
## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1GroundArena: ASH_060:1:0
WithP1Hand: ASH_065
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# RED_LandoDeploy_GalenPlotted_OnlyTheNestedLayerIsOffered
#// After Galen is played from the Plot window the pool must be Galen's When Played and Cobb's trigger ONLY.
#//   Before the fix it was EffectStack-0 = Lando's When Deployed, 1 = Galen, 2 = Cobb. If a fix re-lays the stack,
#//   re-derive the indices: the claim is "exactly the two nested ones".
## GIVEN
CommonSetup: bbw/rrk/{myLeader:LAW_018;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_046:1,7:SOR_095:1
WithP1GroundArena: ASH_060:1:0
## WHEN
- P1>DeployLeader
- P1>ResolveTrigger:SWU_PLOT_WINDOW
- P1>AnswerDecision:myResources-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1SELECTABLEEXACT:EffectStack-1&EffectStack-2

---

# RED_LandoDeploy_GalenPlotted_NestedFirst_ThenCobbAlone_GalenGetsTheShield
#// The whole line in rules order: Galen first (names a card), then Cobb, the last NESTED trigger, resolves on
#//   its own (YES). Only then Lando's When Deployed, which has no Credit token to defeat and does nothing.
#//   Before the fix the step after Galen was a second ordering prompt (Lando + Cobb), so "YES" was refused.
## GIVEN
CommonSetup: bbw/rrk/{myLeader:LAW_018;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_046:1,7:SOR_095:1
WithP1GroundArena: ASH_060:1:0
## WHEN
- P1>DeployLeader
- P1>ResolveTrigger:SWU_PLOT_WINDOW
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:Battlefield Marine
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_060
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:2:CARDID:SEC_046
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1
P1NODECISION
