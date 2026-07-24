# FriendlyHitsBase_LookDiscardOpponentDeck
#// SEC_017 Sabé — "When a friendly unit deals combat damage to a base: you may exhaust this leader; if you
#// do, look at the top 2 cards of the defending player's deck, discard 1, put the other back on top." P1's
#// SOR_046 hits P2's base; P1 exhausts Sabé and discards SEC_080 from P2's top 2 (deck 4 → 3, discard +1).
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2Deck: [SEC_080 SOR_095 SOR_063 SOR_063]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:SEC_080
## EXPECT
P2BASEDMG:3
P2DECKCOUNT:3
P2DISCARDCOUNT:1
P1LEADER:EXHAUSTED

---

# Decline_NoLook
#// SEC_017 Sabé — the exhaust is optional. Declining leaves the opponent's deck untouched and Sabé ready.
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2Deck: [SEC_080 SOR_095 SOR_063 SOR_063]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P2DECKCOUNT:4
P1LEADER:READY

---

# AttackUnit_NoBaseDamage_NoTrigger
#// SEC_017 Sabé — triggers only on combat damage to a BASE. When SOR_046 attacks the enemy unit SEC_080
#// (no base damage), Sabé is not offered and the opponent's deck is untouched.
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2Deck: [SEC_080 SOR_095 SOR_063 SOR_063]
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P2DECKCOUNT:4

---

# OpponentHitsBase_NoTrigger
#// SEC_017 Sabé — the trigger is for a FRIENDLY unit's base damage. When P2's SOR_046 hits P1's base, Sabé
#// (P1's leader) does not fire and P2's own deck is untouched.
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SOR_046:1:0
WithP2Deck: [SEC_080 SOR_095 SOR_063 SOR_063]
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P2DECKCOUNT:4

---

# FrontOverwhelmBaseDamage_LookDiscardDeck
#// SEC_017 Sabé (leader front) — the trigger is any friendly unit dealing combat damage to a base, which
#// includes Overwhelm excess. Steadfast Battalion (SOR_116, 5/5 Overwhelm) attacks Death Star Stormtrooper
#// (SOR_128, 3/1): it dies and 4 excess spills onto P2's base. Sabé is offered; P1 exhausts her and
#// discards SEC_080 from P2's top 2 (deck 4 -> 3).
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_116:1:0
WithP2GroundArena: SOR_128:1:0
WithP2Deck: [SEC_080 SOR_095 SOR_063 SOR_063]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SEC_080
## EXPECT
P2BASEDMG:4
P2DECKCOUNT:3
P1LEADER:EXHAUSTED

---

# FrontEmptyDeck_TriggersButNothingToLook
#// SEC_017 Sabé (leader front) — with the defending player's deck empty, the exhaust is still offered but
#// there is nothing to look at. P1 accepts; Sabé exhausts and no discard happens.
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2Deck: []
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P2DECKCOUNT:0
P2DISCARDCOUNT:0
P1LEADER:EXHAUSTED

---

# FrontOneCardDeck_AutoDiscardsIt
#// SEC_017 Sabé (leader front) — with exactly 1 card in the defending player's deck, looking at the "top
#// 2" finds only that card, which is discarded automatically (no choice). P1 exhausts Sabé; the lone card
#// (SOR_045) goes to P2's discard.
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2Deck: [SOR_045]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P2DECKCOUNT:0
P2DISCARDCOUNT:1
P1LEADER:EXHAUSTED

---

# Deployed_SheHitsBase_LookHandDiscardOpponentDraws
#// SEC_017 Sabé (deployed) — "When she deals combat damage to a base: look at the defending player's
#// hand. You may discard a card from it; if you do, that player draws a card." Deployed Sabé (3/6, Raid 1
#// -> 4 power attacking) hits P2's base for 4. P1 discards Resupply (SOR_126) from P2's hand; P2 then
#// draws a replacement (SOR_045) from their deck. Hand stays at 3 (3 - 1 discard + 1 draw), deck empties.
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP2Hand: SOR_126
WithP2Hand: SOR_095
WithP2Hand: SOR_141
WithP2Deck: [SOR_045]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirHand-0
## EXPECT
P2BASEDMG:4
P2DISCARDCOUNT:1
P2HANDCOUNT:3
P2DECKCOUNT:0
P1LEADER:DEPLOYED

---

# Deployed_SheHitsUnit_NoBaseDamage_NoTrigger
#// SEC_017 Sabé (deployed) — the deployed ability needs combat damage to a BASE. When Sabé attacks an
#// enemy unit (Consular Security Force, SOR_046 3/7) she deals no base damage, so no look/discard happens
#// and P2's hand is untouched.
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SOR_126
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P2HANDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:DEPLOYED

---

# Deployed_FriendlyOtherHitsBase_NoTrigger
#// SEC_017 Sabé (deployed) — the deployed ability triggers only on SABÉ's own base damage, not another
#// friendly unit's. Consular Security Force (SOR_046) attacks P2's base while Sabé is deployed; the
#// deployed side does not fire (and the front-side passive is inactive while deployed), so P2's hand is
#// untouched.
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2Hand: SOR_126
WithP2Hand: SOR_095
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1NODECISION
P2HANDCOUNT:2
P2BASEDMG:3
P1LEADER:DEPLOYED

---

# Deployed_EnemyHitsBase_NoTrigger
#// SEC_017 Sabé (deployed) — an enemy unit dealing base damage does not trigger Sabé. P2's Battlefield
#// Marine (SOR_095) attacks P1's base; deployed Sabé does nothing.
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P1NODECISION
P1LEADER:DEPLOYED

---

# Deployed_EmptyOpponentHand_NoDrawNoBreak
#// SEC_017 Sabé (deployed) — when the defending player's hand is empty, the look/discard does nothing and
#// they do not draw. Deployed Sabé hits P2's base (4); P2 has an empty hand and keeps their deck intact.
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP2Deck: [SOR_045]
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:4
P2DECKCOUNT:1
P2HANDCOUNT:0
P1LEADER:DEPLOYED
