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
