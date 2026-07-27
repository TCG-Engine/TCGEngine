# NoForce_NoEffect
#// LOF_035 Talzin's Assassin — without the Force the optional "use the Force" is not offered (you can't
#// use a Force you don't control): the unit just enters play and no debuff happens.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4;handCardIds:LOF_035}
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:4
P1NODECISION

---

# UseForce_Debuff3
#// LOF_035 Talzin's Assassin (4/4) — When Played: you may use the Force → give a unit -3/-3 for this
#// phase. P1 plays it with the Force, uses the Force, and debuffs the enemy 4/7 (power 4 → 1).

## GIVEN
CommonSetup: bbk/rrk/{myResources:4;handCardIds:LOF_035}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:POWER:1

---

# LeaderShrink_ForceRefuelViaSage_AssassinShrink_Defeats
#// LOF_035 Talzin's Assassin comboing with LOF_002 Mother Talzin (leader front) + a Force base + a Force
#// unit, in a Talzin mirror. P1 starts with the Force. (1) Mother Talzin's front Action [Exhaust, use the
#// Force] gives the enemy Talzin's Assassin -1/-1 (4/4 -> 3/3), spending P1's Force. (2) P1 attacks P2's
#// base with Secretive Sage (LOF_061, a Force unit), so P1's Crystal Caves Force base (LOF_029: "When a
#// friendly Force unit attacks: the Force is with you") refuels P1's Force token. (3) P1 plays Talzin's
#// Assassin from hand and uses the regained Force to give the enemy Assassin -3/-3 (3/3 -> 0/0), defeating
#// it. The two independent shrinks sum to -4/-4 on the 4/4 enemy. Bases: yellow=Cunning (LOF_029) /
#// red=Aggression (LOF_026).

## GIVEN
P1LeaderBase: LOF_002/LOF_029
P2LeaderBase: LOF_002/LOF_026
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Resources: 4
WithP2Resources: 4:LOF_061:0
WithP1Hand: LOF_035
WithP1GroundArena: LOF_061:1:0
WithP2GroundArena: LOF_035:0:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1NOFORCE
P1RESAVAILABLE:0
