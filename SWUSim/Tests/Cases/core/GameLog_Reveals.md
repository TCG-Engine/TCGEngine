# RevealTheTopN_NamesEveryCardPublicly
#// USER DECISION 2026-09-11 (gamelog-updates #4): a peek whose card says "REVEAL the top N" showed every
#// card to the table, so the line names them all publicly — not a count plus a private "You saw".
#// IBH_009 I've Found Them: "Reveal the top 3 cards of your deck. Draw a unit revealed this way, then
#// discard the other revealed cards." (Fixture from ibh/IveFoundThem.md.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_009
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P2LOGSEES:P1 revealed the top 3 cards of their deck: [[SOR_095|Battlefield Marine]], [[SOR_171|Mission Briefing]], [[SOR_171|Mission Briefing]] ([[IBH_009|I've Found Them]])
LOGCOUNT:0:You saw

---

# HandReveal_ViaDoRevealCard_IsLogged
#// DoRevealCard only set a one-request flash message; a reveal that relied on it alone never reached the
#// log. SOR_176 ISB Agent: "When Played: You may reveal an event from your hand. If you do, deal 1 damage
#// to an enemy unit." (Fixture from sor/IsbAgent.md.)

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_176,SOR_172}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2LOGSEES:P1's [[SOR_176|ISB Agent]] revealed [[SOR_172|Open Fire]] from P1's hand
