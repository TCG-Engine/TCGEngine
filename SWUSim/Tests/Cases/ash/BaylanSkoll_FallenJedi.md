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

---

# NeitherCondition_NoTrigger
#// ASH_039 Baylan — both riders are conditional. Played with no enemy base damaged and no friendly upgrade
#// defeated this phase, neither rider fires: no Advantage, no exhaust prompt.
## GIVEN
CommonSetup: ryk/ryk/{myResources:6;handCardIds:ASH_039}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:ASH_039

---

# WhenAttackEnds_BaseDamaged_Advantage
#// ASH_039 Baylan — the riders also fire on "When Attack Ends." A seated Baylan (6/6 Overwhelm) attacks P2's
#// base for 6; the enemy base was damaged this phase, so he gives an Advantage token (to himself here).
## GIVEN
CommonSetup: ryk/ryk
WithP1GroundArena: ASH_039:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
