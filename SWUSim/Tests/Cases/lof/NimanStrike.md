# ForceAttack
#// LOF_124 Niman Strike — Attack with a Force unit, even if exhausted; it gets +1/+0 and can't attack bases.
#// The exhausted Plo Koon (6 power → 7 with the bonus) attacks the only legal target SOR_046 (3/7) and
#// defeats it, taking 3 counter damage.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_124}
P1OnlyActions: true
WithP1GroundArena: LOF_050:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttackerSelectable_ForceOnly
#// LOF_124 Niman Strike — "Attack with a Force unit." Only Force units may be chosen as the attacker. P1 has
#// Plo Koon (LOF_050, Force) and a Battlefield Marine (SOR_095, non-Force); the attacker prompt offers only
#// Plo Koon and Grogu, never the non-Force Marine — exactly the two Force units are selectable.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_124}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArena: LOF_246:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# TargetSelectable_NoBase_NonExhausted
#// LOF_124 Niman Strike — a Force unit "can't attack bases for this attack." Even a ready (non-exhausted) Plo
#// Koon may attack, and once chosen the target prompt offers only enemy units, never the base.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_124}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# NoEnemyUnits_SoftPass
#// LOF_124 Niman Strike — with no enemy units the event still resolves as a soft pass: the Force unit has no
#// legal attack target, so the event is simply discarded and the opponent's base takes no damage (Play anyway
#// → discard, base 0).

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_124}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P2BASEDMG:0
