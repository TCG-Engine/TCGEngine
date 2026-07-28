# DealOneAdvantageOnKill
#// ASH_146 Justifier (Space, 4/5) — When Played/On Attack: you may deal 1 to a unit; if it's defeated this
#// way, give an Advantage token to a unit. Deals 1 to a 3/1 Stormtrooper (dies) → Advantage to itself.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_146}
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# Pass_NoDamage
#// ASH_146 Justifier — the deal is optional ("you may"). P1 plays Justifier with an enemy present but passes;
#// no damage and no Advantage.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_146}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# DealNonLethal_NoAdvantage
#// ASH_146 Justifier — the Advantage rider only fires if the unit is DEFEATED by the 1 damage. Dealing 1 to
#// SOR_046 (7 HP) leaves it alive, so no Advantage token is given.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_146}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# OnAttack_DealOne
#// ASH_146 Justifier — the deal-1 also fires On Attack (not just When Played). A seated Justifier attacks
#// P2's base; On Attack it deals 1 to SOR_046 (survives).
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_146:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:4

---

# WhenPlayed_SelfTarget
#// ASH_146 Justifier — the When Played deal-1 may target ITSELF. P1 plays Justifier and deals 1 to itself.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_146}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_146
P1SPACEARENAUNIT:0:DAMAGE:1

---

# WhenPlayed_LeaderTarget
#// ASH_146 Justifier — the When Played deal-1 may target a friendly deployed LEADER unit. P1 has SOR_011
#// deployed; playing Justifier deals 1 to the leader.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_146;myLeader:SOR_011:1:1}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_011
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# OnAttack_SelfTarget
#// ASH_146 Justifier — On Attack the deal-1 may target ITSELF. Seated Justifier attacks P2's base and
#// deals 1 to itself.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_146:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_146
P1SPACEARENAUNIT:0:DAMAGE:1
P2BASEDMG:4

---

# OnAttack_Pass
#// ASH_146 Justifier — the On Attack deal-1 is optional. Seated Justifier attacks P2's base and declines;
#// no damage to any unit (base still takes the 4 combat damage).
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_146:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:PASS
## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:4

---

# OnAttack_LeaderTarget
#// ASH_146 Justifier — On Attack the deal-1 may target a friendly deployed LEADER unit. Seated Justifier
#// attacks P2's base and deals 1 to the deployed SOR_011.
## GIVEN
CommonSetup: rrk/rrk/{myLeader:SOR_011:1:1}
WithP1SpaceArena: ASH_146:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_011
P1GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:4

---

# OnAttack_AdvantageOnKill
#// ASH_146 Justifier — if the On Attack deal-1 DEFEATS the unit, an Advantage token must be given to a
#// unit (mandatory). Seated Justifier attacks P2's base and deals 1 to the enemy SOR_241 Wing Leader (2/1,
#// dies); the Advantage is given to the friendly SOR_066 System Patrol Craft.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_146:1:0
WithP1SpaceArena: SOR_066:1:0
WithP2SpaceArena: SOR_241:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:mySpaceArena-1
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:1:CARDID:SOR_066
P1SPACEARENAUNIT:1:ADVANTAGECOUNT:1
P2BASEDMG:4

---

# OnAttack_AdvantageWorksForAttack
#// ASH_146 Justifier — an Advantage given to the ATTACKER mid-attack boosts this same attack (+1) and is
#// then consumed. Justifier attacks P2's base, deals 1 to the enemy SOR_241 (dies), takes the Advantage
#// itself → base takes 5 (4 + 1) and the Advantage is spent (0 left on Justifier).
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_146:1:0
WithP2SpaceArena: SOR_241:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:5
P1SPACEARENAUNIT:0:CARDID:ASH_146
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
