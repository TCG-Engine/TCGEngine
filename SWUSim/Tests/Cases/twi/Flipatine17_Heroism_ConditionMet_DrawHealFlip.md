# TWI_017 Chancellor Palpatine "Flipatine" (HEROISM face, Deployed=false) — Action [Exhaust]: If a
# friendly Heroism unit was defeated this phase, draw a card, heal 2 from your base, then flip. P1's
# Heroism Marine (SOR_095) attacks into the 3/7 and dies (friendly Heroism defeated), then P1 uses the
# leader Action: draws 1 (deck 2→1), heals 2 (base 5→3), and flips to the Villainy face (Deployed=true).
# Ruling 1: the leader stays EXHAUSTED after flipping.
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
## EXPECT
P1BASEDMG:3
P1DECKCOUNT:1
P1LEADER:EXHAUSTED
P1LEADER:DEPLOYED
