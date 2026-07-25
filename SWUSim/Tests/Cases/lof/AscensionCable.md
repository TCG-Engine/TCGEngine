# Saboteur
#// LOF_215 Ascension Cable — Attach to a non-Vehicle unit; attached unit gains Saboteur.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_215

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# Saboteur_IgnoresSentinel_AttackBase
#// LOF_215 Ascension Cable — the granted Saboteur lets the attached unit ignore enemy Sentinel. P1's
#// SOR_046 (3/7, +1/+3 from the cable → 4/10) bears the cable; P2 controls a Pyke Sentinel (SHD_029).
#// Normally the Sentinel would force the attack onto itself, but Saboteur ignores it, so SOR_046 attacks
#// P2's base directly for 4 (its buffed power) and the Sentinel survives. Ref: "should give its
#// own non-vehicle unit saboteur" (attack routes past the Sentinel to the base).

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:LOF_215
WithP2GroundArena: SHD_029:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1

---

# AttachToEnemyUnit
#// LOF_215 Ascension Cable — "Attach to a non-Vehicle unit" has NO "friendly" qualifier, so an ENEMY
#// non-Vehicle unit is a legal host (CR 2.e — a player may play an upgrade onto an enemy unit; if it grants
#// abilities, the attached unit's controller resolves them). With both a friendly host (LOF_050) and an enemy
#// host (SOR_046) present, P1 chooses the ENEMY: it gains Saboteur and carries the cable; the friendly host
#// does not. Ref: allows attaching to an enemy unit.

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:LOF_215}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
