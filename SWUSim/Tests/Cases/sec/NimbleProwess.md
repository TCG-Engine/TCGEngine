# WhenPlayed_AllExhausted_AutoPass
#// SEC_069 Nimble Prowess — When Played "you may exhaust a unit in attached unit's arena" must AUTO-PASS
#//   when there is no READY unit to exhaust (only ready units can be exhausted). Host is attached to an
#//   already-exhausted unit and the only other arena unit is also exhausted, so no prompt should fire.
#//   Repro of game 2619: Lama Su plays Nimble Prowess on an exhausted unit, offering a meaningless prompt.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_041:0:0
WithP2GroundArena: SOR_046:0:0
WithP1Hand: SEC_069

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# WhenPlayed_ExhaustInArena
#// SEC_069 Nimble Prowess (upgrade, +1/+1) — Attach to a friendly unit. When Played: you may exhaust a
#//   unit in attached unit's arena. P1 attaches it to SEC_041 (ground) and exhausts the enemy SOR_046.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# WhenPlayed_ExhaustInArena_Space
#// SEC_069 Nimble Prowess — space-arena variant. P1 attaches it to a friendly SPACE unit (SOR_141), then
#//   the When Played "exhaust a unit in attached unit's arena" offers the SPACE arena, so P1 exhausts the
#//   enemy space unit (SOR_141). Proves the arena of the exhaust follows the attached unit's arena.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP2SpaceArena: SOR_141:1:0
WithP1Hand: SEC_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:UPGRADECOUNT:1

---

# PlayedFromOpponentDiscardViaAFineAddition_WhenPlayedStillFires
#// SEC_069 Nimble Prowess — the When Played must fire no matter which zone the upgrade was played from.
#// P1 kills SOR_128 to switch on TWI_040 A Fine Addition, then uses it to play SEC_069 out of P2's
#// DISCARD onto P1's ready SOR_095. Nimble Prowess still resolves: P1 exhausts P2's SOR_164 in the
#// attached unit's arena.
#// Guards the play-from-discard dispatch path, which reaches the same event through different code than
#// a play from hand and can silently skip the ability.

## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040;theirDiscardCardIds:SEC_069}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:theirDiscard-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SEC_069
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
