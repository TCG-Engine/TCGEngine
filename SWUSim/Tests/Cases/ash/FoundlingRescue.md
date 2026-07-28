# Event_DefeatLowHpAndToken
#// ASH_092 Foundling Rescue (Event) — you may defeat a unit with 2 or less remaining HP; create a
#// Mandalorian token. P1 defeats the 3/1 Stormtrooper and gets a Mandalorian token.
## GIVEN
CommonSetup: brk/rrk/{myResources:4;handCardIds:ASH_092}
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01

---

# DeclineDefeat_StillCreatesToken
#// ASH_092 Foundling Rescue — the defeat is optional ("you may"), but the Mandalorian token is created
#// unconditionally. P1 declines to defeat SOR_128, yet still gets a Mandalorian token.
## GIVEN
CommonSetup: brk/rrk/{myResources:4;handCardIds:ASH_092}
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01

---

# DefeatFriendlyUnit
#// ASH_092 Foundling Rescue — the defeat may target a FRIENDLY unit too. P1's own SOR_095 (3/3) has
#// 2 damage (1 remaining HP), so it is a legal target; P1 defeats it and still gets a Mandalorian token.
## GIVEN
CommonSetup: brk/rrk/{myResources:4;handCardIds:ASH_092}
WithP1GroundArena: SOR_095:1:2
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P1DISCARDCOUNT:2

---

# DefeatEnemyLeaderUnit
#// ASH_092 Foundling Rescue — a deployed enemy LEADER unit with 2 or less remaining HP is a legal
#// target. P2's Darth Vader leader unit (5/8) has 7 damage (1 remaining HP); defeating it returns the
#// leader to its undeployed side, and P1 still creates a Mandalorian token.
## GIVEN
CommonSetup: brk/rrk/{myResources:4;handCardIds:ASH_092;theirLeader:SOR_010:1:1:0:7}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2LEADER:NOTDEPLOYED
P1GROUNDARENAUNIT:0:CARDID:ASH_T01

---

# DefeatFriendlyLeaderUnit
#// ASH_092 Foundling Rescue — a deployed friendly LEADER unit with 2 or less remaining HP can be the
#// defeat target. P1's Darth Vader leader unit (5/8) has 7 damage (1 remaining HP); defeating it returns
#// the leader to its undeployed side, and the Mandalorian token is still created.
## GIVEN
CommonSetup: brk/rrk/{myResources:4;handCardIds:ASH_092;myLeader:SOR_010:1:1:0:7}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENAUNIT:0:CARDID:ASH_T01

---

# NothingToDefeat_StillCreatesToken
#// ASH_092 Foundling Rescue — with no unit at 2 or less remaining HP, there is nothing to defeat, but the
#// Mandalorian token is still created unconditionally. P1's only unit (SOR_095, 3/3, undamaged) is not a
#// legal target, so the ability resolves straight to the token with no defeat prompt.
## GIVEN
CommonSetup: brk/rrk/{myResources:4;handCardIds:ASH_092}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
