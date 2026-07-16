# CommandUnitPlayed_HealsBase
#// SOR_109 Colonel Yularen — with Yularen already in play, playing ANOTHER [Command] unit (SOR_095, a
#// Command,Heroism unit) heals 1 from P1's base (3 → 2).

## GIVEN
CommonSetup: ggw/brw/{
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_109:1:0
WithP1Hand: SOR_095
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:2

---

# NonCommandUnit_NoHeal
#// SOR_109 Colonel Yularen — playing a NON-[Command] unit (SOR_237, Heroism only) does NOT trigger the
#// heal; P1's base stays at 3 damage.

## GIVEN
CommonSetup: ggw/brw/{
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_109:1:0
WithP1Hand: SOR_237
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:3
P1SPACEARENACOUNT:1

---

# OwnPlay_HealsBase
#// SOR_109 Colonel Yularen (2/3) — "When you play a [Command] unit (including this one): Heal 1 damage
#// from your base." Yularen is itself a Command unit, so playing HIM (the "including this one" clause)
#// heals 1 from P1's base (3 → 2).

## GIVEN
CommonSetup: ggw/brw/{
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_109
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:1
