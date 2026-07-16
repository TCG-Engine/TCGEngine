# NonSentinel_GrantsSentinel
#// SHD_103 General Rieekan (6-cost, Command/Heroism) — "When Played/On Attack: Choose a friendly unit. If it
#// has Sentinel, give it an Experience token. Otherwise, it gains Sentinel for this phase." Choosing the
#// non-Sentinel SOR_095 grants it Sentinel.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_103
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Sentinel_GivesExp
#// SHD_103 General Rieekan — choosing a unit that ALREADY has Sentinel (SOR_063) gives it an Experience
#// token instead of granting Sentinel.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_103
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
