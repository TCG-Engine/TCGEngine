# RaidPassive_UniqueWhileInitiative
#// SEC_155 Alexsandr Kallus — "While you have the initiative, each OTHER friendly unique unit gains Raid 2."
#//   With Kallus in play and P1 holding initiative, the unique SEC_065 attacks the base for 4 + Raid 2 = 6.

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SEC_155:1:0
WithP1GroundArena: SEC_065:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:6
P1NODECISION

---

# WhenPlayed_Deal2EachOf3
#// SEC_155 Alexsandr Kallus (Unit, cost 7) — When Played: deal 2 to each of up to 3 ground units.

## GIVEN
CommonSetup: rrw/rrk/{myResources:7}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_155

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:2:DAMAGE:2
P1NODECISION
