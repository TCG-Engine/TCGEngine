# WhenDefeatedDealTwo
#// ASH_153 Green Leader (Space, 3/1) — When Defeated: you may deal 2 damage to a unit. Green Leader
#// attacks SOR_225 (2/1) and both die; its WhenDefeated deals 2 to the enemy SEC_080.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_153:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenDefeatedByEvent_ControllerDealsTwo
#// ASH_153 Green Leader (Space, 3/1) — When Defeated: "you may deal 2 damage to a unit." P2 defeats Green
#// Leader with Takedown (SOR_077, "defeat a unit with 5 or less remaining HP"). The When Defeated belongs
#// to Green Leader's controller (P1), who may deal 2 to any unit — here P2's AT-ST (SOR_232) → 2 damage.
## GIVEN
CommonSetup: bbk/bbk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: ASH_153:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0
WithP2SpaceArena: JTL_237:1:0
WithP2Resources: 10
WithP2Hand: SOR_077
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenDefeatedUnderEnemyControl_NewControllerDealsTwo
#// ASH_153 Green Leader — the When Defeated resolves under whoever controls it at defeat. P2 uses No Glory,
#// Only Results (JTL_043, "take control of a non-leader unit, then defeat it") on Green Leader. Now P2
#// controls it, so the "deal 2 damage to a unit" is P2's choice — P2 hits P1's Wampa (SOR_164) → 2 damage.
## GIVEN
CommonSetup: bbk/bbk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: ASH_153:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0
WithP2SpaceArena: JTL_237:1:0
WithP2Resources: 10
WithP2Hand: JTL_043
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:theirGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:DAMAGE:2
