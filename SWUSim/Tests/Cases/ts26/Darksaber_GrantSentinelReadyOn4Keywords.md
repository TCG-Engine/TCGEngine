# TS26_022 The Darksaber (Upgrade +2/+2, cost 4) — Attach to a non-Vehicle unit; it gains Sentinel. When
# Played: if there are 4+ different keywords among friendly units, ready the attached unit. The friendlies
# have Sentinel (from Darksaber), Grit + Raid (501st Veteran), and Shielded (Crafty Smuggler) = 4 distinct
# → the exhausted host SEC_080 is readied and has Sentinel.
## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_022}
WithP1GroundArena: [SEC_080:0:0 TS26_020:1:0 SOR_207:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
