# Deployed_Deal2OnFriendlyAttackerDefeated
#// SEC_013 Luthen Rael (deployed) — "When a friendly unit is defeated while attacking: You may deal 2
#// damage to a unit or base." P1's SOR_128 (idx 1) attacks SOR_063 (Sentinel) and dies; the deployed
#// Luthen reacts → deal 2 to the enemy base. (No leader-exhaust cost on the deployed side.)

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:SEC_013:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:1

---

# LeaderReaction_ExhaustDeal1
#// SEC_013 Luthen Rael (leader front) — "When a friendly unit is defeated while attacking: You may exhaust
#// this leader. If you do, deal 1 damage to a unit or base." P1's SOR_128 (3/1) attacks SOR_063 (2/4
#// Sentinel) and dies to the 2 counter-damage. P1 exhausts Luthen and deals 1 to the enemy base.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:SEC_013;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED

---

# Front_DefeatedWhileDefending_NoTrigger
#// SEC_013 Luthen Rael (leader front) — the reaction is only for a friendly unit defeated while
#// ATTACKING. P2's Consular Security Force (SOR_046, 3/7) attacks and defeats P1's Death Star
#// Stormtrooper (SOR_128, 3/1) while it is defending; Luthen is not offered.
## GIVEN
CommonSetup: brw/bbk/{myLeader:SEC_013;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1NODECISION
P1LEADER:READY

---

# Front_DefeatedByEvent_NotAttacking_NoTrigger
#// SEC_013 Luthen Rael (leader front) — a friendly unit defeated by an event while NOT attacking does not
#// trigger Luthen. P2's Open Fire (SOR_172, deal 4) defeats P1's Death Star Stormtrooper (SOR_128, 3/1).
## GIVEN
CommonSetup: brw/bbk/{myLeader:SEC_013;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 6
WithP2Hand: SOR_172
WithP1GroundArena: SOR_128:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P1NODECISION
P1LEADER:READY

---

# Front_AttackerAndDefenderSurvive_NoTrigger
#// SEC_013 Luthen Rael (leader front) — no unit is defeated, so nothing triggers. P1's Imperial Dark
#// Trooper (SEC_080, 3/3) attacks Yoda (SOR_045, 2/4): both survive (Yoda takes 3, SEC_080 takes 2).
## GIVEN
CommonSetup: brw/bbk/{myLeader:SEC_013;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_045:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
P1LEADER:READY

---

# Front_AttackerSurvivesDefenderDefeated_NoTrigger
#// SEC_013 Luthen Rael (leader front) — only a defeated FRIENDLY ATTACKER triggers Luthen. Here the
#// friendly attacker survives while the defender dies: Imperial Dark Trooper (SEC_080, 3/3) attacks Porg
#// (LOF_254, 1/1), killing it and surviving the 1 counter-damage. Luthen is not offered.
## GIVEN
CommonSetup: brw/bbk/{myLeader:SEC_013;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LOF_254:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION
P1LEADER:READY

---

# Deployed_DefeatedWhileDefending_NoTrigger
#// SEC_013 Luthen Rael (deployed) — the deployed reaction is also gated on ATTACKING. P2's Consular
#// Security Force (SOR_046) attacks and defeats P1's Death Star Stormtrooper (SOR_128, 3/1) while it
#// defends; the deployed Luthen does not fire.
## GIVEN
CommonSetup: brw/bbk/{myLeader:SEC_013:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P2BASEDMG:0
P1NODECISION
P1LEADER:DEPLOYED

---

# Front_SelfDefeatViaHeroicSacrifice_LuthenReacts
#// SEC_013 Luthen Rael (front) — the reaction fires on an EFFECT-driven self-defeat while attacking, not
#// only a combat-counter death. P1 plays SOR_150 Heroic Sacrifice: SOR_095 (3/3 → 5/3) attacks the enemy
#// base for 5, then its granted "when it deals combat damage: defeat it" self-defeats it WHILE ATTACKING.
#// Luthen reacts → exhaust → deal 1 to the enemy base (total 6).

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:SEC_013;
  myBase:JTL_019;
  theirBase:SOR_021;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:6
P1GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED

---

# Deployed_LuthenHimselfDefeatedAttacking_StillDeals2
#// SEC_013 Luthen Rael (deployed) — "a friendly unit defeated while attacking" includes Luthen HIMSELF. The
#// deployed Luthen (2/7, pre-damaged to 1 remaining HP) attacks SOR_063 (2/4 Sentinel) and dies to the 2
#// counter-damage. Even though he has already returned to the leader zone, his deployed reaction still fires
#// → deal 2 to the enemy base.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:SEC_013:1:1:1:6;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1LEADER:NOTDEPLOYED
