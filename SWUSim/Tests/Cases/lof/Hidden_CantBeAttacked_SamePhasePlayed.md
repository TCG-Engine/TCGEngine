# Hidden (LOF keyword) — "This unit can't be attacked if it was played this phase." P1 plays Attuned
# Fyrnock (LOF_143, 4/1, Hidden) this phase; it's the only P1 ground unit. P2's attacker (SEC_080, 3/3)
# has no legal unit target → its attack auto-redirects to P1's base. Fyrnock is untouched (and, at 1 HP,
# would die if it could be targeted — so DAMAGE:0 + alive proves the block).

## GIVEN
CommonSetup: rrw/rrk/{myResources:2}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LOF_143

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_143
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3
