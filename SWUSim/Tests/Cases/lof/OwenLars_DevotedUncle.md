# WhenDefeated_SearchForce
#// LOF_057 Owen Lars — When Defeated: search the top 5 for a Force unit, reveal and draw it. He attacks a
#// 4/7, dies to the counter, and draws the lone Force unit (LOF_050) from the top 5.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_057:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: LOF_050
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:LOF_050

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0

---

# WhenDefeated_Search_UnderNoGlory
#// LOF_057 Owen Lars — No Glory, Only Results (JTL_043) takes control of the enemy Owen FIRST, then defeats
#// it. The queued-decision When Defeated (search top 5 for a Force unit) belongs to P1 (the controller at
#// defeat), so P1 searches P1's deck and draws the Force unit — parallel to LOF_059's immediate WD draw
#// under NGOR. Queued-decision When-Defeated abilities DO resolve during NGOR (no engine bug).
#// ⚠ AUTO-TARGET: JTL_043's target choice auto-resolves against the lone enemy unit, so there is NO
#// "take control" prompt to answer — the ONLY interactive decision is the top-deck search. Do NOT add a
#// `theirGroundArena-0` answer here: it would be a stray that consumes the search decision (the false
#// "searches neither deck" symptom). See the LOF_185 Baylan auto-resolve gotcha.

## GIVEN
CommonSetup: bbw/rrk/{myResources:13;handCardIds:JTL_043}
P1OnlyActions: true
WithP2GroundArena: LOF_057:1:0
WithP1Deck: LOF_050
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LOF_050

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENACOUNT:0
