# Carson202_SupportLendsDealFirst
#// ASH_202 Carson Teva (Ground, 1/4, Support) — "While attacking, this unit deals combat damage before the
#// defender." Support: when played, another friendly unit may attack and gains Carson's other ability for
#// that attack. Carson is played and lends deal-first to SOR_164 Wampa (4/5), which attacks SOR_095
#// Battlefield Marine (3/3): Wampa deals its 4 FIRST, defeating the Marine, so the Marine deals no counter
#// and Wampa takes 0 damage.
## GIVEN
CommonSetup: yrw/grw/{myResources:4;handCardIds:ASH_202}
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Carson202_DealFirstDefeats_NoCounter
#// ASH_202 Carson Teva — deal-first prevents ALL counter damage whenever the defender is defeated by the
#// first strike. Carson (1 power) attacks LOF_254 Porg (1/1): he deals his 1 first, defeating the Porg,
#// which therefore deals no counter — Carson takes 0.
## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: ASH_202:1:0
WithP2GroundArena: LOF_254:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_202
P1GROUNDARENAUNIT:0:DAMAGE:0
