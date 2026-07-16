# NoForce_NoSentinel
#// LOF_196 Jedi Sentinel (5/4) — negative: without the Force, no Sentinel.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1GroundArena: LOF_196:1:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# WithForce_GainsSentinel
#// LOF_196 Jedi Sentinel (5/4) — "While the Force is with you, this unit gains Sentinel." With the Force,
#// Sentinel is active; without it, it is not.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1Force: true
WithP1GroundArena: LOF_196:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
