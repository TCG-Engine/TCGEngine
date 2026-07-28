# BaseHit_AdvantageDifferent
#// ASH_013 Ezra Bridger — "When a friendly unit's attack ends: if it dealt 3+ combat damage to a base, you
#// may exhaust this leader; if you do, give an Advantage token to a different unit." SOR_046 (3 power) hits
#// P2's base for 3; P1 exhausts Ezra and gives an Advantage to SOR_095 (the only non-attacker, auto-resolved).
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_013
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P1LEADER:EXHAUSTED

---

# BaseHit_Decline
#// ASH_013 Ezra — the exhaust is optional. SOR_046 hits P2's base for 3, but P1 declines; no Advantage is
#// given and Ezra stays ready.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
P1LEADER:READY

---

# LessThanThreeToBase_NoTrigger
#// ASH_013 Ezra — the rider needs 3+ combat damage to a base. SOR_063 (2 power) hits P2's base for only 2,
#// so Ezra does not trigger.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1NODECISION
P1LEADER:READY
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0

---

# CombatDamageToUnit_NoTrigger
#// ASH_013 Ezra — only combat damage to a BASE counts. SOR_046 attacks the enemy unit SOR_063 (dealing 3 to
#// a unit, 0 to a base), so Ezra does not trigger.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_063:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P1LEADER:READY
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0

---

# OverwhelmBaseDamage_Advantage
#// ASH_013 Ezra — combat damage dealt to a base via Overwhelm counts. AT-ST (SOR_232, 6 power, Overwhelm)
#// attacks Porg (LOF_254, 1 HP); 5 excess combat damage spills to P2's base. P1 exhausts Ezra and gives an
#// Advantage to the only other friendly unit (SOR_095, auto-resolved).
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_232:1:0 SOR_095:1:0]
WithP2GroundArena: LOF_254:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P1LEADER:EXHAUSTED

---

# CombatLessThanThree_NonCombatFillsBase_NoTrigger
#// ASH_013 Ezra — the rider needs 3+ COMBAT damage to a base, not 3+ total. Sabine Wren (SOR_142, 2 power)
#// attacks P2's base for 2 combat, then her On Attack deals 1 more (non-combat) to the base — 3 total but
#// only 2 combat — so Ezra does not trigger and stays ready.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_142:1:0 SOR_095:1:0]
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1LEADER:READY
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0

---

# StillPromptsWhenNoOtherUnits
#// ASH_013 Ezra — the "you may exhaust" offer still appears even with no other units to receive the
#// Advantage (an ability could yet create one). With only the attacker Wampa (SOR_164, 4 power) on board,
#// P1 is offered the exhaust; choosing to exhaust spends Ezra with no valid Advantage target.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:4
P1LEADER:EXHAUSTED

---

# EnemyAttackToBase_NoTrigger
#// ASH_013 Ezra — the rider only fires for the controller's own units' attacks. An enemy unit (SOR_095)
#// attacking P1's base for 3 does not trigger Ezra; he stays ready and no decision is offered.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P1LEADER:READY
P1NODECISION

---

# Deployed_BaseHit_GiveAdvantageNoExhaust
#// ASH_013 Ezra (DEPLOYED leader unit) — When a friendly unit's attack ends dealing 3+ combat damage to a
#// base, Ezra may give an Advantage token to a different unit, with NO self-exhaust cost. SOR_046 hits P2 base
#// for 3; P1 gives Advantage to SOR_095.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P1LEADER:DEPLOYED
P2BASEDMG:3

---

# Deployed_EzraAttacks_GiveAdvantage
#// ASH_013 Ezra (DEPLOYED leader unit) — when Ezra himself attacks a base for 3+ combat damage, his rider
#// fires and gives an Advantage token to a different unit. Ezra (deployed at index 2) attacks P2's base for
#// 3; the attacker Ezra is excluded, so P1 gives the Advantage to SOR_095.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:2:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1LEADER:DEPLOYED

---

# Deployed_AnotherUnitAttacks_EzraIsSelectable
#// ASH_013 Ezra (DEPLOYED leader unit) — when a DIFFERENT friendly unit attacks a base for 3+, Ezra himself
#// is a valid recipient of the Advantage (only the attacker is excluded). SOR_046 (index 0) attacks P2's base
#// for 3; P1 gives the Advantage to the deployed Ezra (index 2).
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-2
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:2:ISLEADERUNIT
P1GROUNDARENAUNIT:2:ADVANTAGECOUNT:1
P1LEADER:DEPLOYED

---

# Deployed_MultipleAttackers_AdvantageEach
#// ASH_013 Ezra (DEPLOYED leader unit) — the rider fires once for EACH friendly unit that attacks and deals
#// 3+ combat damage to a base. SOR_046 (index 0) attacks P2's base for 3 → give an Advantage to SOR_095;
#// then Ezra (index 2) attacks P2's base for 3 → give another Advantage to SOR_095. P2 base takes 6 total and
#// SOR_095 ends with 2 Advantage tokens.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:2:BASE
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:2
P1LEADER:DEPLOYED

---

# Deployed_EnemyAttackToBase_NoTrigger
#// ASH_013 Ezra (DEPLOYED leader unit) — the rider fires only for the controller's own units' attacks. An
#// enemy SOR_095 attacking P1's base for 3 does not trigger Ezra; no Advantage decision is offered.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013:1:1}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P1NODECISION
P1LEADER:DEPLOYED

---

# Deployed_NonCombatToBase_NoTrigger
#// ASH_013 Ezra (DEPLOYED leader unit) — the rider needs 3+ COMBAT damage to a base, not 3+ total. Sabine
#// Wren (SOR_142, 2 power) attacks P2's base for 2 combat, then her On Attack deals 1 more (non-combat) — 3
#// total but only 2 combat — so Ezra does not trigger and no Advantage is given.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
P1LEADER:DEPLOYED

---

# Deployed_NoOtherUnits_NoPrompt
#// ASH_013 Ezra (DEPLOYED leader unit) — the deployed rider gives an Advantage to a DIFFERENT unit with no
#// exhaust cost. With only the deployed Ezra on the board and nothing else to receive the token, his attack
#// dealing 3 combat damage to P2's base offers no Advantage decision at all (unlike the front side, which
#// still prompts the optional exhaust). Ezra attacks alone; play passes with no decision.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_013:1:1}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1NODECISION
P1LEADER:DEPLOYED
