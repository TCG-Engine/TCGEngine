# NoNonHeroismUnit_Fizzles
#// SOR_235 Galactic Ambition — guard: the only other card in hand is a [Heroism] unit (SOR_095), which
#// is NOT a legal target → the event fizzles: no free play, no self-base damage, SOR_095 stays in hand.

## GIVEN
CommonSetup: rrk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_235
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:0
P1HANDCOUNT:1

---

# PlayFreeUnit_SelfBaseDamage
#// SOR_235 Galactic Ambition (Event, cost 7, Villainy) — "Play a non-[Heroism] unit from your hand for
#// free. Deal damage to your base equal to its cost." P1 plays Galactic Ambition (cost 7 → 0 left),
#// then plays the only non-Heroism unit in hand (SEC_080, cost 2) FREE → it enters the ground arena and
#// P1's OWN base takes 2 (its printed cost). Hand ends empty.

## GIVEN
CommonSetup: rrk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_235
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1BASEDMG:2
P1HANDCOUNT:0
