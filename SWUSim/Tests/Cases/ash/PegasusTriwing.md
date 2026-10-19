# Decline_StaysExhausted
#// ASH_171 Pegasus Tri-Wing — declining the optional upgrade defeat means the Pegasus is NOT readied (it
#// stays exhausted from being just played) and SOR_095 keeps SOR_120 (5 power).
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_171}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1SPACEARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:5

---

# WhenPlayed_DefeatUpgradeReady
#// ASH_171 Pegasus Tri-Wing (Space, 3/2, cost 3) — When Played: you may defeat a friendly upgrade; if you
#// do, ready this unit. Pegasus enters exhausted; defeating SOR_120 off SOR_095 readies the Pegasus (and
#// SOR_095 reverts to 3 power).
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_171}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:POWER:3

---

# MyUpgradeOnAnENEMYUnit_IsFriendly_Readies
#// ASH_171 — "You may defeat a friendly upgrade. If you do, ready this unit." P1's LOF_215 Ascension Cable on
#// P2's SOR_046 is P1's upgrade (CR), so Pegasus may defeat it and readies.
## GIVEN
CommonSetup: rrk/brk/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [LOF_215 ASH_171]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_171
P1SPACEARENAUNIT:0:READY
