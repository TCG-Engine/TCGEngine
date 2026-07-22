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
