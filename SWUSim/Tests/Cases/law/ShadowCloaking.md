# ReadyAndShield
#// LAW_043 Shadow Cloaking (Vigilance,Aggression,Villainy event, cost 5) — "Ready a unit and give a
#// Shield token to it." One exhausted friendly unit on board -> single target auto-resolves: it readies
#// and gains a Shield. (rrk covers Aggression+Villainy; Vigilance pip is off-aspect +2 -> budget 7.)

## GIVEN
CommonSetup: rrk/bgw/{myResources:7}
WithP1GroundArena: SEC_080:0:0
WithP1Hand: LAW_043

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1

---

# ReadyFriendlySpaceUnitShield
#// LAW_043 Shadow Cloaking — with four legal targets the player chooses one. Target the friendly
#// exhausted space unit (SOR_237); it readies and gains a Shield.

## GIVEN
CommonSetup: rrk/bgw/{myResources:7}
WithP1GroundArena: SOR_095:0:0
WithP1SpaceArena: SOR_237:0:0
WithP2GroundArena: SOR_164:0:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_043

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:READY
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1

---

# ReadyEnemyUnitShield
#// LAW_043 Shadow Cloaking — the effect can target ANY unit, including an enemy one. Target the enemy
#// exhausted Wampa (SOR_164); it readies and gains a Shield.

## GIVEN
CommonSetup: rrk/bgw/{myResources:7}
WithP1GroundArena: SOR_095:0:0
WithP1SpaceArena: SOR_237:0:0
WithP2GroundArena: SOR_164:0:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_043

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1

---

# ShieldAlreadyReadyUnitNoReready
#// LAW_043 Shadow Cloaking — targeting an already-ready unit still grants a Shield; readying a ready
#// unit is a no-op, so it simply stays ready. Target the enemy ready space unit (theirSpaceArena-0).

## GIVEN
CommonSetup: rrk/bgw/{myResources:7}
WithP1GroundArena: SOR_095:0:0
WithP1SpaceArena: SOR_237:0:0
WithP2GroundArena: SOR_164:0:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_043

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:READY
P2SPACEARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1

---

# NoUnitsNoEffectStillPlays
#// LAW_043 Shadow Cloaking — with no units in play there is nothing to ready/shield; the event still
#// resolves with no effect and goes to the discard pile.

## GIVEN
CommonSetup: rrk/bgw/{myResources:7}
WithP1Hand: LAW_043

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1NODECISION
