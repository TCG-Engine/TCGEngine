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

# ExhaustPilotLeaderUnit_Bonus
#// ASH_203 Mando's N-1 Starfighter — the "friendly leader" it may exhaust also covers a leader deployed as a
#// PILOT: JTL_001 Asajj is deployed as a pilot onto the vehicle SOR_225, which becomes the friendly leader
#// unit. When the N-1 (index 1) attacks the base, it may exhaust that leader for +2/+0, dealing 3. The leader
#// ends exhausted and the N-1 reverts to power 1 after the attack.
## GIVEN
SkipPreGame: true
P1OnlyActions: true
CommonSetup: rrk/yyk/{myResources:6; myLeader:JTL_001; myLeaderDeployedPilot:1}
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: ASH_203:1:0
## WHEN
- P1>AttackSpaceArena:1:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:ISLEADERUNIT
P1SPACEARENAUNIT:1:CARDID:ASH_203
P1SPACEARENAUNIT:1:POWER:1
P1LEADER:EXHAUSTED
