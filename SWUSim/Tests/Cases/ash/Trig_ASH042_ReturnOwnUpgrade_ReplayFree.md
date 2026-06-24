# ASH_042 Jabba the Hutt (Ground, 2/6, cost 4) — When Played: you may return an upgrade to its owner's
# hand; if it's returned to YOUR hand, you may play it for free. P1 returns its own SOR_120 (+2/+2) off
# SOR_095, then replays it free onto Jabba (Jabba 2 → 4 power; SOR_095 reverts to 3).
## GIVEN
CommonSetup: byk/byk/{myResources:4;handCardIds:ASH_042}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:4
