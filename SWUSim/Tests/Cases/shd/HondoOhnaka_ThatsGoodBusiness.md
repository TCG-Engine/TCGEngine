# Front_Decline
#// SHD_005 Hondo Ohnaka (front) — declining the "may" leaves Hondo ready and grants no Experience token.
#// COVERAGE: offer=N/A (the Experience target pick is answered directly; the pool is every unit in play
#//           and is not narrowed by the ability) · decline=Front_Decline · control=N/A (the trigger is
#//           gated on "when YOU play a card using Smuggle" — it is seat-scoped by construction and no
#//           object changes controller) · boundary=Front_SmugglePlayGivesExp (accept: leader exhausted,
#//           token given) vs Front_Decline (decline: leader ready, no token) · reqboundary=
#//           Front_SmugglePlayGivesExp (the leader's exhaust state is read after the YES is answered)
#//           NOTE: the DEPLOYED side ("When you play a card using Smuggle: You may give an Experience
#//           token to a unit", no exhaust cost) has no coverage here — it does not currently fire.

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_005}
P1OnlyActions: true
WithP1Resources: 1:SHD_065:0,10:SOR_095:1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:-

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3

---

# Front_SmugglePlayGivesExp
#// SHD_005 Hondo Ohnaka (front, undeployed) — "When you play a card using Smuggle: You may exhaust this
#// leader. If you do, give an Experience token to a unit." P1 plays SHD_065 from resources via Smuggle,
#// accepts (exhausting Hondo), and gives an Experience token (+1/+1) to its SOR_046 (3/7 → 4 power).

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_005}
P1OnlyActions: true
WithP1Resources: 1:SHD_065:0,10:SOR_095:1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:4
