# WhenPlayed_Decline
#// SHD_050 Chewbacca — declining the optional defeat leaves the board untouched.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_050
WithP2GroundArena: LAW_124:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenPlayed_DefeatLowRemainingHP
#// SHD_050 Chewbacca (8-cost 4/10, Grit) — "When Played: You may defeat a unit with 5 or less
#// REMAINING HP." P2 has an undamaged Consular (remaining 7 — NOT eligible) and a 2-damaged
#// Industrious Team (7−2 = 5 — eligible). P1 plays Chewbacca (Heroism+Vigilance covered by the bw
#// leader) and defeats the damaged one. Remaining-HP (not printed-HP) is what qualifies it.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_050
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
