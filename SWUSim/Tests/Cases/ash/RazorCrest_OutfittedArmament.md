# OnAttack_DeclineNoBuff
#// ASH_172 Razor Crest (Space, 3/5, cost 4) — declining the On Attack discard leaves the unit at base
#// power. Razor Crest attacks P2's base and P1 declines (NO), so the base takes only 3 and the hand card
#// is kept.
## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SOR_095}
WithP1SpaceArena: ASH_172:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:NO
## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1

---

# OnAttack_DiscardForBuff
#// ASH_172 Razor Crest (Space, 3/5, Saboteur, cost 4) — On Attack: you may discard a card from your hand;
#// if you do, this unit gets +2/+0 for this attack. Razor Crest attacks P2's base; P1 accepts (YES) and
#// discards its one hand card, so the base takes 3+2 = 5 and the hand empties.
## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SOR_095}
WithP1SpaceArena: ASH_172:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:5
P1HANDCOUNT:0

---

# OnAttack_DeclineDiscard_NoBuff
#// ASH_172 Razor Crest — the +2/+0 requires discarding a card. Declining leaves it at base 3 power when it
#// attacks the enemy base.
## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SOR_063}
WithP1SpaceArena: ASH_172:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P2BASEDMG:3

---

# OnAttack_EmptyHand_NoPrompt
#// ASH_172 Razor Crest — the On Attack discard is a "you may discard a card from your hand". With an empty
#// hand there is nothing to discard, so no prompt appears and the unit attacks at base 3 power (the enemy
#// base takes only 3).
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_172:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:3

---

# OnAttack_Saboteur_StripsShield_BeforeBuff
#// ASH_172 Razor Crest (Space, 3/5, Saboteur) attacks P2's System Patrol Craft (SOR_066, Space, 3/4) which
#// carries a Shield token. Saboteur defeats the Shield, then the On Attack discard buff (+2/+0) is accepted:
#// Razor Crest hits for 5 into the now-unshielded 4-HP craft, defeating it.
## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SOR_077}
WithP1SpaceArena: ASH_172:1:0
WithP2SpaceArena: SOR_066:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:1
