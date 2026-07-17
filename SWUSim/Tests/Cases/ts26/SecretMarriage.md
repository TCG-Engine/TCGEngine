# FriendlyOnlyNoDraw
#// TS26_46 Secret Marriage — shielding only a friendly unit (no enemy) does NOT draw a card.
## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:TS26_46}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_046 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1HANDCOUNT:0

---

# ShieldEnemyDrawsCard
#// TS26_46 Secret Marriage (Event, cost 2, Vigilance) — Give a Shield to each of up to 2 non-Vehicle
#// units; if you shield an enemy unit this way, draw a card. Shielding one friendly + one enemy shields
#// both and draws 1 (hand 0 after playing the event → 1).
## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:TS26_46}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_046 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1HANDCOUNT:1
