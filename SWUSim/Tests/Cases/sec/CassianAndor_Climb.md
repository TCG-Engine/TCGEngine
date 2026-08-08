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

---

# FriendlyDamagedOwnBase_NotProtected
#// SEC_012 Cassian Andor (leader front) — damaging YOUR OWN base does not grant the protection. Sabine
#// Wren (SOR_142) attacks Warrior Drone and uses her On-Attack to deal 1 to P1's OWN base (not the
#// opponent's). She never damaged the opponent's base, so she remains attackable: P2's Warrior Drone
#// attacks her (she takes 1 → total 2 damage: 1 counter from her own attack + 1 here), proving no
#// protection was applied.
## GIVEN
CommonSetup: brw/bbk/{myLeader:SEC_012;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: TWI_057:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myBase-0
- P2>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_142
P1GROUNDARENAUNIT:0:DAMAGE:2
P1BASEDMG:1

---

# EnemyDamagedOurBase_NotProtected
#// SEC_012 Cassian Andor (leader front) — the protection is only for FRIENDLY units. An enemy unit that
#// damages P1's base is not protected by Cassian. P2's Battlefield Marine (SOR_095) attacks P1's base
#// (3). On P1's turn, Warrior Drone can freely attack that Battlefield Marine (deals 1 → it takes 1),
#// proving the enemy attacker gained no protection.
## GIVEN
CommonSetup: brw/bbk/{myLeader:SEC_012;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: TWI_057:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:0
## EXPECT
P1BASEDMG:3
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# FriendlyDamagedBase_ViaWhenPlayed_CantBeAttacked
#// SEC_012 (front passive) also protects a unit that damaged the enemy base via a NON-combat source.
#// P1 plays SHD_160 (When Played: deal 1 to each base) — its 1 to P2's base flags it as having damaged
#// the enemy base. When P2 attacks, SHD_160 is excluded, so P2's lone SOR_128 auto-resolves onto P1's
#// base (proving the exclusion). SHD_160 ends undamaged; P1 base takes SHD_160's own 1 + SOR_128's 3 = 4.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:SEC_012;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 5
WithP1Hand: SHD_160
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0

## EXPECT
P2BASEDMG:1
P1BASEDMG:4
P1GROUNDARENAUNIT:0:CARDID:SHD_160
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# FriendlyDamagedBase_ViaIndirect_CantBeAttacked
#// SEC_012 also protects a unit that damaged the enemy base via INDIRECT damage. P1 plays JTL_218
#// (When Played: 3 indirect to a player), aims it at P2, who assigns all 3 to their own base — flagging
#// JTL_218 as having damaged the enemy base (the SWU_DMG_SRC source survives the async assignment).
#// When P2 attacks, JTL_218 is excluded, so P2's lone SOR_128 auto-resolves onto P1's base.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:SEC_012;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 8
WithP1Hand: JTL_218
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:3
- P2>AttackGroundArena:0

## EXPECT
P2BASEDMG:3
P1BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:JTL_218
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# FriendlyDamagedBase_ViaOverwhelm_CantBeAttacked
#// SEC_012 Cassian Andor — "damaged an opponent's base" must count OVERWHELM spillover, not only a direct
#// base attack. P1's Wampa (SOR_164, 4 power, Overwhelm) attacks P2's 2/2 (SHD_110): the 2 excess spills
#// onto P2's base, so the Wampa is protected. P2's remaining unit therefore has no legal unit target and
#// auto-resolves onto P1's base. (The Wampa's 2 damage is the counter-hit from the trade, not from P2.) Same damage-path enumeration family as SEC_077 Retaliation (which read
#// the ATTACK flag instead of the DAMAGE flag) and the JTL_177 indirect bug.
## GIVEN
CommonSetup: brw/bbk/{
  myLeader:SEC_012;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SHD_110:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AttackGroundArena:0
## EXPECT
P2BASEDMG:2
P1BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# DamagedBaseThenLeftAndReturned_NoLongerProtected
#// SEC_012 Cassian Andor — the protection is keyed to the UNIT INSTANCE that damaged the base. A unit that
#// damaged the base, LEFT play, and came back is a new object with a new UniqueID, so the flag no longer
#// applies and it is attackable again. P1's SOR_095 attacks P2's base (flagged), P1 then Waylays it
#// (SOR_222) back to hand and replays it — and P2 can now attack it normally, trading 3/3 into the 3/1.
#// P1's base takes 0: P2 chose the UNIT, which is only possible because the protection no longer applies
#// (in the protected case P2's attack auto-redirects to P1's base, as in FriendlyDamagedBase_CantBeAttacked).
## GIVEN
CommonSetup: brw/bbk/{
  myLeader:SEC_012;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 12
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_222
WithP1Hand: SOR_095
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Pass
- P1>PlayHand:0
- P2>AttackGroundArena:0
## EXPECT
P2BASEDMG:3
P1BASEDMG:0
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
