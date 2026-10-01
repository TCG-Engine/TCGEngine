# WhenPlayed_ResolvesOnTheUnitThatWasPlayed_AfterTheArenaCompacts
#// BUG REPORT #1110 (game 1438045): SEC_193 Thrawn's When Played "readied P1's Mos Espa Watermonger".
#// A When Played trigger was bagged with an INDEX (myGroundArena-N) and dispatched with it unchecked. Between
#// bagging and dispatch the arena compacted under it and a NEW unit refilled slot N, so "this unit" became
#// that unit. Same defect as case 'Shielded' (#1091) and case 'Ambush' — see the memory note
#// bagged-trigger-mzid-refilled-by-compaction.
#// Reachable without Endless Legions: HMW_043 Darth Vader plays SEC_132 Imperial Occupier (2/2) and then
#// TWI_059 Royal Guard Attaché (2/5, "When Played: Deal 2 damage to this unit"), and deals 2 to each BEFORE
#// their triggers flush. The Occupier dies, the Attaché slides from index 2 to 1, and the Occupier's When
#// Defeated Spy token is appended into index 2 — the slot the Attaché's When Played still points at.
#// Correct: the Attaché takes its own 2 (2 + 2 = 4) and the Spy token is untouched.
## GIVEN
CommonSetup: rgk/rgk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_043
WithP1Deck: [SEC_132 TWI_059 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_132,TWI_059
## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:HMW_043
P1GROUNDARENAUNIT:1:CARDID:TWI_059
P1GROUNDARENAUNIT:1:DAMAGE:4
P1GROUNDARENAUNIT:2:CARDID:SEC_T01
P1GROUNDARENAUNIT:2:DAMAGE:0
P1NODECISION
