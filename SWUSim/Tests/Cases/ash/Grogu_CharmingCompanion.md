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
