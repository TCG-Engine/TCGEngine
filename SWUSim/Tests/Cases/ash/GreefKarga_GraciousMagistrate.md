# Decline_NoAdvantage
#// ASH_017 Greef Karga — declining the optional exhaust gives no Advantage and leaves Greef ready. P1 plays
#// SOR_095 and declines.
## GIVEN
CommonSetup: gyw/brk/{
  myLeader:ASH_017
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1LEADER:READY

---

# PlayUnit_Advantage
#// ASH_017 Greef Karga — "When you play or create a unit: you may exhaust this leader; if you do, give an
#// Advantage token to that unit." P1 plays SOR_095 and exhausts Greef to give it an Advantage token.
## GIVEN
CommonSetup: gyw/brk/{
  myLeader:ASH_017
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1LEADER:EXHAUSTED

---

# ExhaustedLeader_NoPromptForNextUnit
#// ASH_017 Greef Karga — using the ability exhausts Greef, so a SECOND unit played the same phase gets no
#// prompt. P1 plays SOR_095 and exhausts Greef (Advantage on it); the next unit SOR_063 enters with no
#// Advantage and no prompt because Greef is already exhausted.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017;handCardIds:SOR_095,SOR_063}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_063
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
P1LEADER:EXHAUSTED
