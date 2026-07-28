# BaseHit_ExhaustCheaper
#// ASH_016 Shin Hati — "When a friendly unit's attack ends: you may exhaust this leader; if you do, exhaust a
#// unit that costs less than the combat damage dealt to a base this attack." SOR_038 (5 power) hits P2's base
#// for 5; P1 exhausts Shin and exhausts SOR_046 (cost 4 < 5, the only legal target, auto-resolved).
## GIVEN
CommonSetup: gyk/brk/{
  myLeader:ASH_016
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# BaseHit_Decline
#// ASH_016 Shin Hati — the exhaust is optional. After SOR_038 hits P2's base for 5, P1 declines; the enemy
#// SOR_046 is not exhausted and Shin stays ready.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO
## EXPECT
P2GROUNDARENAUNIT:0:READY
P1LEADER:READY

---

# AttackUnit_NoBaseDamage_NoTrigger
#// ASH_016 Shin Hati — no base damage means no trigger. SOR_038 attacks the enemy unit SOR_046 (0 combat
#// damage to a base), so Shin is never offered.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P1LEADER:READY

---

# ExhaustTheAttackerItself
#// ASH_016 Shin Hati — the exhaust target may be an already-exhausted unit, including the attacker itself.
#// Battlefield Marine (SOR_095, cost 2) attacks P2's base for 3 combat; it is now exhausted, but it is the
#// only unit costing less than 3, so Shin's exhaust auto-resolves onto the marine (a legal no-op re-exhaust).
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# BaseDamagedByAbility_NoTrigger
#// ASH_016 Shin Hati — only COMBAT damage to a base counts. Sabine Wren (SOR_142) attacks the enemy Porg
#// (0 combat to a base), then her On Attack deals 1 non-combat damage to P2's base. Since no combat damage
#// reached a base, Shin is not offered and stays ready.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: LOF_254:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:1
P1LEADER:READY

---

# OverwhelmCombatToBase_Exhaust
#// ASH_016 Shin Hati — combat damage that reaches a base via Overwhelm counts. Wampa (SOR_164, 4 power,
#// Overwhelm) attacks Battlefield Marine (SOR_095, 3 HP): 1 excess combat damage spills to P2's base. Shin
#// exhausts and exhausts a unit costing less than 1 — only Porg (LOF_254, cost 0) qualifies (auto-resolved).
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [SOR_095:1:0 LOF_254:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:CARDID:LOF_254
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# AttackToBaseDealsZero_NoTrigger
#// ASH_016 Shin Hati — an attack that deals 0 combat damage to a base does not trigger. Doctor Pershing
#// (ASH_072, 0 power) attacks P2's base for 0; Shin is not offered and stays ready.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_072:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:0
P1LEADER:READY
P1NODECISION

---

# OpponentAttacksBase_NoTrigger
#// ASH_016 Shin Hati — the ability only fires for the controller's own units' attacks. An enemy unit
#// (SOR_095) attacking P1's base for 3 does not offer Shin; she stays ready.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016}
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

# StolenEnemyUnitDealsCombatToBase
#// ASH_016 Shin Hati — a stolen enemy unit counts as a friendly attacker for the trigger. P1 uses Change of
#// Heart (SOR_224) to take control of the enemy Wampa (SOR_164), then attacks P2's base with it for 4 combat
#// damage. Shin exhausts and exhausts a unit costing less than 4 — the enemy Battlefield Marine (cost 2).
## GIVEN
CommonSetup: yyk/brk/{myResources:8;handCardIds:SOR_224;myLeader:ASH_016}
WithP2GroundArena: [SOR_164:1:0 SOR_095:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# Deployed_BaseHit_ExhaustCheaperNoSelfExhaust
#// ASH_016 Shin (DEPLOYED leader unit) — When a friendly unit's attack ends dealing combat damage to a base,
#// Shin may exhaust a unit costing less than that damage, with NO self-exhaust cost (once per round). SOR_038
#// (5 power) hits P2 base for 5; P1 exhausts the enemy SOR_046 (cost 4 < 5).
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:DEPLOYED
P2BASEDMG:5

---

# AttackerDies_OverwhelmBase_ShinStillFires
#// ASH_016 Shin — the "attack ends dealing combat damage to a base" trigger is a leader field observer, so it
#// fires even when the ATTACKER dies in the same combat. AT-ST (SOR_232, pre-damaged 6) attacks SOR_108: it
#// deals 6 (SOR_108 dies, 4 Overwhelm to base) and dies to the 1 counter. Base took 4 combat → Shin may exhaust
#// a unit costing <4 (SOR_095, cost 2).
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_232:1:6 SOR_095:1:0]
WithP2GroundArena: SOR_108:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# Deployed_OverwhelmCombatToBase
#// ASH_016 Shin (DEPLOYED leader unit) — combat damage reaching a base via Overwhelm still triggers, with NO
#// self-exhaust (once per round). Wampa (SOR_164, 4 power, Overwhelm) attacks the enemy Porg (LOF_254, 1 HP):
#// Porg dies, 3 excess combat spills to P2's base. Shin may exhaust a unit costing less than 3 — only the enemy
#// Battlefield Marine (SOR_095, cost 2) qualifies. Shin stays deployed (no self-exhaust).
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [LOF_254:1:0 SOR_095:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:DEPLOYED

---

# Deployed_AttackUnit_NoBaseDamage_NoTrigger
#// ASH_016 Shin (DEPLOYED leader unit) — the trigger needs combat damage dealt to a base. When Shin herself
#// attacks an enemy unit (no base damage), no exhaust is offered.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_016:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P1LEADER:DEPLOYED
