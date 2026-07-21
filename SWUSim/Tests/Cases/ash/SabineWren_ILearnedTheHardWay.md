# ShieldAttach_ExhaustGround
#// ASH_208 Sabine Wren (Ground, 4/5, Shielded, cost 5) — "When 1 or more upgrades attach to this unit
#// (including from Shielded): you may exhaust a ground unit." Playing Sabine gives her a Shield (Shielded),
#// which counts as an upgrade attaching, so P1 may exhaust a ground unit — here the enemy SOR_046.
## GIVEN
CommonSetup: yyw/yyk/{myResources:5;handCardIds:ASH_208}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# ShieldAttach_Decline_NoExhaust
#// ASH_208 Sabine Wren — the exhaust is optional. When her Shield attaches on play, P1 declines, so the
#// enemy SOR_046 stays ready (and Sabine still gets her Shield).
## GIVEN
CommonSetup: yyw/yyk/{myResources:5;handCardIds:ASH_208}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
