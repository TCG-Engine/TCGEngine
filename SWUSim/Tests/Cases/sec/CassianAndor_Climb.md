# FriendlyDamagedBase_CantBeAttacked
#// SEC_012 Cassian Andor (leader front passive) — Friendly units that have damaged an opponent's base this
#// phase can't be attacked (unless they have Sentinel). P1's SOR_095 attacks P2's base (flagging it as
#// having damaged the base). When P2 then attacks, SOR_095 is no longer a legal target, so P2's SOR_128
#// auto-resolves onto P1's base instead (proving the exclusion — with 2 legal targets it would not
#// auto-resolve). SOR_095 ends undamaged.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:SEC_012;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0

## EXPECT
P2BASEDMG:3
P1BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Deployed_SurvivesHpDefeat_WithInitiative
#// SEC_012 Cassian Andor (deployed) — "While you have the initiative, this unit isn't defeated by
#// having no remaining HP." P1 holds the initiative. P2's Imperial Dark Trooper (3/3) attacks the
#// deployed Cassian (6/2); he takes 3 combat damage (no remaining HP) but SURVIVES because P1 has
#// the initiative. (P2's attacker is counter-killed by Cassian's 6 power.)

## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_012:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SEC_012
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Deployed_DiesHpDefeat_WithoutInitiative
#// SEC_012 Cassian Andor (deployed) — the initiative-survival is gated on YOU having the initiative.
#// Here P2 holds it, so the deployed Cassian (6/2) taking 3 combat damage from Imperial Dark Trooper
#// (3/3) has no remaining HP and IS defeated by the state-based sweep — leader returns not deployed.

## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_012:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0

---

# Deployed_CantBeDefeatedByEnemyAbility_WithInitiative
#// SEC_012 Cassian Andor (deployed) — "While you have the initiative, this unit can't be defeated by
#// enemy card abilities." P1 holds the initiative; P2's Rival's Fall ("Defeat a unit") targets the
#// deployed Cassian but is prevented — he stays deployed.

## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_012:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Resources: 6
WithP2Hand: SHD_079

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SEC_012

---

# Deployed_DefeatedByEnemyAbility_WithoutInitiative
#// SEC_012 Cassian Andor (deployed) — the enemy-ability defeat protection is gated on YOU having the
#// initiative. P2 holds it, so P2's Rival's Fall defeats the deployed Cassian normally.

## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_012:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 6
WithP2Hand: SHD_079

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0

---

# Deployed_LosesAllAbilitiesAtZeroHP_ImmediatelyDefeated
#// SEC_012 Cassian Andor (deployed) — his no-remaining-HP survival is one of his own abilities, so if
#// an effect makes him lose all abilities while he is at no remaining HP, he loses that protection and
#// is immediately defeated. Cassian starts with 2 damage (6/2 → 0 remaining HP), surviving because P1
#// has the initiative; P2's Force Lightning strips his abilities and he is defeated on the spot.

## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_012:1:1:1:2;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Resources: 6
WithP2Hand: SOR_138

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0

---

# Deployed_SurvivesZeroHP_WithInitiative
#// SEC_012 Cassian Andor (deployed) — "While you have the initiative, this unit isn't defeated by having no
#// remaining HP." P1 holds the initiative; P2's SEC_080 attacks deployed Cassian (6/2), dealing 3 (2 HP → 0
#// and below). Cassian is NOT defeated — he stays deployed with 3 damage. (His 6-power counter kills SEC_080.)
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_012:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SEC_012
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0

---

# Deployed_DefeatedAtZeroHP_WithoutInitiative
#// SEC_012 Cassian Andor (deployed) — the no-HP protection applies ONLY while you have the initiative. With
#// P2 holding it, the same attack takes Cassian to 0 HP and he IS defeated — leaving the ground arena and
#// returning to the leader zone (exhausted).
## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_012:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED
