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
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:7
