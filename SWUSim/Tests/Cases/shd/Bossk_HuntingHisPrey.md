# Deployed_DeclineRecollect
#// SHD_010 Bossk (deployed) — the re-collect is a "may": collecting the bounty (draw 1) then declining
#// Bossk's re-offer leaves P1 with just the 1 card.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0
WithP1Deck: SOR_095 SOR_095 SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:1

---

# Deployed_RecollectBounty
#// SHD_010 Bossk (deployed) — "When you collect a bounty: You may collect that bounty again. Use this
#// ability only once each round." P1's deployed Bossk-controller defeats the enemy SHD_095 (Clone Deserter,
#// draw-1 Bounty) with SOR_046, collects the bounty (draw 1), then Bossk lets P1 collect it AGAIN (draw 1
#// more) — 2 cards drawn total.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0
WithP1Deck: SOR_095 SOR_095 SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:2

---

# Front_DealAndBuffBountyUnit
#// SHD_010 Bossk (front, undeployed) — "Action [Exhaust]: Deal 1 damage to a unit with a Bounty. You may
#// give it +1/+0 for this phase." P1 uses the action on its own SHD_167 (4/4, printed Bounty — the sole
#// Bounty unit, so the target auto-resolves): it takes 1 damage and is buffed to 5 power. Bossk exhausts.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010}
P1OnlyActions: true
WithP1GroundArena: SHD_167:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:POWER:5
