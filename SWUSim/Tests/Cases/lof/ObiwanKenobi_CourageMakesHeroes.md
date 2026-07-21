# DeployedOnAttack
#// LOF_008 Obi-Wan Kenobi (deployed) — On Attack: may give an Experience token to another unit without one.
#// He attacks the base and gives SOR_046 an Experience token → 4/8.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:LOF_008;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 5
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4

---

# ExpUnitWithoutToken
#// LOF_008 Obi-Wan Kenobi — Action [Exhaust, use the Force]: Give an Experience token to a unit without an
#// Experience token on it. Plo Koon (no token) becomes 7/9 and P1 loses the Force.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:LOF_008;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:9
P1NOFORCE

---

# LeaderAbility_GivesExpToEnemyUnit
#// LOF_008 Obi-Wan Kenobi (front) — the Action targets ANY unit without an Experience token, friendly or
#// enemy. With a friendly Marine (SOR_095) and an enemy Consular Security Force (SOR_046, 3/7) both eligible,
#// P1 gives the token to the ENEMY unit → SOR_046 becomes 4/8, and P1 loses the Force. Ref: "allows the
#// player to give an Experience token to an enemy unit".

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:LOF_008;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:8
P1NOFORCE
P1LEADER:EXHAUSTED

---

# DeployedOnAttack_PassDeclinesToken
#// LOF_008 Obi-Wan Kenobi (deployed) — the On Attack "may give an Experience token" is optional even with a
#// legal target. Deployed Obi-Wan attacks the base with an enemy Consular Security Force (SOR_046) available
#// as a target; P1 declines (Pass) → SOR_046 stays 3/7 with no token. Ref: "allows the player to pass the
#// ability".

## GIVEN
CommonSetup: bgw/bbk/{myLeader:LOF_008:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# DeployedOnAttack_NoValidTarget_DoesNothing
#// LOF_008 Obi-Wan Kenobi (deployed) — On Attack targets "ANOTHER unit" (excludes himself) without an
#// Experience token. With Obi-Wan the ONLY unit in play (no other friendly units, no enemy units), there is
#// no valid target: the ability silently does nothing (no prompt) and the attack simply deals 3 to the base.
#// Ref: "does nothing if there are no valid targets".

## GIVEN
CommonSetup: bgw/bbk/{myLeader:LOF_008:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:LOF_008
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
