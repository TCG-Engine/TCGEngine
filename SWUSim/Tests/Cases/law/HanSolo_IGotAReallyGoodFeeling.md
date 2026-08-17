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

---

# Front_EnemyTokensCannotPayTheCost
#// COVERAGE: control=Front_EnemyTokensCannotPayTheCost (enemy tokens are not "a friendly token") +
#//           Front_StolenUnitsTokenIsAFriendlyToken (a token on a P2-OWNED unit that P1 CONTROLS is) +
#//           Epic_StolenResourceCountsTowardFive / Epic_FourOwnedResourcesCannotDeploy ("you control 5 or
#//           more resources" counts the resource zone P1 controls, whoever owns the card) — "friendly"
#//           and "you control" both follow CONTROL, not ownership · offer=Front_EnemyTokensCannotPayTheCost
#//           asserts the cost pool is empty by outcome (the Action is unavailable) rather than by mzID,
#//           because the token cost is answered with Exp~/Shield~/Force/Credit keys, not zone mzIDs ·
#//           decline=Deployed_OnAttack_ChooseNoTokens_NoDamage · reqboundary=N/A.
#//
#// LAW_017 Han Solo (front) — every token on the board belongs to P2: an Experience and a Shield on P2's
#// SOR_046, P2's Credit and P2's Force. P1 controls only a bare SOR_128. "Defeat a FRIENDLY token" can
#// therefore not be paid, so the Action is unavailable: Han stays ready, no damage is dealt, and none of
#// P2's tokens are consumed.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2Credits: 1
WithP2Force: true

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2CREDITCOUNT:1

## WHEN
- P1>UseLeaderAbility

---

# Front_StolenUnitsTokenIsAFriendlyToken
#// LAW_017 Han Solo (front) — owner ≠ controller. P1's only unit is a SOR_095 that P2 OWNS but P1
#// CONTROLS, and the Experience token on it is therefore a token P1 controls: a legal "friendly token"
#// for the cost. Han defeats it (the stolen host survives with 0 upgrades, still in P1's arena) and deals
#// 1 damage to P2's SOR_046. Ownership of the host never enters into it.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_095:2
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

# Epic_StolenResourceCountsTowardFive
#// LAW_017 Han Solo — "Epic Action: If YOU CONTROL 5 or more resources, deploy this leader." P1's
#// resource row holds four P1-owned cards plus a SOR_095 that P2 OWNS (the end state after an enemy card
#// is resourced). That is five resources P1 CONTROLS, so the Epic deploy is legal: Han enters P1's ground
#// arena and the Epic slot is spent. Counting only P1-OWNED resources would read four and refuse.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028;
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true
WithP1ResourceControlled: SOR_095:2

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_017
P1RESCOUNT:5

---

# Epic_FourOwnedResourcesCannotDeploy
#// LAW_017 Han Solo — the negative control for the section above: the same board WITHOUT the stolen fifth
#// resource. Four resources is below the threshold, so the Epic is not usable at all — Han does not
#// deploy, nothing enters the arena, and (as with any unpayable Epic) the once-per-game slot survives.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028;
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
P1GROUNDARENACOUNT:0
P1RESCOUNT:4

---

# Deployed_OnAttack_DamageMayHitAFriendlyUnit
#// COVERAGE addendum (the ledger above is frozen; these two sections extend it): offer=the DAMAGE
#//           target pool is unqualified and spans both sides (this section) and the COST pool is
#//           friendly-only on the deployed On Attack too, not just the leader-front Action
#//           (Deployed_OnAttack_EnemyTokensAreNotInTheCostPool) · boundary="any number" now has all
#//           three arms: zero (Deployed_OnAttack_ChooseNoTokens_NoDamage), one (the enemy-token
#//           section), several (Deployed_OnAttack_DefeatManyTokens_DealThatMany / _DefeatMixedTokens).
#//
#// LAW_017 Han Solo (deployed) — "Deal that much damage to a unit." The target word is UNQUALIFIED: it
#// names no controller, so the pool spans BOTH sides and a friendly unit is a legal (if rarely wanted)
#// choice. With an enemy SOR_128 also in play the choice is real, and P1 deliberately points the damage
#// at its own SOR_046 (3/7): the friendly unit takes the 1 damage and the enemy takes none.
#// This is the axis an auto-resolving single target would hide — restricting the pool to enemy units
#// would make the friendly answer illegal and throw instead.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:Credit0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1CREDITCOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Deployed_OnAttack_EnemyTokensAreNotInTheCostPool
#// LAW_017 Han Solo (deployed) — the On-Attack cost is "defeat any number of FRIENDLY tokens", so the
#// opponent's tokens are outside the pool even though they are tokens in play. P2 holds a Credit, the
#// Force, and an Experience token on its SOR_046; P1's only token is a single Credit. Han attacks P2's
#// base, defeats that one Credit, and deals exactly 1 damage.
#// The count is the discriminator: if the enemy tokens were selectable the repeating "defeat a token"
#// prompt would still be open after the Credit and would swallow the damage-target answer, and the
#// damage would not be 1. P2 ends with its Credit, its Force and its Experience all intact.
#// (Front_EnemyTokensCannotPayTheCost proves the same for the leader-front Action; this is the deployed
#// On Attack, which reaches the pool through a different collection.)

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Credits: 1
WithP2Credits: 1
WithP2Force: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Credit0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P1CREDITCOUNT:0
P2CREDITCOUNT:1
P2HASFORCE
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
