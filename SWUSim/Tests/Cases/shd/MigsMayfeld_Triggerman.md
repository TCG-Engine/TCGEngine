# MigsMayfeld_HandDiscard_Deal2
#// SHD_163 Migs Mayfeld — "When a player discards a card from their hand: You may deal 2 damage to a unit
#// or base. Once each round." P1 plays SHD_244 (No Bargain), forcing P2 to discard its only card; Migs
#// then deals 2 to the enemy SOR_046.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_163:1:0
WithP1Hand: SHD_244
WithP1Deck: SOR_095
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2HANDCOUNT:0

---

# PlayingAnEventDoesNotTriggerHim
#// SHD_163 — "When a player DISCARDS a card from their hand". Playing an event sends it to the discard
#// pile but is not a discard, so Migs must not offer his 2 damage. P1 plays Urgent Mission (which draws
#// and deals 2 to P1's own base) with Migs on board: no decision is raised and the enemy unit is untouched.
#// Third card sharing one root cause with LAW_076 and LAW_179 — the discard funnel's from='HAND' block
#// fired on an event's own play. Migs is the reactive member of that trio, so his guard is the one that
#// proves no stray PROMPT appears rather than just a wrong number.

## GIVEN
CommonSetup: rrk/yyw/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_64
WithP1GroundArena: SHD_163:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:2
