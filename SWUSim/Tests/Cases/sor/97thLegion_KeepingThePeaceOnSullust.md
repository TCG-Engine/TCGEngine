# SevenResources
#// SOR_118 97th Legion (Command unit, cost 7, base 0/0, Imperial/Trooper) — "This unit gets +1/+1 for
#// each resource you control." With 7 resources it is 7/7.

## GIVEN
CommonSetup: ggk/rrk/{myResources:7}
WithP1GroundArena: SOR_118:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7

---

# ThreeResources
#// SOR_118 97th Legion — the bonus scales with the resource COUNT (not a fixed value). With only 3
#// resources it is 3/3.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3}
WithP1GroundArena: SOR_118:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
