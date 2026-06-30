# SOR_034 Del Meeko (3/3) — "Each event an opponent plays costs 1 more."
# P1 controls Del Meeko. P2 holds Surprise Strike (SOR_172, Event, Aggression, cost 3)
# and has exactly 3 ready resources. The surcharge makes it cost 4, so P2 cannot pay:
# PlayHand is a silent no-op — the event stays in hand and P2's resources are untouched.
# (Without Del Meeko, 3 resources would play the cost-3 event — the surcharge is what blocks it.)

## GIVEN
CommonSetup: rrk/rrk/{theirResources:3;theirHandCardIds:SOR_172}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_034:1:0    # Del Meeko

## WHEN
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2RESAVAILABLE:3
