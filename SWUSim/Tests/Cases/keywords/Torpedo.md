# AutoResolve_BaseOnly
#// JTL_234 Torpedo Barrage — P1 chooses "You" with no friendly units in play. The only eligible
#// target is P1's own base, so the assignment auto-resolves (single target → no MZSPLITASSIGN
#// popup) and all 5 land on the base. Confirms the auto-resolve branch leaves no dangling decision.

## GIVEN
CommonSetup: ryk/rrk/{myResources:3;handCardIds:JTL_234}
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You

## EXPECT
P1BASEDMG:5
P1NODECISION

---

# OverCapacity_BaseLethal
#// JTL_234 Torpedo Barrage — P1 targets the Opponent, who controls NO units. The only valid
#// target is their base, so the assignment AUTO-RESOLVES (no popup). The base is an UNLIMITED
#// sink for indirect damage (unlike units, it is NOT capped at remaining HP), so all 5 land and
#// go over capacity. Base HP 30, pre-damaged to 27 → 27 + 5 = 32 damage, base defeated, P1 wins.

## GIVEN
CommonSetup: ryk/rrk/{myResources:3;handCardIds:JTL_234;theirBaseDamage:27}
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:32
P1WIN

---

# ShieldIgnoredNotConsumed
#// JTL_234 Torpedo Barrage — indirect damage is unpreventable and IGNORES shields without
#// consuming them (CR 35.2.a). P2's 3/7 SOR_046 carries a Shield token (SOR_T02). P1 targets
#// Opponent; P2 assigns all 5 to the shielded unit (two valid targets: unit + base → a real
#// split popup). Damage is placed as though there were no shield: unit takes 5 damage, survives
#// (7 HP), and KEEPS its shield.

## GIVEN
CommonSetup: ryk/rrk/{myResources:3;handCardIds:JTL_234}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:5

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# TargetOpponent_AssignsUnitsAndBase
#// JTL_234 Torpedo Barrage (Event, cost 3, Cunning) — "Deal 5 indirect damage to a player."
#// P1 plays it and chooses Opponent; P2 (the damaged player) assigns the 5 unpreventable damage
#// among their own units + base: 3 to their 3/3 SEC_080 (defeats it) and 2 to their base.
#// P1 leader yk = Cunning+Villainy → covers the Cunning pip, JTL_234 plays at printed cost 3.

## GIVEN
CommonSetup: ryk/rrk/{myResources:3;handCardIds:JTL_234}
WithActivePlayer: 1
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:3,myBase-0:2

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1HANDCOUNT:0

---

# TargetSelf_OwnUnits
#// JTL_234 Torpedo Barrage — you may choose ANY player, including yourself (CR 35.1).
#// P1 chooses "You": P1 assigns the 5 among their own units + base — 3 to own 3/3 SEC_080
#// (Villainy, Side-matches the yk leader; defeats it) and 2 to own base.

## GIVEN
CommonSetup: ryk/rrk/{myResources:3;handCardIds:JTL_234}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:myGroundArena-0:3,myBase-0:2

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:2
