# ASH_210 DDC Defender (Upgrade, non-Vehicle) — Attached unit gains "On Defense: you may deal 1 damage to
# a unit in this unit's arena and exhaust it." P2's SEC_080 attacks the host SOR_046 (3/7 + Defender);
# before damage, P1 deals 1 to and exhausts the enemy bystander SOR_095. SEC_080 then dies to SOR_046's
# counter (after SEC_080 leaves, SOR_095 reindexes to ground-0).
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_210
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED
