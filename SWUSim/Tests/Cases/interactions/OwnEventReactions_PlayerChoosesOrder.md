# AhsokaAndBossk_PlayerIsAskedForTheOrder
#// CR 7.6.9: "If a player must resolve multiple triggered abilities on cards they control at the same time, that
#// player chooses the order in which to resolve those abilities." TS26_08 Ahsoka Tano's leader reaction and SOR_182
#// Bossk both trigger on "When you play an event", so once HMW_101 Trust Yourself has resolved (Shield, then the
#// search), P1 picks which resolves first with the engine's standard ordering prompt. They used to resolve in a
#// fixed order (Bossk, then Ahsoka). EffectStack-0 = Bossk (unit observers are collected first), -1 = Ahsoka.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:SEC_080
## EXPECT
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1DECISIONPARAM:EffectStack-0&EffectStack-1
P1LEADER:READY
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# AhsokaChosenFirst_BosskWaits
#// P1 resolves Ahsoka first: her offer comes now, while Bossk has still dealt nothing, and she looks at the
#// post-search top card (SOR_095).
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:YES
## EXPECT
P1DECISIONPARAM:@SOR_095&Play&Discard&Leave
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# AhsokaChosenFirst_ThenBosskResolves
#// Same line through to the end: Ahsoka leaves SOR_095 on top, then Bossk is offered with no second ordering
#// prompt (one trigger left) and deals 2 to Consular Security Force.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:Leave
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED
P1DECKTOPCARD:SOR_095
P1NODECISION

---

# BosskChosenFirst_ThenAhsokaWithNoSecondPrompt
#// The other order: Bossk first (deal 2), then Ahsoka's offer follows straight away (one trigger left, no
#// ordering prompt) and still looks at the post-search top card.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1DECISIONTOOLTIP:Exhaust_Ahsoka_to_look_at_the_top_card?
P1LEADER:READY

---

# BosskChosenFirst_ActionStaysOpenUntilAhsokaResolves
#// ⚠ No P1OnlyActions (it makes TURNPLAYER unobservable). The play must not close while the second ordered
#// trigger is still waiting: after Bossk resolves, P1 still holds the turn and owes Ahsoka's offer.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_101
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1DECISIONTOOLTIP:Exhaust_Ahsoka_to_look_at_the_top_card?
TURNPLAYER:1

---

# BothOrdered_TurnPassesOnce
#// Both triggers resolved (Bossk, then Ahsoka discarding SOR_095): the turn passes exactly once.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_101
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Discard
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1DISCARDUNIT:1:CARDID:SOR_095
P1LEADER:EXHAUSTED
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# BosskAlone_NoOrderingPrompt
#// Control: a single "when you play an event" reaction resolves with no ordering prompt — after the search, the
#// next prompt is Bossk's own.
## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:SEC_080
## EXPECT
P1DECISIONTOOLTIP:Deal_2_damage_to_a_unit

---

# BosskAlone_TurnPassesOnce
#// Control, end to end and without P1OnlyActions: Bossk alone deals 2 and the turn passes exactly once.
## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_101
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# AltCostEvent_AhsokaAndBosskAreOrdered
#// The alternate-cost event branch (SOR_199 Bamboozle, '|OWN') collects the same own reactions, so it orders
#// them too. Pay normally, exhaust Consular Security Force, then P1 picks Ahsoka first.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_199 SOR_199]
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1DECISIONPARAM:EffectStack-0&EffectStack-1

---

# TwinSuns_Seat4_AhsokaAndBosskAreOrdered
#// Seat 4 of a four-seat Twin Suns game: the ordering prompt goes to P4 (the controller). Bossk is the only unit,
#// so the Shield needs no pick.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 4
WithInitiativePlayer: 4
WithP4Leader: TS26_08:1:0
WithP4Leader2: TS26_06:1:0
WithP4Resources: 6
WithP4Hand: HMW_101
WithP4GroundArena: SOR_182:1:0
WithP4Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P4>PlayHand:0
- P4>AnswerDecision:SEC_080
## EXPECT
P4DECISIONTOOLTIP:Choose_trigger_to_resolve
P4DECISIONPARAM:EffectStack-0&EffectStack-1
P4LEADER:READY

---

# TwinSuns_Seat4_AhsokaFirstThenBossk
#// Seat 4, Ahsoka chosen first: she discards SOR_095, then Bossk is offered (no second ordering prompt).
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 4
WithInitiativePlayer: 4
WithP4Leader: TS26_08:1:0
WithP4Leader2: TS26_06:1:0
WithP4Resources: 6
WithP4Hand: HMW_101
WithP4GroundArena: SOR_182:1:0
WithP4Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P4>PlayHand:0
- P4>AnswerDecision:SEC_080
- P4>AnswerDecision:EffectStack-1
- P4>AnswerDecision:YES
- P4>AnswerDecision:Discard
## EXPECT
P4DISCARDUNIT:1:CARDID:SOR_095
P4LEADER:EXHAUSTED
P4DECISIONTOOLTIP:Deal_2_damage_to_a_unit

---

# BosskAndFives_UnitObserversAreOrderedToo
#// Not just Ahsoka: any two of the group order. SOR_182 Bossk and TWI_216 Fives ("When you play an event: You may put
#// a Clone unit from your discard pile on the bottom of your deck. If you do, draw a card.") both react to the event,
#// so P1 is asked for the order (one entry each).
## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1GroundArena: [SOR_182:1:0 TWI_216:1:0]
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:SEC_080
## EXPECT
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1DECISIONPARAM:EffectStack-0&EffectStack-1
