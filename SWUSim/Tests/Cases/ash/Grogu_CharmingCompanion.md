# Deployed_Passive_AttackerDebuff
#// ASH_018 Grogu (deployed) — passive: while another friendly unit is attacking, the defending unit
#// gets -1/-0. P1's Battlefield Marine (SOR_095, 3/3) attacks the enemy wall SOR_046 (3/7); the
#// defender's counter-power drops 3->2, so the Marine takes only 2 (survives).

## GIVEN
CommonSetup: gyw/brk/{
  myLeader:ASH_018:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Deployed_Passive_DefenderBuff
#// ASH_018 Grogu (deployed) — passive: while another friendly unit is defending, it gets +1/+0. P2's
#// SOR_046 (3/7) attacks P1's Battlefield Marine (SOR_095, 3/3); the Marine's counter-power rises
#// 3->4, so the attacker takes 4 (the Marine still dies to the 3 it takes).

## GIVEN
CommonSetup: gyw/brk/{
  myLeader:ASH_018:1:1:1
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENACOUNT:1

---

# Decline_NoDeploy
#// ASH_018 Grogu — declining the optional deploy leaves Grogu undeployed. P1 plays ASH_109 (unique, cost 4)
#// and declines to deploy.
## GIVEN
CommonSetup: gyw/brk/{
  myLeader:ASH_018
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: ASH_109
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1LEADER:NOTDEPLOYED

---

# PlayUqFourPlus_Deploy
#// ASH_018 Grogu — "When you play a unique unit that costs 4 or more: if this leader is ready, you may deploy
#// him." P1 plays ASH_109 (unique, cost 4) and chooses to deploy Grogu (his only deploy path — no Epic Action).
## GIVEN
CommonSetup: gyw/brk/{
  myLeader:ASH_018
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: ASH_109
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1LEADER:DEPLOYED

---

# Undeployed_UniqueFourPlus_MayDeploy
#// ASH_018 Grogu (undeployed, ready) — "When you play a unique unit that costs 4 or more: if this leader is
#// ready, you may deploy him." P1 plays SOR_242 (General Dodonna, unique cost 4) and chooses YES; Grogu
#// deploys, joining Dodonna in the ground arena (count 2).
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_242
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2

---

# Undeployed_UniqueFourPlus_DeclineNoDeploy
#// ASH_018 Grogu — the deploy is optional; declining leaves Grogu undeployed and ready.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_242
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1LEADER:READY
P1GROUNDARENACOUNT:1

---

# Undeployed_NonUniqueFour_NoTrigger
#// ASH_018 Grogu — the trigger requires a UNIQUE unit. Playing SOR_046 (non-unique, cost 4) offers no
#// deploy; Grogu stays undeployed and only SOR_046 is in the arena.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_046
## WHEN
- P1>PlayHand:0
## EXPECT
P1LEADER:READY
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046

---

# Undeployed_UniqueThree_NoTrigger
#// ASH_018 Grogu — the trigger requires cost 4+. Playing SOR_230 (General Veers, unique, cost 3) offers no
#// deploy; Grogu stays undeployed.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_230
## WHEN
- P1>PlayHand:0
## EXPECT
P1LEADER:READY
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_230

---

# TriggerAgainAfterDecline
#// ASH_018 Grogu — declining the deploy on one unique unit does not consume the trigger. P1 plays ASH_109
#// (unique cost 4, space), declines; then plays SOR_242 (General Dodonna, unique cost 4, ground) and accepts.
#// Grogu deploys on the second play.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: ASH_109 SOR_242
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1LEADER:DEPLOYED

---

# UniqueFivePlus_Deploy
#// ASH_018 Grogu — the trigger fires for a unique unit costing MORE than 4 too. P1 plays SOR_196 (Chewbacca,
#// unique cost 5) and deploys Grogu, joining Chewbacca in the ground arena (count 2).
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_196
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2

---

# Exhausted_NoTrigger
#// ASH_018 Grogu — the deploy trigger requires "if this leader is ready". With Grogu exhausted, playing a
#// unique cost-4 unit (SOR_242) offers no deploy; Grogu stays exhausted on the leader side.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018:0}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: SOR_242
## WHEN
- P1>PlayHand:0
## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:1

---

# OpponentPlaysUnique_NoTrigger
#// ASH_018 Grogu — the trigger only fires when GROGU'S controller plays the unit. P2 playing a unique cost-4
#// unit (SOR_242) does not deploy P1's Grogu.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018;theirResources:10;theirhandCardIds:SOR_242}
SkipPreGame: true
WithActivePlayer: 2
## WHEN
- P2>PlayHand:0
## EXPECT
P1LEADER:NOTDEPLOYED

---

# UniqueUpgrade_NoTrigger
#// ASH_018 Grogu — the trigger requires a unique UNIT, not any unique card. Playing SHD_126 (The Darksaber,
#// unique cost 4 upgrade) on a friendly unit does not deploy Grogu.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SHD_126
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1LEADER:NOTDEPLOYED

---

# Deployed_SelfDefending_NoSelfBuff
#// ASH_018 Grogu (deployed, 0/3) — his defender buff is "another friendly unit"; it does NOT apply to himself.
#// P2's SEC_080 (3/3) attacks Grogu directly: Grogu deals 0 back (no +1 self-buff) and is defeated, flipping
#// to the leader side.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018:1:1:1}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:NOTDEPLOYED

---

# Deployed_SelfAttacking_NoEnemyDebuff
#// ASH_018 Grogu (deployed, 0/3) — his attacker debuff is "another friendly unit is attacking"; it does NOT
#// apply when Grogu himself attacks. Grogu attacks SEC_080 (3/3): the enemy gets no -1/-0, deals 3 back, and
#// Grogu is defeated. SEC_080 takes 0 (Grogu has 0 power).
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:NOTDEPLOYED

---

# UniquePilotAsUpgrade_NoTrigger
#// ASH_018 Grogu — the trigger requires playing a unique UNIT costing 4+. A unique Pilot costing 5 (JTL_103
#// Chewbacca) that is played as an UPGRADE via Piloting onto a friendly Vehicle does not enter as a unit, so
#// Grogu does not deploy. P1 plays Chewbacca with Piloting onto the seated A-Wing; Grogu stays on leader side.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_103
WithP1SpaceArena: SEC_213:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
