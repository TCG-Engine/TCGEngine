# Rampart_EnemyDefeatsYourBaseUpgrade_YouAreOffered
#// SSOT #6 (gamelog-updates, 2026-09-11) — SWUObjSeat. A BASE, a LEADER-zone object and a DISCARD / HAND / DECK
#// card carry no Owner / Controller, so `intval($o->Controller ?? $player)` quietly answered "the ACTING
#// player". A sweep of all 172 such fallbacks found two that name the wrong seat even at two seats.
#// (1) HMW_060 Vice Admiral Rampart: "If an upgrade on YOUR base would be defeated, you may defeat this unit
#// instead." SWUDefeatUpgrade asked whether the DEFEATING player controls Rampart. Every existing section has
#// the base's controller defeat its own upgrade; here P2 plays SOR_251 Confiscate ("Defeat an upgrade") on
#// P1's HMW_081 Alliance Shield Generator. P1 controls Rampart, so P1 is offered the swap: Rampart is
#// defeated and the generator stays.

## GIVEN
CommonSetup: bbk/rrk/{theirResources:6;theirhandCardIds:SOR_251}
WithActivePlayer: 2
WithP1BaseUpgrade: HMW_081
WithP1GroundArena: HMW_060:1:0

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1BASE:UPGRADECOUNT:1
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# AFineAddition_UpgradeFromOpponentsDiscard_StaysTheirs
#// (2) TWI_040 A Fine Addition: "play an upgrade from your hand or from ANY player's discard pile". A card
#// played from an opponent's discard is still THEIRS (it returns to their discard when it leaves play). The
#// handler read `$srcObj->Owner ?? $player` off a DISCARD object — which has no Owner — so it always took the
#// caster, and its own "fix ownership" block never ran. P1 attaches P2's SOR_120 to SOR_046, then Confiscates
#// it: it goes to P2's discard (SOR_128, killed by the attack, is already there → 2), not P1's.
#// (Fixture from twi/AFineAddition.md UpgradeFromOpponentDiscard.)

## GIVEN
CommonSetup: brk/bbw/{myResources:12;handCardIds:TWI_040;theirDiscardCardIds:SOR_120}
P1OnlyActions: true
WithP1Hand: SOR_251
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:theirDiscard-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:2
P1DISCARDCOUNT:2
