# NoForce_NoGrit
#// LOF_050 Plo Koon (6/8) — negative: without the Force he has no Grit, so 3 damage does not raise his
#// power (stays 6).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1GroundArena: LOF_050:1:3

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:6

---

# WithForce_GainsGrit
#// LOF_050 Plo Koon (6/8) — "While the Force is with you, this unit gains Grit." With the Force and 3
#// damage on him, Grit is active: power 6 + 3 (one per damage) = 9.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1Force: true
WithP1GroundArena: LOF_050:1:3

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:9
