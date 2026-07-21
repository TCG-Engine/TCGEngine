# AttackDefeatExp
#// LOF_017 Darth Revan — When a friendly unit attacks and defeats a unit: you may exhaust this leader to
#// give that unit an Experience token. Plo Koon defeats SOR_059; P1 exhausts Revan to make Plo Koon 7/9.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:LOF_017;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:7
P1LEADER:EXHAUSTED

---

# Deployed_FriendlyAttackDefeat_GivesExp
#// LOF_017 Darth Revan (DEPLOYED leader unit) — same "when a friendly unit attacks and defeats a unit: give
#// an Experience token" trigger, but with NO exhaust cost (still optional). Battlefield Marine (3/3, index 0)
#// defeats SOR_059; deployed Revan (index 1) offers the token → Marine becomes 4/4.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_059:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4

---

# Deployed_Decline_NoExp
#// The deployed trigger is optional — declining gives no token (Marine stays 3/3).
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_059:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:3

---

# Deployed_SelfExp_AndFiresEveryAttack
#// Deployed Revan gives the token to HIMSELF when he attacks and defeats (index 1 → 4/7). Unlike the front
#// side (exhaust-limited to once), the deployed trigger fires on a SECOND friendly attack the same phase too
#// — Battlefield Marine then defeats the other SOR_059 and also gets a token (4/4).
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_059:1:0
## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:7

---

# U_Decline_NoExp
#// Front side is a MAY (exhaust cost). Declining the YESNO leaves the leader READY and gives no token.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_059:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:READY

---

# U_NoDefeat_NoTrigger
#// FT: does not trigger if a friendly unit attacks an enemy unit and does not defeat it. Plo Koon (6/8)
#// attacks Consular Security Force (3/7) — CSF survives, so no defender is defeated → leader stays READY.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:1
P1LEADER:READY

---

# U_AttackerDefeated_NoTrigger
#// FT: does not trigger if a friendly unit attacks an enemy unit and is defeated. Battlefield Marine (3/3)
#// attacks Wampa (4/5): Wampa survives and kills the Marine → no defender defeated → leader stays READY.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1LEADER:READY

---

# U_EnemyAttacksDefeatsFriendly_NoTrigger
#// FT: does not trigger if an ENEMY unit attacks and defeats a friendly unit. P2 Wampa attacks P1 Marine
#// and kills it — the attacker is not friendly to Revan's controller, so P1's leader stays READY.
## GIVEN
CommonSetup: bgk/rrk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:READY

---

# U_EnemyDefeatedByFriendlyDefender_NoTrigger
#// FT: does not trigger if an enemy unit attacks and is defeated by a friendly unit (as the defender).
#// P2 Warzone Lieutenant (2/2) attacks P1 Marine (3/3); the Marine kills the attacker back, but the Marine
#// did not ATTACK, so P1's leader stays READY.
## GIVEN
CommonSetup: bgk/rrk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_110:1:0
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:READY

---

# U_EventDefeat_NoTrigger
#// FT: does not trigger if an enemy unit is defeated by an event card effect. P1 plays Vanquish to defeat
#// the enemy Warzone Lieutenant — no attack occurred, so the leader stays READY.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_078
WithP1Resources: 8
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:READY

---

# U_NonDefenderAbilityDefeat_NoTrigger
#// FT: does not trigger if an enemy unit which is NOT the defender is defeated by a friendly unit's
#// on-attack ability. Avenger (8/8, space) attacks the enemy BASE; its On Attack makes the opponent defeat
#// a unit they control (their lone Wampa). The DEFENDER (base) was not a defeated unit → leader stays READY.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_040:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:READY

---

# D_Trade_NoTrigger
#// DEPLOYED side, FT: does not trigger if a friendly unit trades with an enemy unit — the attacker is gone,
#// so there is no token target (no prompt). Battlefield Marine (3/3) attacks Freelance Assassin (4/2); both
#// die. Leader remains DEPLOYED, only the leader unit survives in P1's arena.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: TWI_212:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1LEADER:DEPLOYED

---

# D_NoDefeat_NoTrigger
#// DEPLOYED side, FT: does not trigger when a friendly unit attacks and does not defeat. Plo Koon (6/8)
#// attacks Consular Security Force (3/7); CSF survives. No experience given; leader stays DEPLOYED.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:DEPLOYED

---

# D_AttackerDefeated_NoTrigger
#// DEPLOYED side, FT: does not trigger when a friendly unit attacks and is defeated. Battlefield Marine
#// (3/3) attacks Wampa (4/5); Marine dies, no defender defeated. Leader stays DEPLOYED.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1LEADER:DEPLOYED

---

# D_EnemyAttacksDefeatsFriendly_NoTrigger
#// DEPLOYED side, FT: does not trigger if an ENEMY unit attacks and defeats a friendly unit. P2 Wampa
#// attacks P1 Marine and kills it — enemy is the attacker, so P1's deployed leader does not trigger.
## GIVEN
CommonSetup: bgk/rrk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1LEADER:DEPLOYED

---

# D_EnemyDefeatedByFriendlyDefender_NoTrigger
#// DEPLOYED side, FT: does not trigger if an enemy unit attacks and is defeated by a friendly DEFENDER.
#// P2 Warzone Lieutenant (2/2) attacks P1 Marine (3/3); the Marine kills it back but did not attack, so
#// P1's deployed leader does not trigger.
## GIVEN
CommonSetup: bgk/rrk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_110:1:0
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1LEADER:DEPLOYED

---

# D_EventDefeat_NoTrigger
#// DEPLOYED side, FT: does not trigger if an enemy unit is defeated by an event card effect. P1 plays
#// Vanquish to defeat the enemy Warzone Lieutenant — no attack, so the deployed leader does not trigger.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_078
WithP1Resources: 8
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:DEPLOYED

---

# D_NonDefenderAbilityDefeat_NoTrigger
#// DEPLOYED side, FT: does not trigger if an enemy unit which is NOT the defender is defeated by a friendly
#// unit's on-attack ability. Avenger (8/8, space) attacks the enemy BASE; its On Attack makes the opponent
#// defeat their lone Wampa. The defender (base) was not a defeated unit → deployed leader does not trigger.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_040:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:DEPLOYED

---

# U_Maul_TwoDefeatOne_OneExp
#// FT (front): TWI Darth Maul (5/6) attacks two units and defeats only one (Warzone Lieutenant 2/2 dies,
#// Consular Security Force 3/7 survives). The front reaction fires once → exhaust leader → one Experience
#// to Maul (6/7). Leader EXHAUSTED.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SHD_110:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1LEADER:EXHAUSTED

---

# U_Maul_TwoDefeatBoth_OneExp
#// FT (front): "should only trigger once" — TWI Darth Maul attacks two units and defeats BOTH (Warzone
#// Lieutenant 2/2 + Battlefield Marine 3/3). The front reaction is exhaust-limited, so Maul gets ONE
#// Experience token only. Leader EXHAUSTED.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SHD_110:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1LEADER:EXHAUSTED

---

# D_Maul_TwoDefeatOne_OneExp
#// FT (deployed): TWI Darth Maul attacks two units and defeats only one → one Experience to Maul (no
#// exhaust cost). Leader stays DEPLOYED.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SHD_110:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1LEADER:DEPLOYED

---

# U_Trade_LeaderStaysReady
#// FRONT side — RULING (official, 2025-07-14: "a unit attacks and defeats a unit if it defeats the defender
#// at any point during the attack"). On a TRADE (friendly attacker defeats the defender but dies in the same
#// combat), the recipient "that friendly unit" is gone, so SWUSim offers NO may-exhaust prompt and the leader
#// stays READY (intentional divergence — a no-effect prompt is skipped). SOR_095 (3/3) trades with TWI_212 (4/2).
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: TWI_212:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1LEADER:READY

---

# D_Maul_TwoDefeatBoth_OneExp
#// DEPLOYED side — RULING (2025-07-14): "attacks and defeats a unit" keys on defeating the DEFENDER, so an
#// attack that defeats TWO units still triggers the reaction ONCE → ONE Experience (not two). Deployed Maul
#// (TWI_135) defeats both SHD_110 (2/2) + SOR_095 (3/3).
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SHD_110:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1LEADER:DEPLOYED

---

# D_ExpOnlyFromAttackedDefeatedUnit_NotBystander
#// RULING (official, 2025-11-29): Revan gives Experience only for "the unit that was ATTACKED and defeated",
#// NOT for a second unit also defeated during the attack that was not the defender (e.g. a bystander whose
#// shield was popped). Deployed Maul (TWI_135) attacks the defender SHD_110 (2/2, the attacked unit) AND his
#// on-attack ability defeats a second bystander SOR_095 (3/3, NOT attacked) → still only ONE Experience.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SHD_110:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1LEADER:DEPLOYED
