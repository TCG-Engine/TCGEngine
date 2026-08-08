# NoForce_NoBuff
#// LOF_237 The Son (6/8) — negative: without the Force, no buff — The Son stays 6 power and the friendly
#// SOR_095 stays 3 power.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1GroundArena: LOF_237:1:0
WithP1GroundArena: SOR_095:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:1:POWER:3

---

# WithForce_BuffsFriendlies
#// LOF_237 The Son (6/8) — "While the Force is with you, each friendly unit gets +2/+0." With the Force,
#// The Son (6 → 8) and a friendly SOR_095 (3 → 5) both gain +2 power; HP is unchanged.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1Force: true
WithP1GroundArena: LOF_237:1:0
WithP1GroundArena: SOR_095:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HP:8
P1GROUNDARENAUNIT:1:POWER:5

---

# WithForce_EnemyNotBuffed
#// LOF_237 The Son (6/8) — "each FRIENDLY unit gets +2/+0" — the buff is friendly-only. With the Force, The
#// Son (6 → 8) and a friendly SOR_095 (3 → 5) gain +2 power, but an ENEMY SOR_095 (P2) stays at power 3.
#// Intended: "should give only friendly units +2/+0 when you have the force" (enemy rebel-pathfinder unbuffed).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1Force: true
WithP1GroundArena: LOF_237:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:1:POWER:5
P2GROUNDARENAUNIT:0:POWER:3
