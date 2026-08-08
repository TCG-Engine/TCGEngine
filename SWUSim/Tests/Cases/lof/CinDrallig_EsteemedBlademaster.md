# FreeLightsaberReady
#// LOF_150 Cin Drallig — When Played: you may play a Lightsaber upgrade from hand for free on him; if you do,
#// ready him. P1 plays Cin Drallig (5/6), attaches SOR_054 (Jedi Lightsaber +3/+3) for free → 8/9, and he is
#// readied (he entered exhausted from the play).

## GIVEN
CommonSetup: rrw/ggk/{myResources:8;handCardIds:LOF_150,SOR_054}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_150
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HP:9
P1GROUNDARENAUNIT:0:READY

---

# DeclineLightsaber_StaysExhausted
#// LOF_150 Cin Drallig — playing the free Lightsaber is optional. With a Lightsaber (SOR_054) in hand P1
#// declines: no upgrade is attached and, because the "if you do, ready him" clause never fires, Cin Drallig
#// stays exhausted from being played. Intended: "allows the player to choose not to play a Lightsaber".

## GIVEN
CommonSetup: rrw/ggk/{myResources:8;handCardIds:LOF_150,SOR_054}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_150
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# NoLightsaber_NoTrigger
#// LOF_150 Cin Drallig — with no Lightsaber in hand the ability has no legal target, so it does not trigger:
#// Cin Drallig enters exhausted with no upgrade and P1's turn ends. Intended: "has no effect if no
#// Lightsaber is in hand".

## GIVEN
CommonSetup: rrw/ggk/{myResources:8;handCardIds:LOF_150}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_150
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED
