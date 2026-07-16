# SmugglePlay_StealsResource
#// SHD_213 DJ (3-cost 3/5, Smuggle 7 [Cunning Cunning]) — "When played using Smuggle: Take control
#// of an enemy resource." P1 smuggles DJ (both Cunning pips covered by base y + leader yw) and picks
#// one of P2's two exhausted resources: P1 ends with 9 resources (7 + deck replacement + stolen),
#// P2 with 1.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1Resources: 7:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:0
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:7
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_213
P1RESCOUNT:9
P2RESCOUNT:1

---

# StolenResource_RevertsWhenDJLeaves
#// SHD_213 DJ — "When this unit leaves play, that resource's owner takes control of it." After the
#// steal, P2's AT-AT Suppressor (SOR_039, 8/8) defeats DJ (3/5): the lazy leave-play sweep returns
#// the stolen resource to P2. P1 back to 8 resources, P2 back to 2.

## GIVEN
CommonSetup: yyw/yyw
WithActivePlayer: 1
WithP1Resources: 7:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:0
WithP1Deck: SOR_095
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>SmuggleResource:7
- P1>AnswerDecision:theirResources-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:8
P2RESCOUNT:2
