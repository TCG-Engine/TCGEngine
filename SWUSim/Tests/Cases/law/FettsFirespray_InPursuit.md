# CreditOnDefenderDefeated
#// LAW_252 Fett's Firespray (4/6 space, Ambush) — "When Attack Ends: If the defending unit was defeated,
#// create a Credit token." Firespray attacks SOR_237 (2/3 space): deals 4 → SOR_237 defeated; takes 2 →
#// Firespray (6 HP) survives. Defender defeated → 1 Credit created for P1.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_252:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1CREDITCOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_252

---

# NoCreditIfDefenderSurvives
#// LAW_252 Fett's Firespray (4/6 space) — When Attack Ends: defender NOT defeated -> NO Credit.
#// Attacks SEC_047 Coronet (4/6): deals 4 (Coronet survives at 4 dmg); takes 4 -> Firespray survives.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_252:1:0
WithP2SpaceArena: SEC_047:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P1CREDITCOUNT:0
P1SPACEARENAUNIT:0:CARDID:LAW_252
P2SPACEARENAUNIT:0:CARDID:SEC_047
P2SPACEARENAUNIT:0:DAMAGE:4

---

# CreditEvenIfFiresprayAlsoDefeated
#// LAW_252 Fett's Firespray — "When Attack Ends: if the defending unit was defeated, create a Credit."
#// There is NO "if this unit survived" clause, so the Credit is still created when Firespray itself is
#// defeated in the same combat. Pre-damaged Firespray (4/6, 3 dmg → 3 HP) attacks a pre-damaged 4/5 (2 dmg
#// → 3 HP): both are defeated, and P1 still gets the Credit.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1SpaceArena: LAW_252:1:3
WithP2SpaceArena: JTL_037:1:2
WithP1Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P1CREDITCOUNT:1
