# Deployed_RevealFromHand_Deal1
#// SEC_016 Padmé Amidala (deployed) — "When you reveal or discard 1 or more cards from your hand: You may
#// deal 1 damage to a unit." P1 plays SEC_062 (which discloses = reveals a card from hand) → the deployed
#// Padmé reacts and deals 1 to the enemy SOR_095. (SEC_062's own draw also resolves.)

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_016:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_062
WithP1Hand: SEC_059
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:CARDID:SEC_062

---

# LeaderReaction_ExhaustDeal1
#// SEC_016 Padmé Amidala (leader front) — "When you reveal or discard 1 or more cards from your hand: You
#// may exhaust this leader. If you do, deal 1 damage to a unit." P1 plays SEC_062 (discloses = reveals a
#// card from hand) → exhaust Padmé → deal 1 to the enemy SOR_095. (SEC_062's draw also resolves.)

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_016;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_062
WithP1Hand: SEC_059
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# Deployed_RevealEventFromHand_SEC184_FiresPadme
#// SEC_016 Padmé (deployed) fires on a NON-disclose hand reveal. SEC_184 ISB Agent "When Played: reveal
#// an event from your hand; deal 1". Revealing the event triggers Padmé's deployed "deal 1 to a unit"
#// react. Enemy SOR_095 (3hp) takes SEC_184's 1 + Padmé's 1 = 2.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_016:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_184
WithP1Hand: SOR_235
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
