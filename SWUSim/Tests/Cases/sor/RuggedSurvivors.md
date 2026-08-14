# OnAttack_ControlsLeaderUnit_DrawsWhenYes
#// COVERAGE: offer=N/A (the draw is a YESNO, no target pool) · decline=OnAttack_ControlsLeaderUnit_
#//           DeclineNoDraw · boundary=OnAttack_NoLeaderUnit_NoOp (condition gate) + PilotLeaderUnit_
#//           EnablesDraw (derived leader unit counts; plain-pilot-attach negative guarded at the
#//           shared helper by SteadfastBattalion::PoePlainAttach_NotALeaderUnit_NoBuff)
#//           · control=N/A ("you control a leader unit" is evaluated live for the attacker's seat;
#//           no cross-control interaction in the text) · reqboundary=OnAttack_ControlsLeaderUnit_
#//           DrawsWhenYes (the YESNO pends at the request boundary before the draw resolves)
#// SOR_067 Rugged Survivors (Ground, 3/5, Vigilance, cost 5, Grit) — On Attack: if you control a leader
#//   unit, you may draw a card. Leader deployed (controls a leader unit) + attack the base + answer YES
#//   → draw 1 (hand 0→1, deck 1→0). Base takes the attacker's 3 power.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SOR_010:1:1:1; myBase:SOR_022; theirBase:SOR_022}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_067:1:0
WithP1Deck: SOR_063

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P2BASEDMG:3

---

# OnAttack_ControlsLeaderUnit_DeclineNoDraw
#// "you may" → declining draws nothing (hand stays 0, deck stays 1). The attack still resolves (base
#//   takes 3).

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SOR_010:1:1:1; myBase:SOR_022; theirBase:SOR_022}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_067:1:0
WithP1Deck: SOR_063

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P2BASEDMG:3

---

# OnAttack_NoLeaderUnit_NoOp
#// No leader unit (leader undeployed) → the condition fails → no draw prompt at all. Attack still hits
#//   the base for 3; no decision pending.

## GIVEN
P1LeaderBase: SOR_010/SOR_022
P2LeaderBase: SOR_010/SOR_022
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_067:1:0
WithP1Deck: SOR_063

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P2BASEDMG:3
P1NODECISION

---

# PilotLeaderUnit_EnablesDraw
#// SOR_067 Rugged Survivors — "you control a leader unit" also holds when the leader unit is
#// DERIVED: JTL_008 Wedge deployed as a Pilot on the AT-ST makes the HOST a leader unit, with no
#// standalone deployed leader. The draw offer fires; YES draws 1. Base takes the printed 3.
#// (The plain-pilot-attach NEGATIVE — a Poe-style attach that does not make the host a leader
#// unit — is guarded at the shared helper by SteadfastBattalion::PoePlainAttach_NotALeaderUnit_NoBuff.)

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:JTL_008;
  myLeaderDeployedPilot:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1GroundArena: SOR_067:1:0
WithP1Deck: SOR_063

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1HANDCOUNT:1
P1DECKCOUNT:0
P2BASEDMG:3
