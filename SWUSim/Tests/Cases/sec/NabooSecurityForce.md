# DeclineDisclose_NoSentinel
#// SEC_120 Naboo Security Force — decline the When Played disclose → no Sentinel granted.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_120
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1NODECISION

---

# WhenPlayed_Disclose_GiveSentinel
#// SEC_120 Naboo Security Force (Ground, 5/7, Command) — When Played/When Defeated: you may disclose
#//   Command → give a friendly unit Sentinel for this phase.
#// A friendly SOR_095 sits in play. Play SEC_120 → disclose SEC_080 (Command) → choose the friendly
#// SOR_095 → it gains Sentinel this phase.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_120
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1NODECISION

---

# WhenPlayed_Sentinel_ForcesEnemyOffBase
#// SEC_120 Naboo Security Force — after disclosing Command and granting Sentinel to a friendly unit, the
#// Sentinel keyword forces the opponent off the base: with SOR_095 holding Sentinel, P2's SOR_164 Wampa
#// (4 power) cannot attack P1's base — the declared base attack is rejected (base takes 0) and the Wampa
#// stays ready. (Without the Sentinel the base would have taken 4.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_120
WithP1Hand: SEC_080
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenDefeatedByCombat_Disclose_GiveSentinel
#// SEC_120 Naboo Security Force — the When Defeated half also fires. Naboo (5/7) attacks a 7/7 and is
#//   defeated by the counter; its When Defeated then offers the Command disclose. Disclosing SOR_095 and
#//   choosing the surviving friendly (Consular Security Force) grants it Sentinel this phase.

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1GroundArena: [SEC_120:1:0 SOR_046:1:0]
WithP2GroundArena: ASH_131:1:0
WithP1Hand: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
