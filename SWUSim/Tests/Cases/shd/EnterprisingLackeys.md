# WhenDefeated_Decline
#// SHD_107 Enterprising Lackeys — declining the "may" leaves it in the discard and the resources
#// untouched.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SHD_107:1:1
WithP1Resources: 2:SOR_046:1
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P1RESCOUNT:2
P1RESAVAILABLE:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_107

---

# WhenDefeated_ResourceSwap
#// SHD_107 Enterprising Lackeys (4-cost 5/5) — "When Defeated: You may defeat a friendly resource.
#// If you do, put this unit into play as a resource." P1's 1-damaged Lackeys attacks a Wampa
#// (SOR_164, 4/5): mutual kill (5 ≥ 5; counter 4 → 1+4 = 5); the When Defeated resolves inline
#// (attacker self-death). P1 picks a resource to defeat → Lackeys leaves the discard and becomes a
#// resource (exhausted, no "ready it" wording). Net: still 2 resources (1 ready survivor +
#// exhausted Lackeys), the defeated SOR_046 in discard.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SHD_107:1:1
WithP1Resources: 2:SOR_046:1
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myResources-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P1RESCOUNT:2
P1RESAVAILABLE:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_046
