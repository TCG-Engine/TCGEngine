# BaseDamaged_GiveAdvantage
#// ASH_039 Baylan Skoll (Ground, 6/6, Overwhelm, cost 6) — When Played: if an enemy base was damaged this
#// phase, give an Advantage token to a unit. SOR_095 first attacks P2's base (damaging it), then Baylan is
#// played and gives an Advantage to SOR_095. (No friendly upgrade was defeated, so the second rider is skipped.)
## GIVEN
CommonSetup: ryk/ryk/{myResources:6;handCardIds:ASH_039}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
