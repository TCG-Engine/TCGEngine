# UseForceReaction
#// LOF_260 The Father — When you use the Force: You may deal 1 damage to this unit. If you do, the Force is
#// with you. P1 uses Mother Talzin's Force action; The Father's reaction fires first — P1 deals 1 to The
#// Father and regains the Force. Then Talzin's -1/-1 resolves on SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:LOF_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_260:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1HASFORCE
P2GROUNDARENAUNIT:0:POWER:2

---

# Decline_NoTrigger
#// LOF_260 The Father — the reaction is optional ("You may deal 1 damage to this unit"). P1 uses Mother
#// Talzin's Force action but declines The Father's reaction: The Father takes no damage and P1 does NOT
#// regain the Force. Talzin's -1/-1 still resolves on SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:LOF_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_260:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NOFORCE
P2GROUNDARENAUNIT:0:POWER:2

---

# SelfDefeatByOwnDamage
#// LOF_260 The Father — the 1 self-damage can defeat it. At 9 damage (10 HP) the reaction's 1 damage defeats
#// The Father, but P1 still regains the Force ("If you do" is satisfied by dealing the damage).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:LOF_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_260:1:9
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HASFORCE
P2GROUNDARENAUNIT:0:POWER:2

---

# OpponentUsesForce_NoTrigger
#// LOF_260 The Father — "When YOU use the Force" is controller-scoped: when the OPPONENT uses the Force (P2's
#// own Mother Talzin action) The Father does not trigger, so it takes no self-damage. Talzin's -1/-1 still
#// lands on The Father (5 power → 4).

## GIVEN
CommonSetup: bbk/bbk/{
  theirLeader:LOF_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP2Force: true
WithP1GroundArena: LOF_260:1:0

## WHEN
- P2>UseLeaderAbility
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:4
P2NOFORCE
