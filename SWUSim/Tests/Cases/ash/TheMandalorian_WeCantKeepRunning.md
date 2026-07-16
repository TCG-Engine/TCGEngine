# Initiative_Decline
#// ASH_014 The Mandalorian — declining the optional payment skips the draw. P1 claims initiative and
#// declines, keeping its resource and drawing nothing.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 1
WithP1Deck: SOR_095
## WHEN
- P1>Claim
- P1>AnswerDecision:-
## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:1

---

# Initiative_PayDraw
#// ASH_014 The Mandalorian — "When you take the initiative: you may pay 1 resource; if you do, draw a card."
#// P1 claims initiative and accepts, paying 1 resource (1 → 0) to draw SOR_095.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 1
WithP1Deck: SOR_095
## WHEN
- P1>Claim
- P1>AnswerDecision:YES
## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:0

---

# Deployed_OnAttack_DrawWithInitiative
#// ASH_014 The Mandalorian (deployed) — On Attack: if you have the initiative, you may draw a
#// card. P1 holds the initiative → may draw → hand 1, deck 0.

## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014:1:1:1
}
SkipPreGame: true
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
