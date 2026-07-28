# AttackEnd_ReadySelf_DoubleAttackSamePhase
#// ASH_033 Grand Admiral Thrawn (Ground, 5/7, Support) — When Attack Ends: if the defending unit was
#// defeated, ready this unit. Because he readies himself on a kill he can attack twice in one phase. He
#// kills SEC_080 (3/3), readies, then kills a second SEC_080 and readies again.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_033:1:0
WithP2GroundArena: [SEC_080:1:0 SEC_080:1:0]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_033
P1GROUNDARENAUNIT:0:READY

---

# AttackEnd_OtherUnitDefeats_ThrawnNotReadied
#// ASH_033 Grand Admiral Thrawn — the ready rider keys off THIS unit's own attack ending. A different
#// friendly unit (SOR_095 Battlefield Marine) killing an enemy does not ready an exhausted Thrawn; he stays
#// exhausted.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: [ASH_033:0:0 SOR_095:1:0]
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_033
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Support_LendsAttackEndReadyBorrowingAttacker
#// ASH_033 Thrawn (Support) — the lent "When Attack Ends: if the defender was defeated, ready this unit" now
#// fires on the BORROWING attacker. Thrawn is played; SOR_046 supports, kills SOR_108 (survives the 1 counter),
#// and the lent rider readies SOR_046.
## GIVEN
CommonSetup: grk/grk/{myResources:7;handCardIds:ASH_033}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_108:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:READY

---

# AttackEnd_NoDefeat_ThrawnNotReadied
#// ASH_033 Grand Admiral Thrawn — the ready rider only fires if the defender was DEFEATED. Thrawn (5 power)
#// attacks SOR_232 AT-ST (6/7); the AT-ST survives (takes 5), so Thrawn does not ready and stays exhausted.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_033:1:0
WithP2GroundArena: SOR_232:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:CARDID:ASH_033
P1GROUNDARENAUNIT:0:EXHAUSTED
