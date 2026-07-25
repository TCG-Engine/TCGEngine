# EachBounceThenDefeatAll
#// LAW_096 Rhydonium Detonation (Cunning,Vigilance event, cost 7) — "Each player may return a non-leader
#// unit to its owner's hand. Then, defeat all non-leader units." P1 saves SEC_080, P2 saves SOR_095;
#// the remaining non-leader (P2's SOR_237) is defeated.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1HANDCOUNT:1
P2HANDCOUNT:1
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1

---

# OnlyP1SavesWhenP2Passes
#// LAW_096 — only P1 saves a unit; P2 declines. P1 returns SEC_080 to hand; P2 passes, so its SOR_095
#// (ground) and SOR_237 (space) are both defeated by the mass defeat.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1HANDCOUNT:1
P2HANDCOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:2

---

# OnlyP2SavesWhenP1Passes
#// LAW_096 — P1 declines; only P2 saves. P2 returns its own SOR_095 to hand; P1's SEC_080 and P2's SOR_237
#// are defeated.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:1
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1

---

# DefeatAllWhenBothPass
#// LAW_096 — both players decline to save; the mass defeat removes every non-leader unit.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P2>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:2

---

# P1SavesAnOpponentUnit
#// LAW_096 — the caster may return ANY non-leader unit, including an enemy's. P1 saves P2's SOR_095; P2
#// then saves its own SOR_046. Both returned units go to P2's hand; P1's own SEC_080 is defeated.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:2
P1DISCARDCOUNT:2
P2DISCARDCOUNT:0

---

# NoEffectWhenNoNonLeaders
#// LAW_096 — with no non-leader units in play, the event resolves with no effect and simply goes to the
#// discard pile.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# BothMaySaveTheOnlyUnit
#// LAW_096 — the only non-leader in play is P1's SEC_080. P1 returns it to hand; nothing remains for the
#// mass defeat, so it survives in P1's hand.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# DefeatTheOnlyUnitWhenBothPass
#// LAW_096 — the only non-leader is P1's SEC_080. Both players decline, so it is defeated by the mass defeat.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P2>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:2
