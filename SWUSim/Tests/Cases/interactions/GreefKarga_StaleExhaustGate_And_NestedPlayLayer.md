#// Greef Karga (ASH_017, front): "When you play or create a unit: You may exhaust this leader. If you do, give an
#// Advantage token to that unit." Two candidate engine bugs, both seen when TWO Greef triggers are pending.
#//
#// FIXED 2026-09-13 (all RED sections green; full suite 11895/0): RED 1: Ash017Trigger (LeaderAbilities.php) and
#//   ASH_017#0 re-check that Greef is ready when the offer RESOLVES, and created-unit offers are chained through
#//   ASH_017#ASK (a stored list, so each asks only after the previous answer). RED 2: EffectStack layers
#//   (GameLogic.php, _SWUEsLayers — CR 7.6.11): a play's triggers added while older ones are pending form a nested
#//   layer that the ordering prompt and the bare resume see first.
#//
#// FOUND BY: sweep retro #3 of run 2 (2026-09-13): ORDER ASH_017 + ASH_017 + ASH_222:Support, and
#//   greef.aurra_red.s057, where Admiral Ackbar fetched Yellow Aces Bomber and the log reads
#//   "Greef gave an Advantage token to Yellow Aces Bomber" then "Greef had no effect". Reproduced below with
#//   LOF_100 Kelleran Beq, which stays in play and so makes the result visible.
#//
#// ★ WAS RED 1 — THE EXHAUST GATE IS CHECKED WHEN THE PROMPT IS QUEUED, NOT WHEN IT RESOLVES (2 sections + a
#//   GREEN control).
#//   The ASH_017#0 handler (cards/ash/GreefKarga_GraciousMagistrate.php), on YES, sets Ready=false and gives
#//   the Advantage without checking that Greef was ready. A second Greef prompt queued while he was still
#//   ready therefore still asks "Exhaust Greef…?" after the first one exhausted him, and a second YES gives a
#//   SECOND Advantage for the same single exhaust. Probe 2026-09-13: Kelleran and the Bomber each ended with
#//   an Advantage. Correct: an exhausted leader cannot pay "exhaust this leader", so the later trigger does
#//   nothing ("If you do" fails) and should not prompt (as in ash/GreefKarga…::ExhaustedLeader_NoPromptForNextUnit).
#//   ⚠ ash/GreefKarga_GraciousMagistrate.md::TokenCreation_OneAdvantage covers the two-token case but only
#//   asserts the token count and EXHAUSTED; it passes with the second prompt still pending. Left untouched
#//   (it is a confirmed test); the RED section here asserts what it leaves out.
#//
#// ★ WAS RED 2 — NESTED TRIGGERS ARE MERGED INTO THE OUTER LAYER (1 section, + a GREEN control).
#//   Playing Kelleran triggers his When Played and Greef-for-Kelleran together. Resolving the When Played plays
#//   Yellow Aces Bomber, whose Support and Greef-for-the-Bomber trigger DURING it, so they are NESTED (CR
#//   7.6.11): "…must be resolved before any other abilities triggered at the same time as ability 'A'". The
#//   engine instead offers all three in one pool, Greef-for-Kelleran included. This holds under EITHER model
#//   of the still-unruled search-and-play timing question (memory "nested-play-triggers-wait-for-outer-ability":
#//   do a fetched card's triggers resolve mid-ability or after it?). In both, the outer layer's leftover
#//   trigger comes LAST.
#//
#// Cards: LOF_100 Kelleran Beq 7/7 (cost 7; "When Played: Search the top 7 cards of your deck for a unit, reveal
#//   it, and play it. It costs 3 resources less.") · ASH_253 Yellow Aces Bomber (cost 3, Support) · JTL_254
#//   Dedicated Wingmen (creates two X-Wing tokens) · SOR_095 Battlefield Marine.
#//
# RED_TwoTokensCreated_GreefExhaustsOnce_TheSecondTriggerMustNotPrompt
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: JTL_254
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1LEADER:EXHAUSTED
P1NODECISION
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
P1SPACEARENAUNIT:1:ADVANTAGECOUNT:0

---

# RED_KelleranFetchesTheBomber_GreefUsedOnTheBomber_KelleranMustNotGetOneToo
#// Kelleran's When Played first; it fetches the Bomber (no ready unit for its Support, so only Greef-for-the-
#//   Bomber is nested). YES on that: Greef is exhausted. Greef-for-Kelleran must now do nothing.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: LOF_100
WithP1Deck: [ASH_253 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:ASH_253
- P1>AnswerDecision:YES
## EXPECT
P1LEADER:EXHAUSTED
P1NODECISION
P1SPACEARENAUNIT:0:CARDID:ASH_253
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_100
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# CONTROL_KelleranFetchesTheBomber_NestedGreefDeclined_TheOuterOneMayStillBeUsed
#// The other half of the section above: the nested Greef-for-the-Bomber is DECLINED, so Greef stays ready and
#//   Greef-for-Kelleran may still exhaust him. This prompt must survive any fix of the gate.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: LOF_100
WithP1Deck: [ASH_253 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:ASH_253
- P1>AnswerDecision:NO
- P1>AnswerDecision:YES
## EXPECT
P1LEADER:EXHAUSTED
P1NODECISION
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1

---

# RED_KelleranFetchesTheBomber_OnlyTheNestedLayerIsOffered_NotTheOuterGreef
#// With a ready Marine, the Bomber's Support has an attacker. After Kelleran's When Played, the pool must be
#//   the NESTED layer only: the Bomber's Support and Greef-for-the-Bomber. Before the fix the engine also offered the
#//   outer Greef-for-Kelleran (EffectStack-0 here: 0 = Greef-for-Kelleran, 1 = Support, 2 = Greef-for-the-
#//   Bomber). If a fix re-lays the stack, re-derive the indices: the claim is "exactly the two nested ones".
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: LOF_100
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [ASH_253 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:ASH_253
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1SELECTABLEEXACT:EffectStack-1&EffectStack-2

---

# CONTROL_KelleranFirstInTheOuterPool_ThePlayerOrdersKelleranAndGreef
#// The outer layer itself IS a free choice: Kelleran's When Played and Greef-for-Kelleran triggered together.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: LOF_100
WithP1Deck: [ASH_253 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:EffectStack-0&EffectStack-1

---

# TwoTokensCreated_DeclineTheFirst_AcrossARequestBoundary_TheSecondIsStillOffered
#// The fix chains the created-unit offers through a stored list (ASH_017#ASK), so it must survive a request
#//   boundary, and a DECLINE must pass the offer on to the next token rather than end the chain.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: JTL_254
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:NO
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
## EXPECT
P1LEADER:EXHAUSTED
P1NODECISION
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P1SPACEARENAUNIT:1:ADVANTAGECOUNT:1
