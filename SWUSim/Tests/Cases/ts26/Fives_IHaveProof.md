# CopiesAnotherWhenPlayed
#// TS26_34 Fives (Unit 6/6, cost 6) — Sentinel + "You may have this unit enter play with the When Played
#// abilities of another unit in play." Copying the Assault Lander LAAT's When Played (create 2 Clone
#// Troopers) makes Fives create 2 Clones on entry → ground goes from 1 (LAAT) to 4 (LAAT + Fives + 2).
## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
WithP1GroundArena: TS26_23:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:2:CARDID:TS26_T02

---

# DeclineCopy
#// TS26_34 Fives — the copy is optional ("you may"). Declining copies nothing, so only Fives enters play
#// alongside the LAAT (ground count 2).
## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
WithP1GroundArena: TS26_23:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:2
