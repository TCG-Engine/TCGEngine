# Interactions_P1Pass_P2Attack
## GIVEN
SkipPreGame: true
CommonSetup: ygk/grw/{
  myLeader:JTL_006;
  myBase:SEC_025;
  myBaseDamage:16;
  theirLeader:JTL_012;
  theirBase:JTL_024;
  theirBaseDamage:23
}
WithP1Resources: 5
WithP2Resources: 5
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

WithP2GroundArena: ASH_155:1:5
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>Pass
- P2>Claim
- P2>AnswerDecision:myGroundArena-0
- P2>UseLeaderAbility

## EXPECT
P1BASEDMG:18
P2LEADER:READY

---

# Interactions_P1Pass_P2Decline
## GIVEN
SkipPreGame: true
CommonSetup: ygk/grw/{
  myLeader:JTL_006;
  myBase:SEC_025;
  myBaseDamage:16;
  theirLeader:JTL_012;
  theirBase:JTL_024;
  theirBaseDamage:23
}
WithP1Resources: 5
WithP2Resources: 5
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

WithP2GroundArena: ASH_155:1:5
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>Pass
- P2>Claim
- P2>AnswerDecision:-
- P2>AttackGroundArena:0
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:16
P1LEADER:READY

---

# Grogu_Accept
#// ASH_155 Grogu (2/6) — "When you take the initiative: you may attack with a unit." P1 claims initiative
#// with a ready SOR_046 (3/7) in play; Grogu offers a bonus attack → P1 attacks P2's base for 3. The bonus
#// attack must NOT swap the turn (the initiative pass already did) — proven by P2 then taking the next
#// action (its SOR_046 hits P1's base for 3). Initiative stays P1_CLAIMED.
## GIVEN
CommonSetup: rrk/rgw
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0
- P2>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1BASEDMG:3
INITIATIVECOUNTER:P1_CLAIMED

---

# Grogu_Decline
#// ASH_155 Grogu — the bonus attack is a "may". P1 claims initiative and DECLINES (AnswerDecision:-), so
#// no attack happens (P2's base untouched). The decline path also leaves the turn correct — P2 acts next,
#// hitting P1's base for 3. Initiative stays P1_CLAIMED.
## GIVEN
CommonSetup: rrk/rgw
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P1>AnswerDecision:-
- P2>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:0
P1BASEDMG:3
INITIATIVECOUNTER:P1_CLAIMED

---

# Grogu_NoReadyUnit
#// ASH_155 Grogu — with no READY friendly unit to attack with (Grogu itself is exhausted and is the only
#// friendly unit), the trigger fizzles cleanly: no dangling decision is left, so P2 takes the next action
#// normally (hits P1's base for 3). Initiative stays P1_CLAIMED.
## GIVEN
CommonSetup: rrk/rgw
WithActivePlayer: 1
WithP1GroundArena: ASH_155:0:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
INITIATIVECOUNTER:P1_CLAIMED
