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

---

# ActionPickFriendly
#// ASH_011 Cad Bane — Leader Action can target ANY unit with 2+ remaining HP, including a friendly one.
#// With a friendly SEC_080 (3/3) and an enemy SOR_046 (3/7) both legal, P1 chooses the friendly unit.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_011}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED

---

# ActionPickSpace
#// ASH_011 Cad Bane — Leader Action reaches across arenas: an enemy space unit SOR_237 (2/3) is a legal
#// target. P1 pings the space unit for 1.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_011}
SkipPreGame: true
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# ActionNoTargetStillExhausts
#// ASH_011 Cad Bane — with only a 1-HP enemy SOR_128 (3/1) in play there is no legal target, but the
#// leader may still be used and exhausts.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_011}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED

---

# OnAttackPickFriendly
#// ASH_011 Cad Bane leader unit — On Attack: deal 1 to a unit with 2+ HP (optional). Deployed Cad Bane
#// attacks the base, then chooses the friendly SEC_080 (3/3).
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_011:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OnAttackPickSelf
#// ASH_011 Cad Bane leader unit — On Attack the deployed leader may deal 1 to itself.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_011:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# OnAttackPickSpace
#// ASH_011 Cad Bane leader unit — On Attack can reach an enemy space unit SOR_237 (2/3).
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_011:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2BASEDMG:4
P2SPACEARENAUNIT:0:DAMAGE:1

---

# OnAttackPickEnemy
#// ASH_011 Cad Bane leader unit — On Attack: deal 1 to a unit with 2+ HP. Deployed Cad Bane (4 power)
#// attacks the enemy base (4 damage), then pings the enemy ground wall SOR_046 (7 HP, 2+ remaining) for 1.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_011:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:DEPLOYED
