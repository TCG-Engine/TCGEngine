# AttackEnd_PlayTopFree
#// ASH_229 Camtono (Upgrade, cost 1) — Attached unit gains: "When Attack Ends: look at the top card of your
#// deck; if it costs 2 or less, you may play it for free." SOR_046 wears the Camtono and attacks P2's base;
#// the top card SOR_095 (cost 2) is played for free, so P1's ground arena goes to 2 units.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_229
WithP1Deck: [SOR_095 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:2

---

# AttackEnd_Decline
#// ASH_229 Camtono — the free play is optional. SOR_046 attacks base; the top card SOR_095 (cost 2) is
#// eligible but P1 declines, so nothing is played.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_229
WithP1Deck: [SOR_095 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENACOUNT:1

---

# AttackEnd_TopCostsThree_NoOffer
#// ASH_229 Camtono — only a top card costing 2 or less is playable. With SOR_063 (cost 3) on top, no free
#// play is offered.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_229
WithP1Deck: [SOR_063 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1

---

# AttackEnd_PlayUpgradeFree
#// ASH_229 Camtono — the free play works for ANY card type costing 2 or less, including upgrades. SOR_046
#// (wearing Camtono) attacks; the top card is another Camtono ASH_229 (cost 2). P1 plays it for free and
#// attaches it to the second ground unit SOR_095, which then wears 1 upgrade.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_229
WithP1Deck: [ASH_229 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# AttackEnd_PlayEventFree
#// ASH_229 Camtono — an event costing 2 or less can also be played for free. SOR_046 (3 damage) attacks;
#// the top card SOR_074 Repair (cost 1) is played for free and heals 3 from SOR_046 → 0 damage.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:3
WithP1GroundArenaUpgrade: 0:ASH_229
WithP1Deck: [SOR_074 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# AttackEnd_EmptyDeck_NoOp
#// ASH_229 Camtono — with an empty deck there is no top card to look at, so no free play is offered and no
#// decision is presented. SOR_046 still deals its 3 attack damage to the enemy base.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_229
WithP1Deck: []
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P2BASEDMG:3
