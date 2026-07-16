# ShootFirst_DefeatsNoCounter
#// SOR_198 Han Solo (6/6) — "While attacking, this unit deals combat damage before the defender."
#// He attacks a 3/3: his 6 damage defeats it BEFORE it can strike back, so Han takes 0 counter-damage
#// (vs 3 with simultaneous combat).

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_198:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_198
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# ShootFirst_SurvivorCountersNoBonus
#// SOR_198 Han Solo — when the defender SURVIVES his first strike, it still deals its counter-damage.
#// Han (6/6) attacks a 3/7: it takes 6 (NOT 7 — his deal-first is the innate version with NO +1/+0,
#// unlike Shoot First the event), survives, and counters for 3.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_198:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:3
