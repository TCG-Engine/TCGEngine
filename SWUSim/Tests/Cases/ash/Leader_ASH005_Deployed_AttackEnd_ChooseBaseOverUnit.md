# ASH_005 Luke Skywalker (DEPLOYED unit side) — the "that unit OR your base" choice. Friendly X-Wing
# (2/3) attacks a TIE (2/1) and takes 2 counter damage (→ 2 dmg); P1's base is also pre-damaged (4). Both
# the attacker and the base are damaged, so Luke's heal-2 presents a real MZCHOOSE; P1 picks the base
# (4 → 2), leaving the X-Wing untouched at 2 damage.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005:1:1:1;
  myBaseDamage:4;
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:2
P1SPACEARENAUNIT:0:DAMAGE:2
