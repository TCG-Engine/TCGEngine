# BaseHit_MillAndPlayFromDiscard
#// SEC_205 Obi-Wan Kenobi (Unit, 4/5, cost 4, Cunning/Heroism, Force/Jedi/Republic, Ground)
#//   "When this unit deals combat damage to a base: Discard a card from the defending player's deck. For
#//    this phase, you may play that card from their discard pile, ignoring its aspect penalties."
#// SEC_205 attacks P2's base for 4 → mills the top of P2's deck (SOR_095) into P2's discard with the OTPN
#// modifier (play-from-opp-discard at cost, ignoring aspect penalty). P1 then plays SOR_095 from P2's
#// discard. SOR_095 is Command/Heroism — fully off-aspect for Cunning P1 (penalty would be +4), but OTPN
#// ignores it, so P1 pays exactly its cost (2). P1 has exactly 2 ready resources: the play succeeds and
#// ends at 0 ready — which it could NOT if the +4 penalty applied (proving the aspect-penalty bypass).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 2
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P2DISCARDCOUNT:0
P1RESAVAILABLE:0
P2DECKCOUNT:2
