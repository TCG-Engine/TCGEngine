# NoCredit_Unaffordable_NoOp
#// LAW_156 Hunter For Hire — the cost is "defeat a friendly Credit token." With NO Credit token, the
#// opponent can't pay, so the action is a full no-op: control does not change. P2 has no Credit, so its
#// attempt to use the action on the enemy Hunter For Hire does nothing (P1 keeps control).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: LAW_156:1:0

## WHEN
- P2>UseUnitAbility:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_156
P2GROUNDARENACOUNT:0

---

# OpponentTakesControl
#// LAW_156 Hunter For Hire (4/4) — "Action [defeat a friendly Credit token]: Take control of this unit.
#// Any player may use this ability." P1 controls Hunter For Hire; on P2's turn, P2 (the opponent) uses the
#// action — defeating one of P2's OWN Credit tokens — to take control of it. The unit moves to P2's arena.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: LAW_156:1:0
WithP2Credits: 1

## WHEN
- P2>UseUnitAbility:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_156
P2CREDITCOUNT:0
