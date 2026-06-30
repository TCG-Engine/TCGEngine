# SEC_156 Nemik's Manifesto (Upgrade, +1/+1, cost 1, Aggression/Heroism, "Attach to a non-Vehicle unit")
#   "Attached unit gains the Rebel trait and: 'When Defeated: Deal 1 damage to each enemy base for
#    each other friendly Rebel unit.'"
# Host A = SEC_080 (Imperial, NON-Rebel) + Nemik's → 4/4 and Rebel-by-grant. It attacks the 8/8 SOR_039
# and dies (4 HP < 8 counter), firing its granted When Defeated. Other friendly units:
#   B = SEC_080 (non-Rebel) + Nemik's → counts ONLY because the manifesto grants it Rebel,
#   C = SOR_095 (natural Rebel)        → counts,
#   D = SEC_080 (non-Rebel, no Nemik's) → does NOT count.
# So "other friendly Rebel units" = B + C = 2 → deal 2 to P2's base. The value 2 (not 1, not 3) proves
# the trait GRANT works (B counts) AND a plain non-Rebel (D) doesn't. A dies as the ATTACKER so the
# granted When Defeated drains inside P1's own action.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SEC_156
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 1:SEC_156
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:3
P2GROUNDARENACOUNT:1
