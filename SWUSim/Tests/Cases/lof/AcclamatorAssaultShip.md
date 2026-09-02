# Buff5
#// LOF_106 Acclamator Assault Ship (5/8) — On Attack: may give another unit +5/+5 for this phase. It
#// attacks the base and buffs the friendly SOR_095 (3 → 8 power).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_106:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8

---

# Offer_AnotherUnit_SpansBOTHSidesAndBOTHArenas_ExcludingOnlyItself
#// THE OFFER CELL. "Give ANOTHER unit +5/+5" carries exactly one restriction and it is the word
#// "another": no controller word, no arena word, no trait. So the pool is every unit on the table
#// EXCEPT the Acclamator - both players, both arenas - and the only thing that can be wrong with it is
#// the self-exclusion or an unwarranted narrowing.
#// A +5/+5 on an enemy unit is a real if unusual play, which is why the text's silence matters.
#// The Acclamator attacks the base so the On Attack fires with the pool intact.

## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LOF_106:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0
