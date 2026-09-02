# PlaysBottomingTwo_GainsAWhenPlayed
#// HMW_048 Vernestra Rwoh, We Should Handle This Ourselves — Unit (Ground) 5/5, cost 6,
#// [Command][Cunning], Force/Jedi, unique, Legendary.
#// "Sentinel
#//  As an additional cost to play this unit, put up to 2 units that each cost 5 or less from your
#//  discard pile on the bottom of your deck. This unit gains those units' 'When Played' abilities for
#//  this phase."
#// Sentinel is registry-wired (generic coverage) — no code. gyw covers Command+Cunning → cost exactly 6.
#// The additional cost bottoms SHD_080 Salacious Crumb + SOR_046 (vanilla); Crumb's mandatory
#// "When Played: heal 1 from your base" is GAINED and fires as HER entry trigger: base 5 → 4.
#// Deck grows by 2 (bottom order RANDOM per ruling — assertions stay order-agnostic), discard empties.
#// COVERAGE: offer=Offer_CostFiveInSixOut_EventsNever (boundary pair inside the offer) ·
#//           decline=UpToTwo_PickingZero · boundary=the 5-vs-6 offer pair · control=N/A (no owner-scoped
#//           zone crosses control here; "your discard" is the caster's own) ·
#//           reqboundary=SurvivesTheRequestBoundary (the gain stamps ride the entering OBJECT across the
#//           cost-pick decision)

## GIVEN
CommonSetup: gyw/rrk/{myResources:6;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SHD_080 SOR_046]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1BASEDMG:4
P1DECKCOUNT:4
P1DISCARDCOUNT:0
P1RESAVAILABLE:0

---

# Offer_CostFiveInSixOut_EventsNever
#// The pick pool: UNIT cards costing 5 or less, from your discard. IBH_076 Rampaging Wampa (cost 5) is
#// the boundary IN; IBH_056 Ground Assault AT-AT (cost 6) is the boundary OUT; SOR_171 Mission Briefing
#// is a cost-3 EVENT and is out on TYPE. Exactly the two legal units are offered.
#// (First cut used SEC_118 as the "cost 6" card — it costs 5; its 6 is POWER. The offer was right.)

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SOR_095 IBH_076 IBH_056 SOR_171]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1

---

# UpToTwo_PickingZero
#// "up to 2" includes ZERO: declining the pick is a legal payment. She still enters play, nothing is
#// bottomed, nothing is gained, and no decision dangles.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SHD_080 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:2
P1NODECISION

---

# EmptyDiscard_NoPromptCleanPlay
#// Nothing to offer = no prompt at all; the play itself is unaffected (the cost is "up to", so an empty
#// pool is trivially payable).

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_048

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1NODECISION

---

# GainedAbilityTreatsHERAsThisUnit
#// "This unit gains those units' abilities" — inside gained text, "this unit" is VERNESTRA. JTL_051 Red
#// Squadron X-Wing's "When Played: you may deal 2 damage to this unit. If you do, draw a card" is
#// gained; YES puts the 2 on HER (5/5 → 2 damage) and draws.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [JTL_051]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1GROUNDARENAUNIT:0:DAMAGE:2
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# VanillaIsALegalPick_GainsNothing
#// A unit with NO When Played is still a legal pick — bottoming for deck recursion is a real reason.
#// It bottoms, she gains nothing, no crash, no dangling decision.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SOR_046]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1DECKCOUNT:3
P1DISCARDCOUNT:0
P1NODECISION

---

# ShieldedDonor_SheGainsNOShield
#// RULING (2026-08-13): Shielded is a KEYWORD, not a "When Played" ability — the same ruling hardened on
#// LOF_197's NoRepeat_ShieldedKeyword. Bottoming SOR_207 Crafty Smuggler (keyword-only Shielded) is a
#// legal pick that grants NOTHING: Vernestra enters with no shield and no upgrade.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SOR_207]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# AmbushDonor_NoAmbushEntry
#// The Ambush half of the same ruling: bottoming SHD_210 Cloud-Rider (keyword-only Ambush) grants no
#// ambush attack — she enters exhausted with no attack offer even though an enemy unit is present.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SHD_210]
WithP1Deck: [SOR_095 SOR_128]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# TwoGains_BothFire
#// Two donors with mandatory When Playeds: SHD_080 Crumb (heal 1 from your base) + LOF_133 (deal 2 to a
#// Force unit — Vernestra is the only Force unit, so it auto-resolves onto HER). Both gained abilities
#// resolve: base 5 → 4 AND she sits at 2 damage.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SHD_080 LOF_133]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1GROUNDARENAUNIT:0:DAMAGE:2
P1BASEDMG:4
P1DECKCOUNT:4

---

# SurvivesTheRequestBoundary
#// The gains are chosen at the COST step and fire at entry — the stamps must ride the entering OBJECT,
#// not an in-memory global. A boundary between the pick and the trigger resolution is what a real
#// two-request game does.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SHD_080]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>SimulateRequestBoundary
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1BASEDMG:4

---

# NestedDirectPlay_SkipsTheAdditionalCost_LikeExploit
#// ⚠ PINS A DOCUMENTED ENGINE-FAMILY GAP, not the card's rule. A play dispatched by another effect
#// through the direct ActivateCard route (SOR_219 Sneak Attack here) never passes through the
#// hand-play path that owns additional costs — so Vernestra's cost is SKIPPED, exactly as Exploit is
#// skipped on those same routes today. Per the CR an additional cost applies on every play; when the
#// family seam is fixed (one fix for Exploit AND this card — see hmw-implement.md), this section is the
#// one that must FLIP: she should prompt for the discard picks even here.
#// Today: she enters ready (Sneak), no cost prompt, no gains, the donor stays in the discard (joined by
#// the played event: SHD_080 + SOR_219 = 2), and only the two plays are paid (2 + 3 = 5 → 0 left).

## GIVEN
CommonSetup: gyw/rrk/{myResources:5;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: [SOR_219 HMW_048]
WithP1Discard: [SHD_080]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1GROUNDARENAUNIT:0:READY
P1BASEDMG:5
P1DISCARDCOUNT:2
P1RESAVAILABLE:0
P1NODECISION

---

# GainedBlueLeader_SheIsALREADYGround_StillGetsTheExperience
#// ⚠ HMW_048 × JTL_096 Blue Leader — "DO AS MUCH AS YOU CAN", and a near-miss worth pinning.
#// USER RULING 2026-09-02: Blue Leader's gained ability reads "You may pay 2 resources. If you do, move
#// this unit to the ground arena and give 2 Experience tokens to it." The "If you do" is gated on
#// PAYING THE 2 — nothing else. Vernestra is a GROUND unit, so when she gains this ability the move half
#// is already satisfied and does nothing; the Experience half still resolves in full. An ability does as
#// much of itself as it can.
#//
#// Blue Leader (cost 3, so ≤ 5) is bottomed from the discard as her additional cost, she gains his When
#// Played, and it fires with HER as "this unit". 8 resources: 6 for her, 2 for the ability, 0 left.
#// She ends a 7/7 — 5/5 plus two Experience — still in the ground arena, still the only unit there.
#//
#// ⚠ WHY THIS IS A NEAR-MISS RATHER THAN AN OBVIOUS PASS. The continuation
#// (JTL_096_MOVE_PAY, GameLogic) reads:
#//        if (!$paidOk) break;                       // correct: the "if you do" is the PAYMENT
#//        $newMz = SWUMoveUnitBetweenArenas($mz, 'GroundArena');
#//        if ($newMz === '') break;                  // <-- would SWALLOW both Experience tokens
#//        for ($i = 0; $i < 2; $i++) DoGiveExperienceToken($player, $newMz);
#// It only works because SWUMoveUnitBetweenArenas treats a same-arena target as a remove-and-re-add
#// rather than an impossible move, so it returns a live mzID instead of ''. Had it short-circuited on
#// "already there", the tokens would vanish and every other section of both cards would still pass.
#// This section is the thing standing between that helper and a silent regression.

## GIVEN
CommonSetup: gyw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [JTL_096]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
#// The gained ability's own "you may pay 2" — accepted.
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P1SPACEARENACOUNT:0
P1RESAVAILABLE:0

---

# GainedBlueLeader_DeclineThePayment_NoExperience
#// HMW_048 × JTL_096 — the other side of the same ruling. The "If you do" IS gated on the payment, so
#// declining the 2 gives her nothing: no Experience, and the 2 resources are still ready.
#// Paired with the section above, the two pin the gate to the PAYMENT rather than to the move: accepted
#// pays and grants, declined does neither. She remains a plain 5/5.

## GIVEN
CommonSetup: gyw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [JTL_096]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:2

---

# PlayedByAnotherCardsAbility_AdditionalCostStillApplies
#// DISPATCH-PATH cell + the engine seam it exposed (reported 2026-09-02).
#// HMW_048 played NOT from a Play a Card action but by ANOTHER card's ability — LOF_094 Jedi Consular
#// ("Action: Play a unit from your hand. It costs 2 less"). Per CR step 3.c an additional cost is
#// determined and paid on EVERY play, however the play was initiated, so her "bottom up to 2 units from
#// your discard" must still be offered here. Before the fix the shared DISCOUNT_PLAY_FROM_HAND
#// continuation entered via ActivateCard — the second HALF of the play ceremony — so the additional
#// cost was never reached and she landed as a plain 5/5 with an untouched discard.
#// Both donors are picked, so BOTH gained When-Played abilities go on the stack and must be ORDERED:
#// LAW_067 Jyn Erso (give an Experience token) resolved first, then JTL_096 Blue Leader (pay 2 for 2
#// Experience). She is printed cost 6, so 6 resources - (6-2 Consular discount) = 2, then Blue Leader's
#// 2 = 0 left, and she is 5/5 + 3 Experience = 8/8. The discard empties (both donors bottomed).
#// ⚠ The fix is deliberately unitOnly: CR 17.c forbids using Piloting through a "play a unit" grant.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Force: true
WithP1Hand: HMW_048
WithP1GroundArena: LOF_094:1:0
WithP1Discard: [JTL_096 LAW_067]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
#// The additional cost — the prompt that did not exist before the fix.
- P1>AnswerDecision:myDiscard-0&myDiscard-1
#// Order the two gained triggers: Jyn Erso first.
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:GiveExperience
- P1>AnswerDecision:myGroundArena-1
#// Blue Leader's "you may pay 2" — accepted.
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_048
P1GROUNDARENAUNIT:1:POWER:8
P1GROUNDARENAUNIT:1:HP:8
P1GROUNDARENAUNIT:1:UPGRADECOUNT:3
P1RESAVAILABLE:0
P1DISCARDCOUNT:0

---

# PlayedByAnotherCardsAbility_DeclineTheAdditionalCost
#// NEGATIVE control for the section above: the additional cost is "up to 2", so declining it entirely
#// must still play her (an "up to" cost of zero is payable) and leave the discard intact. This is what
#// separates "the additional cost fires" from "the play is now gated on the discard" — without it the
#// section above would also pass if the prompt were mandatory. She enters a plain 5/5, and only the
#// Consular-discounted cost is spent: 6 - (6-2) = 2.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Force: true
WithP1Hand: HMW_048
WithP1GroundArena: LOF_094:1:0
WithP1Discard: [JTL_096 LAW_067]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_048
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1RESAVAILABLE:2
P1DISCARDCOUNT:2
