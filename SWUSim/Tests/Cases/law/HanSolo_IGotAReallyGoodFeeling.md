# FrontDefeatTokenDeal1
#// LAW_017 Han Solo (leader front) — "Action [Exhaust, defeat a friendly token]: Deal 1 damage to a
#// unit." P1's only friendly token (JTL_T01 TIE Fighter) is defeated as the cost, and 1 damage is dealt
#// to P2's SOR_128 (3/1), defeating it.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_T01:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Unit~myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# Deployed_OnAttack_DefeatManyTokens_DealThatMany
#// LAW_017 Han Solo (deployed) — On Attack: "Defeat any number of friendly tokens. Deal that much damage
#// to a unit." Deployed Han (4/5) attacks P2's base for 4. On attack he defeats 3 friendly tokens (2 token
#// units TWI_T01/TWI_T02 + 1 Credit), then deals 3 damage to SOR_046 (3/7, survives). Only Han remains in
#// P1's ground arena, the Credit is gone, and the base has 4 combat damage.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [TWI_T01:1:0 TWI_T02:1:0]
WithP1Credits: 1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:2:BASE
- P1>AnswerDecision:Unit~myGroundArena-0
- P1>AnswerDecision:Unit~myGroundArena-0
- P1>AnswerDecision:Credit0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_017
P1CREDITCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Deployed_OnAttack_ChooseNoTokens_NoDamage
#// LAW_017 Han Solo (deployed) — the On-Attack "defeat any number of friendly tokens" is optional: P1 may
#// choose 0. Deployed Han attacks P2's base (4 damage), then declines to defeat any token, so no unit
#// damage is dealt and both friendly tokens survive.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [TWI_T01:1:0 TWI_T02:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:2:BASE
- P1>AnswerDecision:Done

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:3
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Deployed_OnAttack_NoTokens_AutoSkipped
#// LAW_017 Han Solo (deployed) — with no friendly tokens in play, the On-Attack token-defeat prompt is
#// skipped entirely and no unit damage is dealt. Deployed Han attacks P2's base for 4; SOR_046 is untouched.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_NoTokens_AbilityUnavailable
#// LAW_017 Han Solo (leader front) — the Action costs "defeat a friendly TOKEN"; with only a non-token
#// friendly unit (SOR_128) in play there is nothing to pay the cost with, so the ability is unavailable.
#// Using the leader is a no-op: Han stays ready and the enemy SOR_046 takes no damage.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_DefeatExperienceToken
#// LAW_017 Han Solo (front) — an Experience token is a "friendly token" and can be defeated to pay the
#// cost. SOR_095 (3/3) carries an Experience (→ 4/4); the front action defeats the Experience (the unit
#// survives with 0 upgrades) and deals 1 to the enemy SOR_046.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Exp~myGroundArena-0~0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Front_DefeatShieldToken
#// LAW_017 Han Solo (front) — a Shield token is a "friendly token" and can be defeated to pay the cost.
#// SOR_095 carries a Shield; the front action defeats the Shield (unit survives, 0 upgrades) and deals 1
#// to the enemy SOR_046.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Shield~myGroundArena-0~0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Front_DefeatForceToken
#// LAW_017 Han Solo (front) — the Force token is a "friendly token" and can be defeated to pay the cost.
#// P1 holds the Force; the front action defeats it (P1 no longer has the Force) and deals 1 to the only
#// unit in play, the enemy SOR_046 (auto-targeted).

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Force

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Deployed_DefeatMixedTokens_Deal4
#// LAW_017 Han Solo (deployed) — On Attack he may defeat ANY combination of friendly token types. Here P1
#// defeats the Force token, a Credit, an Experience and a Shield (4 tokens) and deals 4 to the enemy
#// SOR_046 (3/7, survives). Deployed Han also hits the base for 4 combat.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Credits: 1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:Force
- P1>AnswerDecision:Credit0
- P1>AnswerDecision:Exp~myGroundArena-0~0
- P1>AnswerDecision:Shield~myGroundArena-0~0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P1NOFORCE
P1CREDITCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:4
