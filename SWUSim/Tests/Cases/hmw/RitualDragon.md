# EntersReady_SelfAndSaboteur_WhileTatooineBase
#// HMW_234 Ritual Dragon (6/9, Cunning, cost 8, Creature) — "Saboteur. While you control a Tatooine base,
#// friendly units enter play ready (including this one)." Played onto a Tatooine base (JTL_030 Mos Eisley,
#// a vanilla Cunning Tatooine base) with no existing copy, it enters READY via the self-inclusion, and it
#// carries the auto-wired Saboteur keyword.

## GIVEN
CommonSetup: yyk/rrk/{myBase:JTL_030;myResources:10}
P1OnlyActions: true
WithP1Hand: HMW_234

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_234
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# AnotherFriendlyUnitEntersReady_WhileTatooineBase
#// With HMW_234 already in play and a Tatooine base, the NEXT friendly unit played enters ready too.

## GIVEN
CommonSetup: yyk/rrk/{myBase:JTL_030;myResources:10}
P1OnlyActions: true
WithP1GroundArena: HMW_234:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY

---

# NoTatooineBase_EntersExhaustedNormally
#// Without a Tatooine base the passive is inactive, so a played unit enters EXHAUSTED (CR 8.22.f default).
#// HMW_234 is in play here; the missing condition is the base, proving the base gate matters.

## GIVEN
CommonSetup: yyk/rrk/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: HMW_234:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:EXHAUSTED
