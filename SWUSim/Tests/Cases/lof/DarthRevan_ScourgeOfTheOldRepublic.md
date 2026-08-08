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
#// Intended: does not trigger if a friendly unit attacks an enemy unit and does not defeat it. Plo Koon (6/8)
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
#// Intended: does not trigger if a friendly unit attacks an enemy unit and is defeated. Battlefield Marine (3/3)
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
#// Intended: does not trigger if an ENEMY unit attacks and defeats a friendly unit. P2 Wampa attacks P1 Marine
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
#// Intended: does not trigger if an enemy unit attacks and is defeated by a friendly unit (as the defender).
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
#// Intended: does not trigger if an enemy unit is defeated by an event card effect. P1 plays Vanquish to defeat
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
#// Intended: does not trigger if an enemy unit which is NOT the defender is defeated by a friendly unit's
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
#// DEPLOYED side, Intended: does not trigger if a friendly unit trades with an enemy unit — the attacker is gone,
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
#// DEPLOYED side, Intended: does not trigger when a friendly unit attacks and does not defeat. Plo Koon (6/8)
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
#// DEPLOYED side, Intended: does not trigger when a friendly unit attacks and is defeated. Battlefield Marine
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
#// DEPLOYED side, Intended: does not trigger if an ENEMY unit attacks and defeats a friendly unit. P2 Wampa
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
#// DEPLOYED side, Intended: does not trigger if an enemy unit attacks and is defeated by a friendly DEFENDER.
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
#// DEPLOYED side, Intended: does not trigger if an enemy unit is defeated by an event card effect. P1 plays
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
#// DEPLOYED side, Intended: does not trigger if an enemy unit which is NOT the defender is defeated by a friendly
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
#// FRONT side: TWI Darth Maul (5/6) attacks two units and defeats only one (Warzone Lieutenant 2/2 dies,
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
#// FRONT side, triggers only ONCE: TWI Darth Maul attacks two units and defeats BOTH (Warzone
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
#// DEPLOYED side: TWI Darth Maul attacks two units and defeats only one → one Experience to Maul (no
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

# D_Maul_TwoDefeatBoth_TwoExp
#// DEPLOYED side — RULING (2026-08-07, supersedes the 2025-07-14 one-Experience reading for this side):
#// a two-unit attack that defeats BOTH attacked-and-defeated TWO units, and the deployed side has NO cost,
#// so it fires ONCE PER DEFEATED DEFENDER → TWO Experience. (The FRONT side stays at one — its cost is
#// "exhaust this leader", which an already-exhausted leader cannot pay again; see U_Maul_TwoDefeatBoth_OneExp.)
#// Deployed Maul (TWI_135) defeats both SHD_110 (2/2) + SOR_095 (3/3).
#// The two identical reactions are simultaneous, so the engine asks for a resolution ORDER first
#// (EffectStack-0/EffectStack-1); the order is immaterial here since both entries are identical.
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
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1LEADER:DEPLOYED
P1NODECISION

---

# D_ExpOnlyFromAttackedDefeatedUnit_NotBystander
#// RULING (official, 2025-11-29): Revan gives Experience only for "the unit that was ATTACKED and defeated",
#// NOT for a second unit also defeated during the attack that was NOT a defender.
#// Maul (TWI_135) carries Fallen Lightsaber (SOR_137), whose On Attack deals 1 damage to EACH ground unit
#// the defending player controls — so it reaches a unit that was never attacked. He attacks ONLY SHD_110
#// (2/2): the ping leaves the defender at 1 HP and kills the 1-HP bystander SOR_095, then combat damage
#// finishes the defender. TWO enemy units died this attack but only ONE of them was attacked → ONE
#// Experience (upgrade count 2 = Fallen Lightsaber + 1 Experience), and no second offer is pending.
#// NOTE: this section previously attacked BOTH units, so it never actually contained a bystander and did
#// not test the ruling in its name; it now does.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP1GroundArenaUpgrade: 0:SOR_137
WithP2GroundArena: SHD_110:1:0
WithP2GroundArena: SOR_095:1:2
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1LEADER:DEPLOYED
P1NODECISION

---

# D_DefenderKilledByOnAttackAbility_StillCounts
#// RULING (2026-08-07): a unit "attacks and defeats a unit" if it defeats the DEFENDER at any point during
#// the attack — it does not have to be combat damage that finishes it. Sabine Wren (SOR_142, 2/3) attacks a
#// Wampa (SOR_164, 4/5) already at 4 damage; her On Attack spends the lethal point, so the Wampa is defeated
#// BEFORE combat damage and the attack deals none (Sabine survives at 0 damage instead of trading into a 4
#// power counter-hit). Deployed Revan must still offer the Experience.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_164:1:4
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_142
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:READY
P1LEADER:DEPLOYED

---

# U_DefenderKilledByOnAttackAbility_StillCounts
#// FRONT side of the same 2026-08-07 ruling: defeating the DEFENDER with an on-attack ability (rather than
#// with combat damage) still counts as "attacks and defeats". Sabine Wren (SOR_142) attacks a Wampa
#// (SOR_164, 4/5) already at 4 damage and spends her On Attack point on it. The front reaction costs the
#// leader's exhaust → leader EXHAUSTED, Experience on Sabine.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_164:1:4
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_142
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED

---

# U_DefenderLeavesPlayWithoutBeingDefeated_NoTrigger
#// The load-bearing negative for the 2026-08-07 ruling: the trigger keys on the defender being DEFEATED
#// during the attack, NOT merely on it leaving play. General Grievous (SEC_187) bounces HIMSELF to hand on
#// being attacked, so the attack fizzles with no defeat at all → no Experience, leader stays READY.
#// (A plain 3/3 attacker, so the only step-1 trigger is Grievous own and there is no ordering decision.)
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_187:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:NOTDEPLOYED
P1LEADER:READY

---

# U_StolenUnitAttacksAndDefeats_GetsExperience
#// "a FRIENDLY unit" is about CONTROL, not ownership: a unit P1 has taken control of (owner = seat 2)
#// that attacks and defeats still earns the Experience. P1 controls an opponent-owned Wampa (SOR_164,
#// 4/5) which attacks and kills a 2/2 (SHD_110). Front side → exhaust the leader, Experience on the
#// stolen Wampa.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_164:2
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1LEADER:EXHAUSTED

---

# D_DeployedFiresMultipleTimesInSamePhase
#// DEPLOYED side has NO cost (no "exhaust this leader"), so unlike the front side it is not limited to
#// once per phase: two separate friendly attacks that each defeat a unit each give an Experience. Two
#// 4/5 Wampas (SOR_164) each kill a 2/2 (SHD_110) in the same action phase → one Experience each.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SHD_110:1:0
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1LEADER:DEPLOYED

---

# D_MaulSaber_TwoOneHpDefenders_BothPinged_TwoExp
#// RULING (2026-08-07) — the combined case: Darth Maul (TWI_135, "can attack 2 units instead of 1") with
#// Fallen Lightsaber (SOR_137) attached. He is a Force unit, so the saber grants "On Attack: deal 1 damage
#// to each ground unit the defending player controls". Attacking TWO 1-HP units, the ping alone is lethal
#// to both — so both are defeated during the attack without needing combat damage. Both were ATTACKED and
#// both were DEFEATED, so deployed Revan fires twice → TWO Experience (upgrade count 3 = saber + 2 exp).
#// Two identical simultaneous reactions ⇒ an order prompt first; the order is immaterial.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP1GroundArenaUpgrade: 0:SOR_137
WithP2GroundArena: SHD_110:1:1
WithP2GroundArena: SOR_095:1:2
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1LEADER:DEPLOYED
P1NODECISION

---

# U_MaulSaber_TwoOneHpDefenders_BothPinged_OneExp
#// FRONT-side counterpart of the section above — the load-bearing half of the 2026-08-07 ruling's
#// asymmetry. Identical board, but Revan is UNDEPLOYED: his front reaction costs "you may exhaust this
#// leader", and an already-exhausted leader cannot pay it a second time, so two defeated defenders still
#// yield only ONE Experience (upgrade count 2 = saber + 1 exp). Leader ends EXHAUSTED.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_017;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP1GroundArenaUpgrade: 0:SOR_137
WithP2GroundArena: SHD_110:1:1
WithP2GroundArena: SOR_095:1:2
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED
P1NODECISION
