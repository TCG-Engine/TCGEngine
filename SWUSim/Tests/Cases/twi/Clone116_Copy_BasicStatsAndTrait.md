# TWI_116 Clone (Unit, 0/0, cost 7, Command) — "You may have this unit enter play as a copy of a
# non-leader, non-Vehicle unit in play, except it gains the Clone trait and is not unique. (Only the
# card's printed attributes are copied.)" Clone copies an enemy SOR_095 (3/3, Rebel/Trooper): it enters
# play AS SOR_095 — 3/3, with SOR_095's printed traits — plus the gained Clone trait, and is not a leader.
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:HASTRAIT:Clone
P1GROUNDARENAUNIT:0:HASTRAIT:Rebel
P1GROUNDARENAUNIT:0:NOTLEADERUNIT
P2GROUNDARENACOUNT:1
