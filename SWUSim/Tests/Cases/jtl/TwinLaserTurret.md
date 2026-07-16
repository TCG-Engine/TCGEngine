# OnAttack_DealsOneToEachOfUpToTwoUnits_SameArena
#// JTL_172 Twin Laser Turret (upgrade, +2/+2, "Attach to a Vehicle unit") — attached unit gains
#// "On Attack: Deal 1 damage to each of up to 2 units in this arena." The turret is on Frontier AT-RT
#// (SOR_249, ground vehicle, 3/5 → 5/7). It attacks P2's base; on attack P1 deals 1 to TWO ground units:
#// Wampa (SOR_164) takes 1, and Battlefield Marine (SOR_095) — which carries a Shield — takes 0 as its
#// Shield absorbs and pops. The host and unpicked units are untouched. Base takes the host's 5 power.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_249:1:0
WithP1GroundArenaUpgrade: 0:JTL_172
WithP2GroundArena: [SOR_164:1:0 SOR_095:1:0]
WithP2GroundArenaUpgrade: 1:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:5

---

# OnAttack_MayDealToNone
#// JTL_172 Twin Laser Turret — the granted On Attack is "up to 2", so it may be declined entirely.
#// P1 attacks with the turret host but chooses no targets; no unit takes damage and the shielded
#// Battlefield Marine keeps its Shield. The base still takes the host's combat damage (5).

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_249:1:0
WithP1GroundArenaUpgrade: 0:JTL_172
WithP2GroundArena: [SOR_164:1:0 SOR_095:1:0]
WithP2GroundArenaUpgrade: 1:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:1:SHIELDCOUNT:1
P2BASEDMG:5

---

# OnAttack_SpaceArena_OnlyHostInArena
#// JTL_172 Twin Laser Turret — "in this arena" follows the host's arena. On a space Vehicle (Green
#// Squadron A-Wing, SOR_141, 1/3, Raid 2 → 3/5 base, 5/5 while attacking) with no other space units,
#// the only legal target is the host itself, so it deals 1 to itself (survives at 5 HP). Base takes the
#// host's 5 attacking power (1 +2 turret +2 Raid).

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArenaUpgrade: 0:JTL_172

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_141
P1SPACEARENAUNIT:0:DAMAGE:1
P2BASEDMG:5