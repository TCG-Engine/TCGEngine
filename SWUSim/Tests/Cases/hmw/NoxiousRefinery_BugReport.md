# PlayedFromHand_P1_AttachesToBase_ThenFiresAtRegroup
#// HMW_160 Noxious Refinery — bug report 2026-10-09 (Discord, no gamestate): "Base upgrade Noxious
#// Refinery did not let me proc it." Every section in NoxiousRefinery.md SEEDS the upgrade onto the base
#// (WithP1BaseUpgrade), so none of them proved the card ever gets THERE through the real play path.
#// This one plays it from hand (rrk = on-aspect, cost 4 of 5 resources), then both players pass into
#// regroup: the attach must land on P1's base and the regroup-start trigger must fire off it.
#// (Verified the same on the production entry, EngineRunAction, one fresh process per request.)
## GIVEN
CommonSetup: rrk/grw/{myResources:5;myhandCardIds:HMW_160}
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>Drain
## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASE:UPGRADE:0:CARDID:HMW_160
P1HANDCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:1
LOGCOUNT:1:revealed

---

# PlayedFromHand_P2_AttachesToBase_ThenFiresAtRegroup
#// The P2 mirror: played from P2's hand onto P2's base; at regroup P2 reveals and P1's unit takes the 1.
## GIVEN
CommonSetup: grw/rrk/{theirResources:5;theirhandCardIds:HMW_160}
WithP2Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>Pass
- P2>Pass
- P2>Drain
## EXPECT
P2BASE:UPGRADECOUNT:1
P2BASE:UPGRADE:0:CARDID:HMW_160
P1GROUNDARENAUNIT:0:DAMAGE:1
LOGCOUNT:1:revealed

---

# P1Owned_TwoEnemyUnits_PromptIsOnP1_PhaseWaits
#// With TWO enemy units the "deal 1" is a real choice: it must be pending on the OWNER's (P1's) queue,
#// offering exactly the two enemy units, and the regroup must not have moved past it (deck still 4 — the
#// draw step has not run). P2, who sent the last request (the second pass), must have nothing pending.
## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
## WHEN
- P1>Pass
- P2>Pass
- P1>Drain
## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P1DECISIONTOOLTIP:Deal_1_damage_to_an_enemy_unit_(Noxious_Refinery)
P2NODECISION
P1DECKCOUNT:4

---

# P2Owned_TwoEnemyUnits_PromptIsOnP2_PhaseWaits
#// The P2 mirror: P2 owns the Refinery AND sends the last pass. The choose sits on P2's queue.
#// ⚠ NO `P2>Drain` here, on purpose: the harness's Drain verb (GameTestAdapter::drainQueue) runs
#// ExecuteStaticMethods(2) while $playerID is still 1 (passAction restores the caller's frame), so
#// MZCountChoices reads "theirGroundArena-…" from P1's side, counts 0, and auto-PASSES the choose — the
#// regroup then runs on to RES with no damage. That is a TEST-HARNESS frame bug (production's
#// ProcessGoldfishAutomation drains each seat in its own frame since 2026-09-14, and the same board on
#// EngineRunAction keeps the prompt). Adding `- P2>Drain` turns this section red for that reason only.
## GIVEN
CommonSetup: grw/rrk/{myResources:5}
WithP2BaseUpgrade: HMW_160
WithP2Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP1GroundArena: [SOR_046:1:0 SEC_080:1:0]
## WHEN
- P1>Pass
- P2>Pass
## EXPECT
P2SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P2DECISIONTOOLTIP:Deal_1_damage_to_an_enemy_unit_(Noxious_Refinery)
P1NODECISION
P2DECKCOUNT:4

---

# P2Owned_TwoEnemyUnits_AnswerLandsAndRegroupResumes
#// ... answered: the chosen unit takes 1, the other does not, and the regroup resumes to RES.
## GIVEN
CommonSetup: grw/rrk/{myResources:5}
WithP2BaseUpgrade: HMW_160
WithP2Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP1GroundArena: [SOR_046:1:0 SEC_080:1:0]
## WHEN
- P1>Pass
- P2>Pass
- P2>AnswerDecision:theirGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:1
P2DECKCOUNT:2
PHASE:RES

---

# NonAggressionTop_TwoEnemyUnits_NoPrompt_RegroupContinues
#// The non-Aggression reveal with a real choice available: no damage prompt at all, both units
#// untouched, and the regroup runs straight through to its own resource prompt. This is the case a
#// player is most likely to read as "it didn't proc": the only trace is the reveal LOG line (there is
#// no REVEAL popup — the card does not route through DoRevealCard).
## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_095 SOR_128 SOR_046 SEC_080]
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
## WHEN
- P1>Pass
- P2>Pass
- P1>Drain
## EXPECT
P1DECISIONTOOLTIP:Resource_up_to_1_card
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
LOGCOUNT:1:revealed
P1DECKCOUNT:2
PHASE:RES

---

# WithDarkSanctum_RefineryOrderedFirst_RevealsAggressionAndDeals
#// P1 has a SECOND, different regroup-start ability (HMW_070 Dark Sanctum, same base): the pair goes
#// through the ordering prompt (EffectStack-0 = Sanctum, EffectStack-1 = Refinery). Refinery first:
#// it reveals SOR_128 (Aggression) and the 1 is dealt; then the Sanctum draws 1 and the regroup draws 2.
## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: [HMW_070 HMW_160]
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
## WHEN
- P1>Pass
- P2>Pass
- P1>Drain
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:0
P1DECKCOUNT:1
PHASE:RES

---

# WithDarkSanctum_SanctumOrderedFirst_DrawsTheAggressionCard_NoDamage
#// Same board, Sanctum first: it DRAWS the Aggression top card, so the Refinery then reveals SOR_095
#// (Command) and correctly deals nothing. Correct rules behaviour — and a second way a player can see
#// "no proc" from an Aggression card they knew was on top.
## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: [HMW_070 HMW_160]
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
## WHEN
- P1>Pass
- P2>Pass
- P1>Drain
- P1>AnswerDecision:EffectStack-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P1HANDCARD:0:SOR_128
P1DECKCOUNT:1
PHASE:RES

---

# WithOpponentRegroupTrigger_OpponentFirst_RefineryStillPrompts
#// P2 controls ASH_159 Alphabet Squadron U-Wing (its own regroup-start trigger). Both players' triggers
#// pending → the initiative player (P1) picks who resolves first. P1 answers NO (opponent first): P2
#// gives its Advantage, THEN the Refinery's choose surfaces on P1's queue (it must not be lost behind the
#// cross-seat resume), and answering it finishes the regroup.
## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
WithP2SpaceArena: ASH_159:1:0
## WHEN
- P1>Pass
- P2>Pass
- P1>Drain
- P1>AnswerDecision:NO
- P2>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENAUNIT:0:CARDID:ASH_159
P2SPACEARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P1DECKCOUNT:2
PHASE:RES

---

# TwinSuns_P1Owned_OffersEveryOpponentsUnits
#// Twin Suns (3 seats): "an enemy unit" fans out across BOTH opponents, and the choose sits on seat 1.
## GIVEN
CommonSetup3P: rrk/grw/grw
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SEC_080:1:0
WithP2Deck: [SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
## EXPECT
P1SELECTABLEEXACT:p2GroundArena-0&p3GroundArena-0
P1DECKCOUNT:4

---

# TwinSuns_P3Owned_AnswerLands
#// Seat 3 owns it: the choose offers seats 1 and 2's units, and the answer lands on the picked one.
## GIVEN
CommonSetup3P: grw/grw/rrk
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3BaseUpgrade: HMW_160
WithP3Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P3>AnswerDecision:p2GroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:1
P3DECKCOUNT:2
