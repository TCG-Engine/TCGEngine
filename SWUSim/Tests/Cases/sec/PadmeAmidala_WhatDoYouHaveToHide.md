# HandDiscardTriggers_ExhaustDealOne
#// SEC_016 Padmé — "When you reveal or discard 1 or more cards from your hand: you may exhaust this leader;
#// if you do, deal 1 damage to a unit." P1 uses Mayor's Majordomo (ASH_217), whose cost discards SOR_063 from
#// hand; that discard triggers Padmé FIRST — P1 exhausts her and deals 1 to SOR_046 — then the Majordomo
#// effect exhausts SOR_046.
## GIVEN
CommonSetup: yyk/brk/{myLeader:SEC_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_217:1:0
WithP1Hand: SOR_063
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# HandDiscard_Decline_NoDamage
#// SEC_016 Padmé — the exhaust is optional. Declining the discard-trigger deals no damage (Padmé stays
#// ready); the Mayor's Majordomo effect still exhausts SOR_046.
## GIVEN
CommonSetup: yyk/brk/{myLeader:SEC_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_217:1:0
WithP1Hand: SOR_063
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:NO
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:READY

---

# ForcedDiscard_SimultaneousTwo_TriggersOnce
#// SEC_016 Padmé (deployed) — "When you reveal or discard 1 or more cards from your hand: deal 1 damage
#// to a unit." A forced "discard N cards" effect (P2's Pillage) discards 2 of P1's cards SIMULTANEOUSLY.
#// This must trigger Padmé's deployed ability exactly ONCE (collective) — the enemy unit takes 1 damage,
#// not 2. Regression: the forced-discard path (SWUDiscardCards) previously bypassed the Padmé reaction
#// entirely (never fired); the fix fires it once per discard event.
## GIVEN
CommonSetup: yyw/brk/{myLeader:SEC_016:1:1:1;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP1Hand: SOR_095
WithP1Hand: SOR_063
WithP2Hand: SHD_181
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# ForcedDiscard_ChoiceOfTwo_TriggersOnce
#// Same as above but P1 holds 3 cards, so Pillage forces a CHOICE of 2 (the choice branch of
#// SWUDiscardCards). Padmé's deployed ability still triggers exactly once → 1 damage.
## GIVEN
CommonSetup: yyw/brk/{myLeader:SEC_016:1:1:1;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP1Hand: SOR_095
WithP1Hand: SOR_063
WithP1Hand: SOR_108
WithP2Hand: SHD_181
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# SelfDiscardWholeHand_BlackOne_TriggersOnce
#// SEC_016 Padmé (deployed) — a SELF bulk discard also triggers her once. P1 plays Black One (SOR_147)
#// "discard your hand, draw 3"; the 2 held cards are discarded simultaneously → Padmé's deployed "deal 1"
#// fires exactly ONCE (enemy takes 1). Regression: SOR_147's bulk-discard bypassed DoDiscardCard.
## GIVEN
CommonSetup: yyw/brk/{myLeader:SEC_016:1:1:1;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_147
WithP1Hand: SOR_095
WithP1Hand: SOR_063
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# SelfDiscardKeepTwo_SmokeAndCinders_TriggersOnce
#// SEC_016 Padmé (deployed) — Smoke and Cinders (SOR_174) "each player discards all but 2". P1 keeps 2 of
#// 4 → discards 2 simultaneously → Padmé fires exactly ONCE. Regression: SOR_174 bypassed DoDiscardCard.
## GIVEN
CommonSetup: yyw/brk/{myLeader:SEC_016:1:1:1;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: SOR_174
WithP1Hand: SOR_095
WithP1Hand: SOR_063
WithP1Hand: SOR_108
WithP1Hand: SOR_046
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1HANDCOUNT:2

---

# DiscloseReveal_Front_ExhaustDealOne
#// SEC_016 Padmé (front) — "When you reveal or discard 1 or more cards from your hand: you may exhaust
#// this leader to deal 1 damage to a unit." A Disclose reveals cards from hand, so it satisfies the
#// "reveal" half. SEC_094 Mina Bonteri (2/4) attacks LAW_124 (4/7) and is defeated; her When Defeated
#// discloses CommandCommandHeroism (SEC_096 Command/Heroism + SEC_080 Command/Villainy) to draw 1 — that
#// reveal fires Padmé, who exhausts and deals 1 to the only remaining unit LAW_124 (auto-target).
#// LAW_124 ends with 2 (combat) + 1 (Padmé) = 3 damage; Mina's draw brings the hand from 2 → 3.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:SEC_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_094:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SEC_096
WithP1Hand: SEC_080
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:EXHAUSTED
P1HANDCOUNT:3

---

# OpponentDiscardsYourHand_Front_ExhaustDealOne
#// SEC_016 Padmé (front) — the reaction fires on ANY discard from your hand, including one forced by an
#// enemy effect. P2 plays No Bargain (SHD_244, "each opponent discards a card from their hand; draw a
#// card"); P1's lone card is discarded → Padmé's front reaction offers to exhaust her to deal 1. P1
#// accepts; the only unit in play (SOR_046) auto-resolves as the target → 1 damage, Padmé exhausted.
## GIVEN
CommonSetup: yyw/brk/{myLeader:SEC_016;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP1Hand: SOR_095
WithP2Hand: SHD_244
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# Deployed_ForcedDiscard_Decline_NoDamage
#// SEC_016 Padmé (deployed) — the deployed "deal 1 damage to a unit" is a "may". P2's No Bargain (SHD_244)
#// forces P1 to discard their lone card, triggering Padmé; P1 declines the target (no exhaust cost on the
#// deployed side) → no damage is dealt and the deployed leader remains in play unharmed.
## GIVEN
CommonSetup: yyw/brk/{myLeader:SEC_016:1:1:1;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP1Hand: SOR_095
WithP2Hand: SHD_244
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:DEPLOYED

---

# Negative_DiscardFromOwnDeck_NoTrigger
#// SEC_016 Padmé (front) — the reaction is "discard from your HAND". A discard from the DECK must NOT
#// trigger it. SOR_204 Greedo (3/1) attacks SOR_046 (3/7) and is defeated by the counter; his When
#// Defeated discards the top card of P1's deck (a unit → no deal-2 branch). Padmé does not fire: she stays
#// ready and no extra decision is pending. Discard pile = Greedo + the milled card = 2.
## GIVEN
CommonSetup: yyk/brk/{myLeader:SEC_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_204:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P1LEADER:READY
P1NODECISION
P1DISCARDCOUNT:2

---

# Negative_OpponentHandDiscard_NoTrigger
#// SEC_016 Padmé (deployed) — the reaction keys on cards leaving YOUR hand, so making the OPPONENT discard
#// does nothing for it. P1 plays No Bargain (SHD_244); P2 discards their lone card. Padmé (deployed, P1)
#// deals no damage and no target decision is pending.
## GIVEN
CommonSetup: yyw/brk/{myLeader:SEC_016:1:1:1;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: SHD_244
WithP1Deck: [SOR_095 SOR_095]
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P2HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:DEPLOYED
P1NODECISION

---

# Deployed_TriggersEachDiscardEvent_SamePhase
#// SEC_016 Padmé (deployed) — her "deal 1 damage" fires on EVERY separate discard event, not once per
#// phase. Across the same action phase P2 plays No Bargain (SHD_244) twice; each forces one card out of
#// P1's hand, and each fires Padmé for 1 damage to SOR_046 → 2 damage total.
## GIVEN
CommonSetup: yyw/brk/{myLeader:SEC_016:1:1:1;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 12
WithP2Deck: [SOR_095 SOR_095]
WithP1Hand: SOR_095
WithP1Hand: SOR_063
WithP2Hand: SHD_244
WithP2Hand: SHD_244
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# RevealFromHand_Front_ExhaustDealOne
#// SEC_016 Padmé (front) — the trigger is "When you REVEAL or discard 1+ cards from your hand." Playing
#// SOR_176 ISB Agent reveals an event (SOR_041) from hand and deals its own 1 to the enemy 3/7; that hand
#// REVEAL then triggers Padmé, who may exhaust to deal 1 more. The enemy takes 2 total and Padmé exhausts.

## GIVEN
CommonSetup: yyk/brk/{myLeader:SEC_016}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: [SOR_176 SOR_041]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED
