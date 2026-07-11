# SHD_010 Bossk (deployed) — the re-collect is a "may": collecting the bounty (draw 1) then declining
# Bossk's re-offer leaves P1 with just the 1 card.

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
