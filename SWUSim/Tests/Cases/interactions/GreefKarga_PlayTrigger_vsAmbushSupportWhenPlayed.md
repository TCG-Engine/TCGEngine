#// Greef Karga (ASH_017) — "When you play or create a unit" — against the played unit's OWN triggers: Ambush,
#// Support and When Played. They all trigger on the same play and are all P1's, so P1 orders them (CR 7.6.9).
#// The order is load-bearing whenever the Advantage token (+1/+0, defeated when the unit's attack ends) could
#// ride along on the Ambush attack.
#//
#// FOUND BY: sweep retro #2 of run 2 (2026-09-13). Uncovered combo shapes, all from aggro_greef:
#//   ORDER ASH_017 + LAW_067:WhenPlayed (169×) · ASH_017 + LAW_219:Ambush (92×) · ASH_017 + JTL_096:Ambush (90×)
#//   · ASH_017 + JTL_096:Ambush + JTL_096:WhenPlayed (86×) · ASH_017 + ASH_253:Support (78×) · ASH_017 +
#//   SEC_204:Ambush (53×) · ASH_017 + JTL_096:WhenPlayed (49×).
#//   All sections were green on first run: this file is REGRESSION COVERAGE, not a bug.
#//
#// THE RULES.
#//   - ASH_017 front: "When you play or create a unit: You may exhaust this leader. If you do, give an Advantage
#//     token to that unit." Deployed: "When you play or create a unit: Give an Advantage token to that unit."
#//     The deployed half is still a TRIGGER, so it is ordered too (it shows as ASH_017#1 on the stack).
#//   - ASH_T02 Advantage: +1/+0, "When attached unit's attack or defense ends: Defeat this upgrade."
#//   - Blue Leader rulings (03/06/2025): Ambush triggers with the When Played abilities, and "Blue Leader's Ambush
#//     keyword and 'When Played' ability can be resolved in either order". See jtl/BlueLeader_AmbushAfterMove.md
#//     for the Ambush the move re-arms.
#//   - Yellow Aces Bomber ruling (07/21/2026): its On Attack "deals 2 damage to a base if the ATTACKING unit is
#//     upgraded", not if the Bomber is.
#// Cards: LAW_219 Anakin's Podracer 3/2 (Ambush; deals combat damage first if no other unit attacked this
#//   phase) · SEC_028 Trayus Acolyte 2/4 (vanilla) · JTL_096 Blue Leader 3/3 space (Ambush; "When Played: You may
#//   pay 2 resources. If you do, move this unit to the ground arena and give 2 Experience tokens to it") ·
#//   ASH_030 Marrok 2/6 · LAW_067 Jyn Erso 2/2 ("When Played: Either give an Experience token to a unit or
#//   exhaust a unit") · ASH_253 Yellow Aces Bomber (Support; "On Attack: If this unit is upgraded, deal 2 damage
#//   to a base") · SOR_095 Battlefield Marine 3/3 · SOR_120 Academy Training (+2/+2) · SEC_204 Blue Ace 4/5
#//   space (Ambush; "On Attack: Ready an exhausted enemy unit").
#// Step note: a "#" in a step line starts a comment, so the deployed Greef trigger (ASH_017#1) is picked by
#//   its EffectStack index, not by ResolveTrigger.
#//
# Podracer_GreefAndAmbush_ThePlayerOrdersThem
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: LAW_219
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1LEADER:READY

---

# Podracer_GreefFirst_TheAdvantageRidesTheAmbush_4PowerDefeatsTheAcolyte
#// Greef first: the Podracer is 4/2 when it Ambushes, strikes first, and defeats the 2/4 Acolyte untouched.
#//   The Advantage is then defeated at the end of the attack. No P1OnlyActions: the turn must pass exactly once.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 8
WithP1Hand: LAW_219
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:LAW_219
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1LEADER:EXHAUSTED
TURNPLAYER:2

---

# Podracer_AmbushFirst_3PowerLeavesTheAcolyteAlive_ThePodracerDies_GreefHasNothingToGive
#// Ambush first: 3 damage does not defeat the Acolyte, whose 2 back defeats the 3/2 Podracer. Greef's trigger
#//   then has no "that unit" left: no prompt, and Greef stays ready.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: LAW_219
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:Ambush
- P1>AnswerDecision:YES
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:READY

---

# DeployedGreef_TheAutoGiveIsStillATrigger_OrderedFirst_ItArmsTheAmbush
#// Deployed Greef gives the Advantage with no exhaust and no prompt, but it is still a trigger of the same play:
#//   the ordering prompt appears. Resolved first, it makes the Ambush a 4-power one that defeats the Acolyte.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: LAW_219
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:LAW_219
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0

---

# BlueLeader_GreefFirst_TheAdvantageSurvivesTheMove_6PowerAmbushDefeatsMarrok
#// The enemy holds only a ground unit, so Blue Leader's Ambush is not bagged on the play (nothing to attack from
#//   space). Greef first puts the Advantage on it IN SPACE; the When Played then moves it to the ground with 2
#//   Experience, which re-arms the Ambush. It attacks as 3 +1 +2 = 6 and defeats the 2/6 Marrok, taking 2.
#//   Afterwards only the 2 Experience remain (the Advantage went with the attack).
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_096
WithP2GroundArena: ASH_030:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1LEADER:EXHAUSTED

---

# CONTROL_BlueLeader_GreefDeclined_5PowerAmbush_MarrokSurvives
#// The same line with Greef's exhaust declined: Blue Leader attacks as 5 and Marrok survives on 5 damage. This
#//   is what makes the section above prove the Advantage crossed the arena move.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_096
WithP2GroundArena: ASH_030:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:NO
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:READY

---

# BlueLeader_MoveFirst_TheReArmedAmbushJoinsGreefInTheOrderingPool
#// The other order: the When Played first. The move re-arms the Ambush, which goes back into the pool beside
#//   Greef's still-pending trigger, so P1 is asked to order again.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_096
WithP2GroundArena: ASH_030:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:YES
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# BlueLeader_MoveFirst_ThenGreef_ThenAmbush_StillDefeatsMarrok
#// Continuing that order: Greef, then the re-armed Ambush. Same 6-power attack as Greef-first.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_096
WithP2GroundArena: ASH_030:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:YES
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED

---

# Jyn_GreefFirst_ThenJynGivesHerselfExperience_BothTokensStay
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: LAW_067
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
- P1>AnswerDecision:GiveExperience
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_067
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:4
P1LEADER:EXHAUSTED

---

# Jyn_WhenPlayedFirst_ExhaustsTheEnemy_ThenGreefStillGivesTheAdvantage
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: LAW_067
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:Exhaust
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1LEADER:EXHAUSTED

---

# YellowAces_GreefFirst_TheBomberHoldsTheAdvantage_AnUnupgradedAttackerDealsNoExtra
#// Greef's Advantage goes on the BOMBER (the played unit). Support then attacks with the Marine, which gains
#//   "If this unit is upgraded, deal 2 damage to a base". "This unit" is the ATTACKER (ruling 07/21/2026): the
#//   Marine is not upgraded, so there is no base prompt and the base takes the Marine's 3 alone.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: ASH_253
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1NODECISION
P2BASEDMG:3
P1BASEDMG:0
P1SPACEARENAUNIT:0:CARDID:ASH_253
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# CONTROL_YellowAces_AnUpgradedAttacker_Deals2ToEitherBase
#// The same line, with the Marine wearing Academy Training (+2/+2): the gained On Attack fires, and "a base"
#//   is unqualified, so both bases are offered. Their base takes 5 +2 = 7.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: ASH_253
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# CONTROL_YellowAces_AnUpgradedAttacker_TheirBaseTakes7
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: ASH_253
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:7
P1BASEDMG:0

---

# BlueAce_GreefFirst_A5PowerAmbush_DefeatsTheEnemyBlueAce
#// Greef first: Blue Ace Ambushes as 5/5 and defeats the enemy 4/5 Blue Ace, taking 4. Its own On Attack
#//   readied the exhausted defender first, which does not matter to the combat.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: SEC_204
WithP2SpaceArena: SEC_204:0:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SEC_204
P1SPACEARENAUNIT:0:DAMAGE:4
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# Patrol_GreefFirst_SupportAttackerGainsSaboteur_TheBaseIsOfferedPastASentinel
#// Retro #3 (ASH_017 + ASH_222:Support, 183×). ASH_222 Unsanctioned Patrol (Support, Saboteur): the Support
#//   attacker gains Saboteur, so P2's Sentinel (SOR_066 System Patrol Craft) no longer blocks the base.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_222
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_066:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SELECTABLEEXACT:theirSpaceArena-0&theirBase-0
P1SPACEARENAUNIT:1:CARDID:ASH_222
P1SPACEARENAUNIT:1:ADVANTAGECOUNT:1

---

# CONTROL_Patrol_NoSupport_ThePlainAttackIsHeldBySentinel
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_066:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:0
P2SPACEARENAUNIT:0:DAMAGE:2

---

# BD1_GreefFirst_ThenBD1sWhenPlayed_TheMarineGetsPlusOneAndSaboteur
#// Retro #3 (ASH_017 + LOF_191:WhenPlayed, 162×). LOF_191 BD-1: "When Played: Choose another friendly unit.
#//   While this unit is in play, the chosen unit gets +1/+0 and gains Saboteur." The lone other unit is chosen
#//   automatically.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: LOF_191
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_191
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# Amidala_GreefFirst_HerSpiesAreCreatedLater_GreefIsExhausted_NoPrompt
#// Retro #3 (ASH_017 + SEC_101:WhenPlayed, 73×). Greef first gives Amidala the Advantage. Her When Played then
#//   creates 2 Spy tokens, which triggers Greef ("or create a unit") twice more, but he is already exhausted:
#//   no prompt and no Advantage on the Spies. (The OTHER order is the stale-gate RED in
#//   GreefKarga_StaleExhaustGate_And_NestedPlayLayer.md.)
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SEC_101
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SEC_101
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:2:ADVANTAGECOUNT:0

---

# Ackbar_GreefFirst_ThenAckbarDefeatsHimself_TheFreeSpaceUnitsFindGreefExhausted
#// Retro #3/#4 (ASH_017 + ASH_110:WhenPlayed, 76×/36×). Greef first: Ackbar takes the Advantage, then defeats
#//   himself and plays two space units for free (cost 2 + 2 ≤ 5). Greef is exhausted, so neither prompts.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_110
WithP1Deck: [SOR_237 SOR_225 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_225,SOR_237
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P1SPACEARENAUNIT:1:ADVANTAGECOUNT:0
P1LEADER:EXHAUSTED
