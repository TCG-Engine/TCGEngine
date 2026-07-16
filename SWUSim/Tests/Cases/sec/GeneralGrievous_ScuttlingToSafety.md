# OnDefense_BouncesOnlyHim_PopulatedBoard
#// SEC_187 General Grievous — clean fizzle on a populated board. P2 controls Grievous AND a vanilla
#//   SEC_080 (3/3). P1 attacks Grievous specifically; he bounces to hand before damage, the attack
#//   fizzles, and the other P2 unit + the attacker are untouched. Proves the right unit bounces and no
#//   stray damage lands.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_187:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P2HANDCOUNT:2

---

# OnDefense_ReturnsToHandBeforeDamage
#// SEC_187 General Grievous (Ground, 3/3, Hidden) — On Defense (when this unit is attacked): return him
#//   to his owner's hand BEFORE damage is dealt (mandatory). P1's SOR_046 (3/7) attacks Grievous; before
#//   combat damage he bounces to P2's hand, so the attack fizzles: no damage to anyone, and SOR_046 takes
#//   no counter damage (DAMAGE:0). Grievous is GIVEN-placed (not played this phase) so he's attackable.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_187:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0
