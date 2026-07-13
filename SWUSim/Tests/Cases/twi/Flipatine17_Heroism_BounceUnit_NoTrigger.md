# TWI_017 "Flipatine" (HEROISM face) — a BOUNCE is not a defeat. P1 plays TWI_191 (return a friendly
# non-leader non-Vehicle unit) to return its own Heroism Marine (SOR_095) to hand, then uses the leader
# Action. Since no friendly Heroism unit was DEFEATED this phase, the Action resolves nothing (no draw,
# no heal, no flip) — the leader is just spent (ruling 2).
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myResources:3;myBaseDamage:5;handCardIds:TWI_191}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>UseLeaderAbility
## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_191
P1GROUNDARENACOUNT:0
P1BASEDMG:5
P1DECKCOUNT:2
P1LEADER:EXHAUSTED
P1LEADER:NOTDEPLOYED
