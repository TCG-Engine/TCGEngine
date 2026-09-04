# Deployed_PlayUnitDealLessOrEqual
#// TWI_018 Quinlan Vos (Leader, deployed) — "When you play a unit: You may deal 1 damage to an enemy unit
#// that costs the same as or less than the played unit." With Quinlan deployed, playing SOR_095 (cost 2)
#// deals 1 to SEC_080 (cost 2 ≤ 2). No leader exhaust needed.
## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myLeader:TWI_018:1:1;handCardIds:SOR_095}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Front_PlayUnitDealSameCost
#// TWI_018 Quinlan Vos (Leader, front) — "When you play a unit: You may exhaust this leader. If you do, deal
#// 1 damage to an enemy unit that costs the same as the played unit." Playing SOR_095 (cost 2) lets P1
#// exhaust Quinlan and deal 1 to SEC_080 (also cost 2).
## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myLeader:TWI_018:1;handCardIds:SOR_095}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# TwinSuns_ExhaustsQuinlanNotLeaderZero
#// "You may exhaust THIS leader" means the Quinlan instance. In a Twin Suns seat with Quinlan in the
#// SECOND slot the cost used to be charged to `$leaderArr[0]` — the wrong leader was taxed AND Quinlan
#// stayed ready, so the ability could fire again on the next unit played. The trigger gate itself was
#// already CardID-keyed (`_SWULeaderReadyUndeployed`), so only the payment was wrong: the offer appeared
#// correctly and then billed someone else.
## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myLeader:SHD_011:1;myLeader2:TWI_018:1;handCardIds:SOR_095}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER0:READY
P1LEADER1:EXHAUSTED

---

# TwinSuns_QuinlanInSlotTwoAlreadyExhausted_NoOffer
#// The other half of "this leader": with Quinlan (slot 2) already exhausted the cost cannot be paid, so
#// nothing is offered — even though leader 0 is sitting there ready. Control for the gate, which reads
#// the Quinlan instance by CardID rather than the first leader's Ready flag.
## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myLeader:SHD_011:1;myLeader2:TWI_018:0;handCardIds:SOR_095}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER0:READY
P1NODECISION
