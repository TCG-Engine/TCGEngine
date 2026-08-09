# DeployedAttackEndPeekPlay
#// TS26_08 Ahsoka Tano (leader deployed, 3/6) — Raid 1 + When Attack Ends: look at the top card; play it
#// (costs 1 less), discard it, or leave it. Deployed Ahsoka attacks the enemy base (Raid 1 → 4), then plays
#// SEC_080 from the top of the deck.
## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08:1:1;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Play
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:1:CARDID:SEC_080

---

# FrontEventPeekPlayTop
#// TS26_08 Ahsoka Tano (leader front) — When you play an event: you may exhaust this leader; if you do,
#// look at the top card of your deck and play it (paying its cost), discard it, or leave it. Playing the
#// neutral event Confiscate triggers Ahsoka; exhausting her plays SEC_080 from the top of the deck.
## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08;myResources:12;handCardIds:SOR_251}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Play
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1LEADER:EXHAUSTED

---

# BlankedEventStillTriggersAhsoka
#// TS26_08 Ahsoka "When you play an event" fires on the ACT of playing an event, even when the event is
#// Relentless-blanked (SOR_089) — playing a blanked event is still "playing an event"; only the event's own
#// ability is lost. Same as FrontEventPeekPlayTop but P2 controls Relentless, so P1's first event this round
#// (Confiscate SOR_251) is blanked. Ahsoka must STILL trigger: exhausting her plays SEC_080 from the deck
#// top. (Before the preamble was moved out of the blanked/Galen gate, Ahsoka did NOT trigger on a blanked
#// event and this outcome was unreachable.)

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08;myResources:12;handCardIds:SOR_251}
SkipPreGame: true
P1OnlyActions: true
WithP2SpaceArena: SOR_089:1:0
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Play

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1LEADER:EXHAUSTED

---

# FrontEventPeekDiscardTop
#// TS26_08 Ahsoka Tano (leader front) — the peek's DISCARD branch. Playing Confiscate (SOR_251) and
#// exhausting Ahsoka looks at the top card; choosing Discard mills it. Deck 2 -> 1, and the discard pile
#// holds both the resolved event and the milled top card.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08;myResources:12;handCardIds:SOR_251}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Discard

## EXPECT
P1DECKCOUNT:1
P1DISCARDCOUNT:2
P1GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED

---

# FrontEventPeekLeaveOnTop
#// TS26_08 Ahsoka Tano (leader front) — the peek's LEAVE branch. Choosing Leave puts nothing anywhere:
#// the deck keeps both cards and SEC_080 is still the top card. Only the played event is in the discard.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08;myResources:12;handCardIds:SOR_251}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Leave

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SEC_080
P1DISCARDCOUNT:1
P1LEADER:EXHAUSTED

---

# FrontEventDeclineExhaust
#// TS26_08 Ahsoka Tano (leader front) — "You MAY exhaust this leader." Declining the exhaust skips the
#// whole ability: Ahsoka stays ready, no peek happens, and the deck is untouched.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08;myResources:12;handCardIds:SOR_251}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1LEADER:READY
P1DECKCOUNT:2
P1GROUNDARENACOUNT:0

---

# FrontUnitPlayDoesNotTrigger
#// TS26_08 Ahsoka Tano (leader front) — the trigger is "when you play an EVENT". Playing a unit
#// (SOR_095 Battlefield Marine) must not offer the exhaust, leaving Ahsoka ready and the deck untouched.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08;myResources:12}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1LEADER:READY
P1NODECISION
P1DECKCOUNT:2

---

# FrontUpgradePlayDoesNotTrigger
#// TS26_08 Ahsoka Tano (leader front) — playing an UPGRADE (SOR_166 Infiltrator's Skill onto a friendly
#// unit) is not playing an event, so Ahsoka does not trigger.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08;myResources:12}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_166
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1LEADER:READY
P1NODECISION
P1DECKCOUNT:2

---

# FrontOpponentEventDoesNotTrigger
#// TS26_08 Ahsoka Tano (leader front) — "when YOU play an event". P2 playing Confiscate must not trigger
#// P1's Ahsoka: she stays ready, P1's deck is untouched, and P1 is offered no decision.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08;myResources:12;theirResources:12}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Hand: SOR_251
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P2>PlayHand:0

## EXPECT
P1LEADER:READY
P1DECKCOUNT:2
P1NODECISION

---

# FrontEmptyDeckStillExhausts
#// TS26_08 Ahsoka Tano (leader front) — with an empty deck there is no top card to look at. Exhausting
#// Ahsoka still happens (that is the cost of the "if you do"), and the peek simply finds nothing, leaving
#// no dangling decision.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08;myResources:12;handCardIds:SOR_251}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:EXHAUSTED
P1NODECISION

---

# FrontPlayIsFullCost_UnaffordableLeavesOnTop
#// TS26_08 Ahsoka Tano (leader front) — the FRONT peek plays the top card at FULL cost (no discount; the
#// -1 belongs to the deployed side only). SEC_080 is Command/Villainy under a Cunning base + Cunning/
#// Heroism leader, so both aspects are uncovered: 2 + 4 = 6. After paying 1 for Confiscate only 5 remain,
#// so choosing Play cannot pay for it — the card stays on top of the deck and no resources are spent.
#// This is the discriminating counterpart to DeployedPlayCostsOneLess, which succeeds at exactly 5.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08;myResources:6;handCardIds:SOR_251}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Play

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:2
P1DECKTOPCARD:SEC_080
P1RESAVAILABLE:5

---

# DeployedAttackEndDiscard
#// TS26_08 Ahsoka Tano (leader deployed) — the When Attack Ends peek's DISCARD branch. Ahsoka attacks the
#// enemy base (3 power + Raid 1 = 4), then mills the top card instead of playing it.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08:1:1;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Discard

## EXPECT
P2BASEDMG:4
P1DECKCOUNT:1
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:1

---

# DeployedAttackEndLeave
#// TS26_08 Ahsoka Tano (leader deployed) — the When Attack Ends peek's LEAVE branch. The deck keeps both
#// cards with SEC_080 still on top, and nothing reaches the discard pile.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08:1:1;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Leave

## EXPECT
P2BASEDMG:4
P1DECKCOUNT:2
P1DECKTOPCARD:SEC_080
P1DISCARDCOUNT:0

---

# DeployedPlayCostsOneLess
#// TS26_08 Ahsoka Tano (leader deployed) — "If you play it, it costs 1 resource less." SEC_080 costs 6
#// here (2 printed + 4 for two uncovered aspects), so with exactly 5 resources it is only playable through
#// the discount. It enters play and drains the pool to 0 — proving the -1 is applied, not merely that the
#// card was affordable anyway.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08:1:1;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Play

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1RESAVAILABLE:0

---

# DeployedAttackEndFiresEvenIfAhsokaDies
#// TS26_08 Ahsoka Tano (leader deployed) — CR 16.c: a "When Attack Ends" ability still triggers when the
#// unit it is on is defeated by combat damage. Ahsoka (3/6, Raid 1 -> 4 power) attacks a 7/6 Army of the
#// Dead: she deals 4 and takes 7, so she is defeated. The peek must still happen — here the top card is
#// discarded. Before the CR 16.c path existed, the attack-end collection's surviving-attacker gate
#// swallowed this trigger and the deck was never touched.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08:1:1;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: LOF_236:1:0
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Discard

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1DISCARDCOUNT:1

---

# DeployedDiscountAppliesEvenIfAhsokaDies
#// TS26_08 Ahsoka Tano (leader deployed) — the CR 16.c trigger keeps the deployed side's -1 discount.
#// Ahsoka dies to Army of the Dead, and the surviving peek still plays SEC_080 (cost 6 here) for 5.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08:1:1;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: LOF_236:1:0
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Play

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1RESAVAILABLE:0
