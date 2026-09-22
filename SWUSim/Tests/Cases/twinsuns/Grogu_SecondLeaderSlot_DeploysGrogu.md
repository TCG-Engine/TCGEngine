# GroguInLeaderSlot2_YesDeploysGrogu_NotLeader1
#// Player report (Twin Suns, 2026-09-21): "the Grogu leader did not properly flip when I played a unit that cost 4
#// or more and it flipped my Bail Organa leader when I hit the yes button."
#// ASH_018 Grogu — "When you play a unique unit that costs 4 or more: if this leader is ready, you may deploy him."
#// The trigger found Grogu in EITHER leader slot (by CardID), but its YES handler deployed leader index 0 —
#// hard-coded — so with Grogu in the SECOND slot it deployed whichever leader sat in the first (here Bail Organa).
#// The trigger now carries Grogu's live leader index to the handler.
## GIVEN
CommonSetup: gyw/brk/{myLeader:SEC_008; myLeader2:ASH_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_242
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1LEADER0DEPLOYED:false
P1LEADER1DEPLOYED:true
P1GROUNDARENACOUNT:2
P1NODECISION

---

# GroguInLeaderSlot1_StillDeploysGrogu
#// Control: Grogu in the FIRST slot (the only case the hard-coded 0 happened to get right) keeps working.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_018; myLeader2:SEC_008}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_242
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1LEADER0DEPLOYED:true
P1LEADER1DEPLOYED:false
P1GROUNDARENACOUNT:2
P1NODECISION
