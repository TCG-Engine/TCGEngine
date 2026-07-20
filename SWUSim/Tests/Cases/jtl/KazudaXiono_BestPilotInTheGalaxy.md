# DeployAsPilot_HostGrantsLoseAbilities
#// JTL_018 Kazuda Xiono (leader) — deployed as a PILOT, the host Vehicle gains his "On Attack: Choose any
#// number of friendly units. They lose all abilities for this round." Kazuda deploys as a Pilot onto the
#// lone friendly Vehicle (SOR_237, now 2+3=5 power), the host attacks the base, and its granted On Attack
#// strips SOR_063 Cloud City Wing Guard of Sentinel for the round.
#//
#// This works through the GENERIC OnAttackFromUpgrade seam (it reuses $onAttackAbilities["JTL_018:0"] for
#// any upgrade whose CardID has that key) — no JTL_018-specific wiring needed. Guard test only.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_018;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# Deployed_OnAttack_LoseAbilities
#// JTL_018 Kazuda Xiono (deployed leader unit) — On Attack: choose any number of friendly units; they
#// lose all abilities for this round. Kazuda attacks P2's base; on attack P1 chooses SOR_063 (innate
#// Sentinel), which loses Sentinel.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_018:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# LeaderAction_LoseAbilities_ExtraAction
#// JTL_018 Kazuda Xiono (undeployed) — Leader Action [Exhaust]: a friendly unit loses all abilities for
#// this round; take an extra action. P1 has one friendly unit (SOR_063, innate Sentinel). The action
#// auto-targets it (it loses Sentinel). Then, because Kazuda grants an EXTRA action (no turn swap), the
#// same player immediately attacks with SOR_063 into P2's base for 2 — proving the turn didn't pass.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_018;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2BASEDMG:2

---

# Deployed_OnAttack_MultipleUnits
#// JTL_018 Kazuda Xiono (deployed leader unit) — On Attack: choose ANY NUMBER of friendly units; they lose
#// all abilities for this round. Kazuda attacks the base and P1 chooses BOTH friendly Sentinel units
#// (SOR_063 and SOR_035); both lose Sentinel.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_018:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: SOR_035:1:0

## WHEN
- P1>AttackGroundArena:2:BASE
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:SOR_035
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
