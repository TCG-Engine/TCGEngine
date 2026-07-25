# FrontAttackBuffSelfDefeat
#// LAW_001 Saw Gerrera (leader front) — "Action [Exhaust]: Attack with a unit. It gets +2/+0 and gains
#// Overwhelm for this attack. After completing this attack, defeat it." SEC_080 (3/3) is the only ready
#// unit and P2 has no units, so it auto-attacks the base for 3+2 = 5, then is defeated.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:0

---

# FrontAttackEnemyOverwhelmDefeat
#// LAW_001 Saw Gerrera (leader front) — Action: attack with a unit granting +2/+0 and Overwhelm, then
#// defeat it. SEC_080 (3/3, auto-selected as the lone ready unit) attacks the enemy SHD_110 (2/2): it
#// hits for 5, defeats SHD_110, and Overwhelm carries the 3 excess to the base. Afterward SEC_080 is
#// defeated by Saw's ability; the leader is exhausted.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_110:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P2BASEDMG:3
P1LEADER:EXHAUSTED

---

# FrontUseWithNoLegalAttacker
#// LAW_001 Saw Gerrera (leader front) — with only an exhausted unit (which cannot attack) there is no legal
#// attacker, but the ability may still be used to no effect: the leader exhausts, the exhausted unit stays
#// in play, and the enemy is untouched.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP2GroundArena: SHD_110:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# DeployedTriggerSecondAttack
#// LAW_001 Saw Gerrera (deployed) — When Saw's attack ends (and he survives), another friendly unit may
#// attack, gaining +2/+0 and Overwhelm and then being defeated. Saw (4/7) attacks the base for 4, then
#// SEC_080 attacks SHD_110 (2/2): 5 power defeats it and Overwhelm carries 3 to the base (total 7). SEC_080
#// is then defeated, leaving only Saw in the arena.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001:1:1:1;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_110:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:7
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1

---

# DeployedTriggerPassed
#// LAW_001 Saw Gerrera (deployed) — the follow-up attack is optional. Saw attacks the base for 4 and the
#// player declines the trigger: no second unit attacks, so SEC_080 and the enemy SHD_110 are untouched.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001:1:1:1;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_110:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:2

---

# DeployedNoTriggerIfSawDies
#// LAW_001 Saw Gerrera (deployed) — the follow-up requires Saw to survive his attack. Saw (4/7) attacks the
#// SHD_172 Krayt Dragon (10/10): Saw deals 4 but takes 10 and is defeated, so the "attack with another unit"
#// trigger never fires — SEC_080 is untouched and no decision is offered.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001:1:1:1;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_172:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENACOUNT:1
P1NODECISION
