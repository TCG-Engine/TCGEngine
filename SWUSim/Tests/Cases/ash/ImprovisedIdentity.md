# TransplantAbilities
#// ASH_230 Improvised Identity (Upgrade) — grants the host: "Action: search the top 3 for a ground unit and
#// discard it; then you may attack with this unit, gaining the discarded unit's abilities for this attack."
#// SOR_046 (wearing the upgrade) discards SOR_059 (On Attack: may heal 2 from another unit) and attacks P2's
#// base; the transplanted On Attack heals 2 from the damaged SOR_095 (2 → 0 damage).
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1GroundArena: SOR_095:1:2
WithP1Deck: [SOR_059 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_059
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
