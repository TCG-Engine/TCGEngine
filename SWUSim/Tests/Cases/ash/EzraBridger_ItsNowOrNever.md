# BaseHit_AdvantageDifferent
#// ASH_013 Ezra Bridger — "When a friendly unit's attack ends: if it dealt 3+ combat damage to a base, you
#// may exhaust this leader; if you do, give an Advantage token to a different unit." SOR_046 (3 power) hits
#// P2's base for 3; P1 exhausts Ezra and gives an Advantage to SOR_095 (the only non-attacker, auto-resolved).
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_013
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P1LEADER:EXHAUSTED

---

# BaseHit_Decline
#// ASH_013 Ezra — the exhaust is optional. SOR_046 hits P2's base for 3, but P1 declines; no Advantage is
#// given and Ezra stays ready.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
P1LEADER:READY

---

# LessThanThreeToBase_NoTrigger
#// ASH_013 Ezra — the rider needs 3+ combat damage to a base. SOR_063 (2 power) hits P2's base for only 2,
#// so Ezra does not trigger.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1NODECISION
P1LEADER:READY
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0

---

# CombatDamageToUnit_NoTrigger
#// ASH_013 Ezra — only combat damage to a BASE counts. SOR_046 attacks the enemy unit SOR_063 (dealing 3 to
#// a unit, 0 to a base), so Ezra does not trigger.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_063:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P1LEADER:READY
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
