# ExhaustDeployedLeaderUnit_Bonus
#// ASH_203 Mando's N-1 Starfighter (Space, 1/3, Support) — On Attack: you may exhaust a friendly
#// (non-upgrade) leader for +2/+0 this attack. Here the friendly leader is a DEPLOYED leader UNIT (SOR_016):
#// exhausting it grants the +2/+0, so the N-1 deals 3 to the enemy base. The buff is for this attack only —
#// the N-1 is back to power 1 afterward.
## GIVEN
CommonSetup: yyk/yyk/{myLeader:SOR_016:1:1}
WithP1SpaceArena: ASH_203:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1LEADER:EXHAUSTED
P1SPACEARENAUNIT:0:CARDID:ASH_203
P1SPACEARENAUNIT:0:POWER:1

---

# SupportGrantsExhaustLeaderBonus
#// Support grants the On Attack exhaust-a-leader ability to the chosen attacker. A friendly space unit
#// (IBH_012, 2/2) is chosen; the granted On Attack offers the exhaust — exhausting the friendly leader
#// grants IT the +2/+0, so it deals 4 to the enemy base. The exhaust prompt resolves before the attack
#// target is chosen. The leader ends exhausted.
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_203;myLeader:SOR_016:1}
WithP1SpaceArena: IBH_012:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:4
P1LEADER:EXHAUSTED

---

# ExhaustUndeployedLeader_Bonus
#// ASH_203 Mando's N-1 Starfighter — the "friendly leader" it may exhaust can be the UNDEPLOYED base-zone
#// leader, not just a deployed leader unit. The default ready leader is the only valid target; P1 accepts,
#// the leader exhausts, and the N-1 gets +2/+0 so it deals 3 to the enemy base.
## GIVEN
CommonSetup: yyk/yyk
WithP1SpaceArena: ASH_203:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P1LEADER:EXHAUSTED
P1SPACEARENAUNIT:0:CARDID:ASH_203
P1SPACEARENAUNIT:0:POWER:1

---

# NoValidTarget_LeaderAlreadyExhausted
#// ASH_203 Mando's N-1 Starfighter — if the only friendly leader is already exhausted there is no valid
#// target for the exhaust clause, so the ability offers nothing: no decision is presented and the N-1 deals
#// only its base 1 combat damage to the enemy base.
## GIVEN
CommonSetup: yyk/yyk/{myLeader:SOR_016:0}
WithP1SpaceArena: ASH_203:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# DeclineAbility_NoBonus_LeaderStaysReady
#// The exhaust-a-leader clause is optional. Attacking an enemy unit (SHD_151) the player declines: the N-1
#// deals only its base 1 combat damage and the friendly leader stays ready.
## GIVEN
CommonSetup: yyk/yyk
WithP1SpaceArena: ASH_203:1:0
WithP2SpaceArena: SHD_151:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:NO
## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:1
P1LEADER:READY

---

# PilotDeployedLeaderIsNotAValidPayer
#// ASH_203's printed "(non-upgrade) leader", and the official ruling of 07/21/2026: "You may exhaust
#// leaders or leader UNITS as part of Mando's N-1 Starfighter's On Attack ability. UPGRADES CAN'T BE
#// EXHAUSTED." A leader deployed via Piloting IS an upgrade — it lives as a Subcard on its host vehicle
#// (DeployedUniqueID 0), and the host is not a leader. So with JTL_001 Asajj deployed as a pilot onto
#// SOR_225 and no other leader, there is NO eligible payer: the ability offers nothing and the N-1 deals
#// its base 1. This section used to assert the opposite (that the pilot-deployed leader could pay), which
#// the ruling contradicts; the old implementation "paid" by flipping the Leader-zone Ready flag while the
#// leader was not even in that zone as an exhaustable card.
## GIVEN
SkipPreGame: true
P1OnlyActions: true
CommonSetup: rrk/yyk/{myResources:6; myLeader:JTL_001; myLeaderDeployedPilot:1}
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: ASH_203:1:0
## WHEN
- P1>AttackSpaceArena:1:BASE
## EXPECT
P2BASEDMG:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:1:CARDID:ASH_203
P1SPACEARENAUNIT:1:POWER:1
P1NODECISION

---

# DeployedLeaderUnitActuallyExhausts
#// Paying with a DEPLOYED leader must exhaust the leader UNIT — that is what the cost means and what
#// stops it attacking. The old code flipped only the Leader-zone `Ready` flag (which gates the UNDEPLOYED
#// Action and nothing else), so the leader unit stayed ready on the board after "paying" with it. The
#// sibling section ExhaustDeployedLeaderUnit_Bonus asserts the zone flag; this one asserts the board.
## GIVEN
CommonSetup: yyk/yyk/{myLeader:SOR_016:1:1}
WithP1SpaceArena: ASH_203:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# TwinSuns_SecondLeaderPaysWhenTheFirstIsExhausted
#// "A friendly leader" is not "leader 0". Twin Suns seat with leader 0 already exhausted and leader 1
#// ready: the ready second leader can pay, so the +2/+0 applies and the base takes 3. The offer used to
#// read GetLeader($player)[0] and returned early here, so the ability never prompted at all — reported
#// live 2026-09-03 as "did not give me the option at all if my first leader was already exhausted".
## GIVEN
CommonSetup: yyk/yyk/{myLeader:SOR_016:0; myLeader2:SHD_011:1}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_203:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P1LEADER0:EXHAUSTED
P1LEADER1:EXHAUSTED

---

# TwinSuns_ChoosesWhichLeaderExhausts
#// With BOTH leaders able to pay the player picks which one. The old bare YES/NO always spent leader 0
#// with no choice offered. Here P1 accepts and names Kylo Ren (leader 1), so Thrawn (leader 0) stays
#// READY — the assertion that would still pass on the old code if the choice were ignored.
## GIVEN
CommonSetup: yyk/yyk/{myLeader:SOR_016:1; myLeader2:SHD_011:1}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_203:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:Kylo_Ren
## EXPECT
P2BASEDMG:3
P1LEADER0:READY
P1LEADER1:EXHAUSTED

---

# TwinSuns_ChoosesTheFirstLeader
#// The mirror of the section above — naming Grand Admiral Thrawn spends leader 0 and leaves Kylo Ren
#// ready. Both directions are pinned so a picker that silently ignores the answer cannot pass.
## GIVEN
CommonSetup: yyk/yyk/{myLeader:SOR_016:1; myLeader2:SHD_011:1}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_203:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:Grand_Admiral_Thrawn
## EXPECT
P2BASEDMG:3
P1LEADER0:EXHAUSTED
P1LEADER1:READY

---

# TwinSuns_DeclineWithTwoLeaders_NeitherExhausts
#// Declining the "you may" with two eligible payers spends nothing: both leaders stay ready and the N-1
#// deals its base 1. Guards the multi-payer branch's decline path, which is a separate handler from the
#// single-payer YES/NO.
## GIVEN
CommonSetup: yyk/yyk/{myLeader:SOR_016:1; myLeader2:SHD_011:1}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_203:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:NO
## EXPECT
P2BASEDMG:1
P1LEADER0:READY
P1LEADER1:READY
