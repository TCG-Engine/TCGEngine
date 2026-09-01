# OnAttack_ControlsLeaderUnit_DrawsWhenYes
#// COVERAGE: offer=N/A (the draw is a YESNO, no target pool) · decline=OnAttack_ControlsLeaderUnit_
#//           DeclineNoDraw · boundary=OnAttack_NoLeaderUnit_NoOp (condition gate) + PilotLeaderUnit_
#//           EnablesDraw (derived leader unit counts; plain-pilot-attach negative guarded at the
#//           shared helper by SteadfastBattalion::PoePlainAttach_NotALeaderUnit_NoBuff)
#//           + Grit_ScalesWithDamage_PowerOnly (0 damage vs 4 damage in one board, and +1/+0 not
#//           +1/+1) · control=StolenSurvivors_DrawsFromTheControllersDeck (SUPERSEDES the former
#//           "N/A — no cross-control interaction": the text has TWO controller-scoped reads, and
#//           the second one is a zone. "Draw a card" names the CONTROLLER's deck, so a Survivors
#//           owned by P2 and controlled by P1 must check P1's board for a leader unit and draw off
#//           P1's deck — an owner-framed resolution would move a card into P2's hand)
#//           · reqboundary=OnAttack_ControlsLeaderUnit_
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

---

# Grit_ScalesWithDamage_PowerOnly
#// SOR_067 Rugged Survivors — the FIRST clause, Grit: "This unit gets +1/+0 for each damage on it."
#// Two copies side by side make the scale and its shape visible at once: the undamaged one reads its
#// printed 3/5, the one carrying 4 damage reads 7/5. The HP half is the load-bearing negative — Grit
#// is +1/+0, so the 4 damage buys 4 power and NOT a point of HP (a +1/+1 reading would show 9 HP and
#// make the unit unkillable). N vs N±1 discrimination in one board: 0 damage → +0, 4 damage → +4.

## GIVEN
CommonSetup: bbk/bbk/{myBase:SOR_022; theirBase:SOR_022}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_067:1:0    # undamaged — idx 0
WithP1GroundArena: SOR_067:1:4    # 4 damage — idx 1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:POWER:7
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:1:DAMAGE:4

---

# Grit_BoostedPowerIsDealtInCombat
#// Intended: the Grit bonus is real power, not a display value — a Rugged Survivors carrying 2
#// damage deals 5 to the enemy base, not its printed 3. Control for the amount:
#// OnAttack_NoLeaderUnit_NoOp is the same attack from an undamaged copy and puts 3 on the base.
#// The leader is left undeployed so the On Attack draw does not fire and no decision is pending.

## GIVEN
P1LeaderBase: SOR_010/SOR_022
P2LeaderBase: SOR_010/SOR_022
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_067:1:2
WithP1Deck: SOR_063

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P2BASEDMG:5
P1NODECISION
P1DECKCOUNT:1

---

# StolenSurvivors_DrawsFromTheControllersDeck
#// Intended: "you control a leader unit" and "draw a card" both resolve for the CONTROLLER, so a
#// Rugged Survivors owned by P2 but controlled by P1 (the end state after a take-control effect)
#// checks P1's board for a leader unit and draws off P1's deck. P1 has a deployed leader; the stolen
#// Survivors attacks P2's base, P1 answers YES, and P1's deck 1 → 0 with P1's hand 0 → 1 while P2's
#// seeded deck and empty hand are untouched. Resolving the draw against the OWNER's deck instead
#// would leave P1's deck at 1 and move a card into P2's hand.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SOR_010:1:1:1; myBase:SOR_022; theirBase:SOR_022}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_067:2
WithP1Deck: SOR_063
WithP2Deck: SOR_063

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P2HANDCOUNT:0
P2DECKCOUNT:1
P2BASEDMG:3
