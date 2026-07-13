# TWI_017 "Flipatine" (HEROISM face) — bouncing a TOKEN defeats it (a token can't return to hand, so it
# is defeated instead). P1 plays TWI_191 to "return" its own Clone Trooper token (TWI_T02, Heroism) — the
# token is defeated, which DOES satisfy "a friendly Heroism unit was defeated this phase." The leader
# Action then resolves: draw 1 (deck 2→1), heal 2 (base 5→3), flip to the Villainy face.
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myResources:3;myBaseDamage:5;handCardIds:TWI_191}
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>UseLeaderAbility
## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_191
P1GROUNDARENACOUNT:0
P1BASEDMG:3
P1DECKCOUNT:1
P1LEADER:EXHAUSTED
P1LEADER:DEPLOYED
