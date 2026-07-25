# ExhaustAll
#// LOF_203 Premonition of Doom — The next time you take the initiative this phase, exhaust all units. P1
#// plays it, then claims the initiative; every unit in play (both players') is exhausted.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1Hand: LOF_203
WithP1Resources: 7
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>Claim

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# OpponentClaims_NoExhaust
#// LOF_203 Premonition of Doom — the delayed effect fires only the next time the CONTROLLER (P1) takes the
#// initiative. If the OPPONENT (P2) claims the initiative instead, the effect does not trigger and all units
#// remain ready. Ref: "does not trigger if the opponent claims".

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1Hand: LOF_203
WithP1Resources: 7
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>Claim

## EXPECT
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:READY
