# VillainyBuff
#// LOF_081 Sith Legionnaire (2/2) — "While you control another Villainy unit, this unit gets +2/+0." With
#// the Villainy SEC_080 controlled, it is 4 power; alone it is 2.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_081:1:0
WithP1GroundArena: SEC_080:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:2

---

# NoOtherVillainy_BaseStats
#// LOF_081 Sith Legionnaire (2/2) — with NO other Villainy unit controlled the +2/+0 does not apply, so it
#// stays at base 2/2. P1 also controls Plo Koon (LOF_050, non-Villainy) which does not enable the buff.
#// Ref: "should have base stats when no other Villainy unit is controlled".

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_081:1:0
WithP1GroundArena: LOF_050:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2
