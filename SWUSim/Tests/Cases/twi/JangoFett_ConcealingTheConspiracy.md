# Combo_IG2000_Deployed_ExhaustsAll
#// TWI_016 Jango Fett (DEPLOYED) + JTL_140 IG-2000 — the deployed side has NO leader-exhaust cost, so it
#// can exhaust EVERY enemy the AoE damages. P1 plays IG-2000, deals 1 to two enemies, and exhausts BOTH.
#// (Contrast the front-side tests, which cap at one per turn.)
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_016:1:1;myResources:4;handCardIds:JTL_140}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENACOUNT:2
P1LEADER:DEPLOYED

---

# Combo_IG2000_Front_AcceptFirst
#// TWI_016 Jango Fett (FRONT) + JTL_140 IG-2000 — P1 ACCEPTS the first ping (exhaust Jango → exhaust enemy
#// 0), then tries to accept the second too. The front side's cost (exhausting the leader) is already spent,
#// so the second acceptance can't pay and does nothing — enemy 1 stays ready. Proves the once-per-turn cap.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_016:1;myResources:4;handCardIds:JTL_140}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:1:READY
P2GROUNDARENACOUNT:2
P1LEADER:EXHAUSTED

---

# Combo_IG2000_Front_PassFirst_AcceptSecond
#// TWI_016 Jango Fett (FRONT) + JTL_140 IG-2000 — "When Played: deal 1 to each of up to 3 units" damages
#// multiple enemies, pinging Jango once per enemy. On the front side the single leader-exhaust can pay for
#// only ONE, but the player chooses WHICH: here P1 DECLINES the first ping and ACCEPTS the second, so the
#// second enemy is exhausted (Jango's exhaust wasn't spent on the first).
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_016:1;myResources:4;handCardIds:JTL_140}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:-
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENACOUNT:2
P1LEADER:EXHAUSTED

---

# Combo_IG2000_Front_PassTwo_AcceptThird
#// TWI_016 Jango Fett (FRONT) + JTL_140 IG-2000 — three enemies pinged; P1 declines the first TWO offers
#// and accepts the THIRD. Only the third enemy is exhausted, proving the single leader-exhaust can be spent
#// on any one of the pings (declines don't consume it).
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_016:1;myResources:4;handCardIds:JTL_140}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0 SOR_046:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2
- P1>AnswerDecision:-
- P1>AnswerDecision:-
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:2:DAMAGE:1
P2GROUNDARENAUNIT:2:EXHAUSTED
P2GROUNDARENACOUNT:3
P1LEADER:EXHAUSTED

---

# Combo_WarJuggernaut170_Deployed_ExhaustsAll
#// TWI_016 Jango Fett (DEPLOYED) + JTL_170 War Juggernaut — "When Played: deal 1 to each of any number of
#// units" is friendly-unit AoE damage, so each enemy it hits pings the deployed Jango. P1 damages two
#// enemies and exhausts BOTH (deployed side, no leader cost).
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_016:1:1;myResources:6;handCardIds:JTL_170}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENACOUNT:2
P1LEADER:DEPLOYED

---

# Deployed_CombatDamage_Decline
#// TWI_016 Jango Fett (DEPLOYED) — the deployed-side "may exhaust that unit" is declined ("-"): the enemy
#// stays ready. Pre-placed deployed Jango (myLeader:TWI_016:1:1) attacks the enemy 3/7.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:1:1}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENACOUNT:1
P1LEADER:DEPLOYED

---

# Deployed_CombatDamage_Exhausts
#// TWI_016 Jango Fett (DEPLOYED leader unit) — "When a friendly unit deals damage to an enemy unit: You may
#// exhaust that unit." (No leader-exhaust cost on the deployed side.) P1 deploys Jango (cost 5) and attacks
#// the enemy 3/7 with him: 3 combat damage (enemy survives, ready). Jango's controller may exhaust that
#// enemy unit → P1 says YES. Uses the real DeployLeader → attack execution path.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016;myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:1
P1LEADER:DEPLOYED

---

# Front_AbilityDamage_Exhausts
#// TWI_016 Jango Fett (FRONT) — the trigger also fires on ABILITY damage from a friendly UNIT, not just
#// combat. P1 plays LOF_259 Ravening Gundark (When Played: deal 1 to a ground unit) and targets the enemy
#// 3/7 (survives, still ready). The damage source is a friendly unit, so Jango triggers: P1 exhausts Jango
#// to exhaust that enemy unit. (Proves the SWU_DMG_SRC source-tracking path.)
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:1;myResources:5;handCardIds:LOF_259}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# Front_CombatDamage_Decline
#// TWI_016 Jango Fett (FRONT) — the "may" is declined: P1 answers "-" to the exhaust offer, so nothing is
#// exhausted. The enemy stays ready and Jango stays ready (the leader-exhaust cost was not paid).
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:READY
P1LEADER:READY
P1LEADER:NOTDEPLOYED

---

# Front_CombatDamage_Exhausts
#// TWI_016 Jango Fett (leader, FRONT/undeployed) — "When a friendly unit deals damage to an enemy unit:
#// You may exhaust this leader. If you do, exhaust that enemy unit." P1's Battlefield-durable unit (SOR_046
#// 3/7) attacks the enemy 3/7, deals 3 combat damage (enemy survives, still ready). Jango's controller may
#// exhaust Jango to exhaust that enemy unit. P1 says YES → enemy exhausted, Jango leader exhausted.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED
P1LEADER:NOTDEPLOYED

---

# Front_EnemyAlreadyExhausted_AutoSkip
#// TWI_016 Jango Fett (FRONT) — direction/target guard: the enemy unit is ALREADY exhausted, so exhausting
#// it again gains nothing. The trigger auto-skips (no offer), Jango stays ready, and the friendly attacker
#// taking counter-damage does NOT trigger Jango (damage to a friendly unit is not "damage to an enemy unit").
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:0:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:READY
P1NODECISION

---

# Front_EventDamage_NoTrigger
#// TWI_016 Jango Fett (FRONT) — an EVENT is NOT a unit, so event damage to an enemy unit must NOT trigger
#// Jango. P1 plays Open Fire (SOR_172, "Deal 4 damage to a unit") on the enemy 3/7 (survives). The source
#// is an event, not a friendly unit → no exhaust offer, Jango stays ready. (Guards the source-must-be-a-unit
#// requirement.)
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:1;myResources:6;handCardIds:SOR_172}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:READY
P1LEADER:READY
P1NODECISION

---

# Front_LeaderExhausted_AutoSkip
#// TWI_016 Jango Fett (FRONT) — the leader is already EXHAUSTED (myLeader:TWI_016:0), so the "exhaust this
#// leader" cost cannot be paid. The trigger must auto-skip cleanly: no decision, the enemy is not exhausted,
#// and P1 keeps its position. (SEC_069 lesson — don't offer a "may" that can gain nothing.)
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:0}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED
P1NODECISION
