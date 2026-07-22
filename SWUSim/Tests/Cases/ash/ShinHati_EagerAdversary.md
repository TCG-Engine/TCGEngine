# BaseHit_ExhaustCheaper
#// ASH_016 Shin Hati — "When a friendly unit's attack ends: you may exhaust this leader; if you do, exhaust a
#// unit that costs less than the combat damage dealt to a base this attack." SOR_038 (5 power) hits P2's base
#// for 5; P1 exhausts Shin and exhausts SOR_046 (cost 4 < 5, the only legal target, auto-resolved).
## GIVEN
CommonSetup: gyk/brk/{
  myLeader:ASH_016
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# BaseHit_Decline
#// ASH_016 Shin Hati — the exhaust is optional. After SOR_038 hits P2's base for 5, P1 declines; the enemy
#// SOR_046 is not exhausted and Shin stays ready.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO
## EXPECT
P2GROUNDARENAUNIT:0:READY
P1LEADER:READY

---

# AttackUnit_NoBaseDamage_NoTrigger
#// ASH_016 Shin Hati — no base damage means no trigger. SOR_038 attacks the enemy unit SOR_046 (0 combat
#// damage to a base), so Shin is never offered.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P1LEADER:READY
