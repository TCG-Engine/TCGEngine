# BaseAction_CreatesForceToken
#// LOF_022 Mystic Monastery — "Action: The Force is with you (create your Force token). Use this ability
#// no more than 3 times each game." Unlike the other common Force bases this is a repeatable base Action
#// (NOT an Epic Action, NOT a When-attacks trigger). One use creates P1's Force token and consumes one of
#// the three per-game uses.

## GIVEN
CommonSetup: gbk/bbk/{
  myBase:LOF_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseBaseAbility

## EXPECT
P1HASFORCE
P1BASEACTIONUSES:2
P1BASE:EPICAVAILABLE

---

# ThreeUsesPerGame_FourthBlocked
#// LOF_022 Mystic Monastery — "Use this ability no more than 3 times each game." Using the base Action a
#// fourth time is a no-op: the per-game NumUses budget caps at 3 (and is EXEMPT from the per-round
#// NumUses refill). The Force token itself is idempotent (max one, CR 37.1), so the observable cap is the
#// remaining-uses budget reaching 0 (not 1).

## GIVEN
CommonSetup: gbk/bbk/{
  myBase:LOF_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseBaseAbility
- P1>UseBaseAbility
- P1>UseBaseAbility
- P1>UseBaseAbility

## EXPECT
P1HASFORCE
P1BASEACTIONUSES:0
