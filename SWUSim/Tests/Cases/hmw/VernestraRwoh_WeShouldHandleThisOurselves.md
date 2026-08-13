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
