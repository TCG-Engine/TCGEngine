# TWI_017 "Flipatine" (HEROISM face) — Ruling 2: the Action can still be USED even with no friendly
# Heroism unit defeated this phase, but NONE of the listed effects resolve — no draw, no heal, and NO
# flip. The leader is exhausted (the action was taken) and stays on the Heroism face.
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myBaseDamage:5}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1LEADER:NOTDEPLOYED
P1BASEDMG:5
P1DECKCOUNT:2
