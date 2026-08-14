# WhenDefeated_InitiativeDraws2
#// COVERAGE: offer=N/A (no target choice — a mandatory conditional draw) · reqboundary=
#//           WhenDefeated_InitiativeDraws2 (the defeat resolves in the combat drain across the
#//           attack request) · control=ControlTakenAndDefeated_NewControllerDraws ("you" follows
#//           the controller at defeat, not the owner) · boundary pair=
#//           WhenDefeated_InitiativeDraws2 vs WhenDefeated_NoInitiative_NoDraw (the initiative
#//           gate) · decline=N/A (not a "you may").
#// SOR_163 Star Wing Scout (4/1, Space) — When Defeated: If you have the initiative, draw 2
#// cards. P1 holds the initiative. The Scout attacks the Gladiator Star Destroyer (5/6): it
#// deals 4 (Gladiator survives) and takes 5 (1 HP → defeated). Because P1 has the initiative,
#// its When Defeated draws 2 (hand 0 → 2, deck −2).

## GIVEN
CommonSetup: ggw/ggw
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1SpaceArena: SOR_163:1:0     # Star Wing Scout (ready) — attacker, dies
WithP2SpaceArena: SOR_086:1:0     # Gladiator (5/6) — kills it back, survives
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:2
P1DECKCOUNT:0

---

# WhenDefeated_NoInitiative_NoDraw
#// SOR_163 Star Wing Scout — the draw is gated on holding the initiative. Here P2 holds it
#// (P1OnlyActions), so when the Scout is defeated in combat P1 draws nothing. Absence guard.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_163:1:0     # attacker, dies
WithP2SpaceArena: SOR_086:1:0     # Gladiator (5/6) — kills it back
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# ControlTakenAndDefeated_NewControllerDraws
#// SOR_163 Star Wing Scout — "If YOU have the initiative" reads the unit's CONTROLLER at defeat
#// time. P2 (holding claimed initiative) plays JTL_043 No Glory, Only Results on P1's Scout:
#// take control, then defeat it. The When Defeated resolves for P2, who has the initiative, so
#// P2 draws 2; P1 draws nothing. The Scout lands in its OWNER's (P1's) discard.

## GIVEN
CommonSetup: ggw/bgk/{
  theirResources:5;
  theirhandCardIds:JTL_043
}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_163:1:0
WithP2Deck: [SOR_128 SOR_128]

## WHEN
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P2HANDCOUNT:2
P2DECKCOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_163
P2DISCARDCOUNT:1
P2NODECISION
P1NODECISION
