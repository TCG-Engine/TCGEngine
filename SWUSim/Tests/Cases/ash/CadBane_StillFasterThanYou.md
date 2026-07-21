# Attack_Pass
## GIVEN
SkipPreGame: true
CommonSetup: grk/rrk/{
  myLeader: ASH_011:1:1:3
}
WithP1GroundArena: SEC_080

## WHEN
- P1>AttackGroundArena:1
- P1>AnswerDecision:PASS
- P1>AttackGroundArena:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# PingLeaders
## GIVEN
CommonSetup: grk/brk/{
  theirLeader:SOR_010:1:1:1;
  myLeader:ASH_011:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# DealOneTwoPlusHP
#// ASH_011 Cad Bane — Leader Action [Exhaust]: deal 1 damage to a unit with 2 or more remaining HP. SOR_046
#// (3/7) has 7 remaining HP (the only legal target, auto-resolved) and takes 1 damage; Cad Bane exhausts.
## GIVEN
CommonSetup: grk/brk/{
  myLeader:ASH_011
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# CantTargetOneHpUnit
#// ASH_011 Cad Bane — the action can only hit a unit with 2 or more remaining HP. With only a 1-HP enemy
#// (SOR_128, 3/1) in play, there is no legal target: the ability does nothing.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_011}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
