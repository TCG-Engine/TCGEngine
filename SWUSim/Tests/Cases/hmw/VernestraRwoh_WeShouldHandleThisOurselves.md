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

---

# FromHand_TwoFiveCostDonors_PalpatineAndInfernoSquad_BothFire
#// ── THE MAUL / DATA VAULT DECK (reported 2026-09-14) ─────────────────────────────────────────────────
#// The deck: HMW_016 Maul (Cunning/Villainy) on JTL_024 Data Vault (Command), so Vernestra's
#// Command+Cunning is on-aspect. The combo the player wants: bottom HMW_110 Emperor Palpatine AND HMW_202
#// Inferno Squad — BOTH cost exactly 5, the top of "5 or less" — and fire both When Playeds off her.
#// Reported against another engine as "if you select two units that cost exactly 5 it will not work at
#// all". This is the plain from-hand CONTROL for the Nightbrother / Maul sections that follow: same
#// donors, same board, no nested play.
#// Palpatine (ordered first) steals the enemy SOR_095 (cost 2) and gives it 2 Weakness (-1/-1 each), so
#// it sits on P1's side as a 1/1. Inferno Squad then deals 1 to the enemy SOR_046 (cost 4, so Palpatine
#// could not have taken it) and gives it a Weakness.

## GIVEN
CommonSetup: gyk/rrk/{myResources:6;myLeader:HMW_016;myBase:JTL_024}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [HMW_110 HMW_202]
WithP1Deck: [SOR_095 SOR_128]
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:0
P1DECKCOUNT:4
P1RESAVAILABLE:0
P1NODECISION

---

# ViaNightbrother_TheAdditionalCostIsOffered
#// HMW_204 Nightbrother plays Vernestra from the DISCARD at 3 less. Per CR step 3.c an additional cost
#// is determined and paid on EVERY play however it was initiated, so her "bottom up to 2 units" must be
#// offered here too — over the discard units costing 5 or less, and never herself (cost 6, and she is
#// the card being played).
#// 10 resources: 7 for Nightbrother, 6 - 3 = 3 for her.

## GIVEN
CommonSetup: gyk/rrk/{myResources:10;myLeader:HMW_016;myBase:JTL_024}
P1OnlyActions: true
WithP1Hand: HMW_204
WithP1Discard: [HMW_048 HMW_110 HMW_202]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-1&myDiscard-2

---

# ViaNightbrother_PalpatineAndInfernoSquad_BothFire
#// THE REPORTED COMBO, end to end: Nightbrother -> Vernestra from the discard -> bottom Palpatine and
#// Inferno Squad (both cost exactly 5) -> both gained When Playeds resolve. Nightbrother's riders still
#// apply to her: she enters READY and carries the defeat-at-regroup marker.

## GIVEN
CommonSetup: gyk/rrk/{myResources:10;myLeader:HMW_016;myBase:JTL_024}
P1OnlyActions: true
WithP1Hand: HMW_204
WithP1Discard: [HMW_048 HMW_110 HMW_202]
WithP1Deck: [SOR_095 SOR_128]
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myDiscard-1&myDiscard-2
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_204
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:0
P1DECKCOUNT:4
P1RESAVAILABLE:0
P1NODECISION

---

# ViaNightbrother_SheIsLASTInTheDiscard_TheDonorsBeforeHerStillBottom
#// ⚠ THE DISCARD REINDEX. When Vernestra is played FROM the discard, her own additional cost removes
#// cards from that SAME pile before she is paid for. Here she sits at myDiscard-2 behind both donors;
#// bottoming them compacts the pile, so a play still addressed to "myDiscard-2" points past the end and
#// the play silently does nothing — the donors are gone and she never arrives. She must be found again
#// after the cost is paid, not by her pre-cost position.

## GIVEN
CommonSetup: gyk/rrk/{myResources:10;myLeader:HMW_016;myBase:JTL_024}
P1OnlyActions: true
WithP1Hand: HMW_204
WithP1Discard: [HMW_110 HMW_202 HMW_048]
WithP1Deck: [SOR_095 SOR_128]
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-2
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:0
P1DECKCOUNT:4
P1RESAVAILABLE:0
P1NODECISION

---

# ViaNightbrother_SheIsMIDDLE_TheWrongCardIsNeverPlayed
#// The dangerous shape of the same reindex: with a card AFTER her, her stale position does not point
#// past the end, it points at a DIFFERENT CARD. [Palpatine, Vernestra, Inferno Squad, ASH_242]: bottoming
#// 0 and 2 leaves [Vernestra, ASH_242], and a play addressed to "myDiscard-1" would put ASH_242 (a vanilla
#// 4-cost 5/4, cost 1 at -3) into play instead of her. She must be the unit that arrives, ASH_242 must
#// stay in the discard, and she must be charged her own 3 (ASH_242 at -3 would be 1).

## GIVEN
CommonSetup: gyk/rrk/{myResources:10;myLeader:HMW_016;myBase:JTL_024}
P1OnlyActions: true
WithP1Hand: HMW_204
WithP1Discard: [HMW_110 HMW_048 HMW_202 ASH_242]
WithP1Deck: [SOR_095 SOR_128]
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-1
- P1>AnswerDecision:myDiscard-0&myDiscard-2
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:ASH_242
P1RESAVAILABLE:0

---

# ViaMaulDeploy_SheWasDefeatedThisPhase_CostOfferedAndBothGainsFire
#// HMW_016 Maul's When Deployed: "play a unit that was defeated this phase from your discard pile. It
#// costs 5 less." Vernestra (pre-damaged to 1 remaining HP) trades into the enemy SOR_095, which both
#// kills her and stamps the defeated-this-phase flag. The discard already held both donors, so she is
#// appended LAST — the reindex shape again, on a second nested-play path.
#// Maul deploys (free — the threshold is a condition) and brings her back for 6 - 5 = 1. Her additional
#// cost is offered, Palpatine + Inferno Squad are bottomed, and both gained abilities resolve. With
#// SOR_095 dead in the trade, Palpatine finds no enemy unit costing 3 or less (SOR_046 costs 4) and
#// offers nothing; Inferno Squad pings SOR_046.
#// Both gains still go on the stack and are ORDERED (a trigger with no legal target still triggers) —
#// as their own layer, above Maul's own pending Shielded (EffectStack-0), per CR 7.6.11.
#// 8 resources - 1 = 7 left.

## GIVEN
CommonSetup: gyk/rrk/{myResources:8;myLeader:HMW_016;myBase:JTL_024}
P1OnlyActions: true
WithP1GroundArena: HMW_048:1:4
WithP1Discard: [HMW_110 HMW_202]
WithP1Deck: [SOR_095 SOR_128]
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myDiscard-2
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_048
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:0
P1DECKCOUNT:4
P1RESAVAILABLE:7
P1NODECISION

---

# ViaMaulFront_CostOffered_SheIsDefeated_BothGainsStillResolve
#// HMW_016 Maul's front Action: "Play a unit from your hand. It costs 1 less. Then, defeat it. (When
#// Played abilities resolve after the unit is defeated.)" Her additional cost is part of that play, so it
#// is offered; she is then defeated; and the gained When Playeds — which triggered when she entered —
#// still resolve afterwards, exactly as her own printed one would.
#// 6 - 1 = 5 resources. Palpatine steals SOR_095; Inferno Squad pings SOR_046.

## GIVEN
CommonSetup: gyk/rrk/{myResources:5;myLeader:HMW_016;myBase:JTL_024}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [HMW_110 HMW_202]
WithP1Deck: [SOR_095 SOR_128]
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_048
P1DECKCOUNT:4
P1RESAVAILABLE:0
P1NODECISION

---

# ViaNightbrother_RidersSurviveARequestBoundaryAtTheCost_DefeatedAtRegroup
#// ⚠ THE DEFERRED LEG OF NIGHTBROTHER'S RIDERS. Her cost is a real choice, so the play stops there and
#// finishes in a LATER request — after Nightbrother's handler has returned and nulled its play-grant
#// globals. "Enters play ready" and "defeat it at the next regroup" must ride the gamestate
#// (SWU_PENDING_PLAY_GRANTS), not a global: the boundary sits exactly between the pick and the play.
#// The attack is the READY receipt (5 to the base); the regroup sweep then defeats her, so she is the
#// only card in the discard (Crumb went to the deck bottom as her cost). Crumb's gained heal: 5 -> 4.
#// Both decks seeded — an empty deck at regroup would put CR 6.1 damage on a base.

## GIVEN
CommonSetup: gyk/rrk/{myResources:10;myLeader:HMW_016;myBase:JTL_024;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_204
WithP1Discard: [HMW_048 SHD_080]
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myDiscard-1
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:5
P1BASEDMG:4
P1GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:HMW_204
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_048

---

# ViaNightbrother_TheActionStaysOpenWhileHerGainsResolve
#// No P1OnlyActions: the turn really alternates. Paused at the ORDERING prompt for her two gained
#// triggers, the action is still P1's — nothing may have handed the turn over mid-resolution.

## GIVEN
CommonSetup: gyk/rrk/{myResources:10;myLeader:HMW_016;myBase:JTL_024}
WithActivePlayer: 1
WithP1Hand: HMW_204
WithP1Discard: [HMW_048 HMW_110 HMW_202]
WithP1Deck: [SOR_095 SOR_128]
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myDiscard-1&myDiscard-2

## EXPECT
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
TURNPLAYER:1

---

# ViaNightbrother_ExactlyOneTurnSwap
#// The same play driven to the end with the turn really alternating: one P1 action -> P2's turn. The
#// deferred play legitimately ATTEMPTS a second close (the resumed play and Nightbrother's own trigger
#// resume both reach SWUAfterAction) and the ledger refuses it, so NOEXTRAACTION is not asserted —
#// TURNPLAYER is what proves no extra action happened (docs/action-close-deferrals.md §4).

## GIVEN
CommonSetup: gyk/rrk/{myResources:10;myLeader:HMW_016;myBase:JTL_024}
WithActivePlayer: 1
WithP1Hand: HMW_204
WithP1Discard: [HMW_048 HMW_110 HMW_202]
WithP1Deck: [SOR_095 SOR_128]
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myDiscard-1&myDiscard-2
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
TURNPLAYER:2

---

# ViaMaulFront_ExactlyOneTurnSwap
#// Maul's front Action with the turn really alternating. The "then defeat it" step now runs where the
#// deferred play finishes, and it still owns the close: one P1 action -> P2's turn.

## GIVEN
CommonSetup: gyk/rrk/{myResources:5;myLeader:HMW_016;myBase:JTL_024}
WithActivePlayer: 1
WithP1Hand: HMW_048
WithP1Discard: [HMW_110 HMW_202]
WithP1Deck: [SOR_095 SOR_128]
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1DISCARDUNIT:0:CARDID:HMW_048
TURNPLAYER:2

---

# ViaMaulFront_SurvivesARequestBoundaryAtTheCost
#// ⚠ The "then defeat it" step is a FUNCTION NAME carried in the gamestate, not a closure in memory:
#// the boundary between the hand pick and her cost pick is a fresh process. She must still be defeated
#// after entering, and Crumb's gained heal must still resolve (5 -> 4).

## GIVEN
CommonSetup: gyk/rrk/{myResources:5;myLeader:HMW_016;myBase:JTL_024;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SHD_080]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_048
P1BASEDMG:4
P1DECKCOUNT:3
P1RESAVAILABLE:0
P1NODECISION

---

# ViaMaulFront_SecondCopy_TheNewOneIsDefeated_TheOldStands
#// Vernestra is UNIQUE. A second copy played through Maul's front with a donor picked takes the deferred
#// path AND collides with the copy already in play. Maul's step defeats the NEW copy (found by its
#// marker, never by position), the old one stays, and the gained heal still resolves (5 -> 4).

## GIVEN
CommonSetup: gyk/rrk/{myResources:5;myLeader:HMW_016;myBase:JTL_024;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: HMW_048:1:2
WithP1Hand: HMW_048
WithP1Discard: [SHD_080]
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1GROUNDARENAUNIT:0:DAMAGE:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_048
P1BASEDMG:4
P1NODECISION

---

# ViaNightbrother_ExploitUnit_ExploitIsOffered_RidersSurvive
#// The same seam for EXPLOIT, which is also an additional cost Nightbrother's old direct play skipped.
#// TWI_182 Infiltrating Demolisher (Exploit 1, cost 4) from the discard: 4 - 3 = 1, and exploiting the
#// friendly SOR_128 takes 2 more, floored at 0 — so the 1 resource left after Nightbrother stays. The
#// exploit picker defers the play, and the unit must still enter READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:8;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: HMW_204
WithP1Discard: TWI_182
WithP1GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_182
P1GROUNDARENAUNIT:0:READY
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_128
P1RESAVAILABLE:1

---

# ViaNightbrother_CreditPrompt_RidersSurvive
#// The Credit payment prompt ALSO sits between the start of a play and the play itself — it is not an
#// additional cost, and it is the one the old per-card snapshots never covered. P1 declines Credits for
#// Nightbrother, then spends one on LOF_236 (6 - 3 = 3, one Credit pays 1): the unit must still enter
#// READY. 10 resources - 7 - 2 = 1 left, 1 Credit left.

## GIVEN
CommonSetup: yyk/rrk/{myResources:10;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: HMW_204
WithP1Discard: LOF_236
WithP1Credits: 2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_236
P1GROUNDARENAUNIT:0:READY
P1CREDITCOUNT:1
P1RESAVAILABLE:1
