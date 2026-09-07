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

---

# CreatedTOKEN_AlsoEntersReady
#// "Friendly units ENTER PLAY ready" is an entry-wide rule, not a play-time one — every route into the
#// arena has to honour it, and each route is a different code path. The three sections above all reach
#// the arena by PLAYING a card from hand; this one creates a TOKEN, which never passes through the play
#// ceremony at all (a token is created, not played).
#// TWI_200 Creative Thinking exhausts a non-unique unit and creates a Clone Trooper token. The token is
#// the last unit in the arena and must arrive READY.
## GIVEN
CommonSetup: yyk/rrk/{myBase:JTL_030;myResources:12}
P1OnlyActions: true
WithP1GroundArena: HMW_234:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: TWI_200
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:2:READY

---

# DragonOutOfPlay_FriendlyUnitsEnterExhausted
#// The aura's OTHER condition. NoTatooineBase_EntersExhaustedNormally covers "the base is wrong"; this
#// covers "the Dragon is not there" — with the Tatooine base still in place, so the two conditions are
#// proven independent rather than one standing in for the other.
#// "While you control…" is a constant ability read off the live board, so with no Ritual Dragon in play
#// the Marine arrives exhausted exactly as it always would.
## GIVEN
CommonSetup: yyk/rrk/{myBase:JTL_030;myResources:10}
P1OnlyActions: true
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# RescuedUnit_AlsoEntersReady
#// A third route into the arena: a RESCUE. A captured card returning to play never touches the play
#// ceremony or the token-creation path, so it is its own code path — and the one most likely to be
#// missed, because nothing about "rescue" reads like "entering play".
#// P2's base holds a captured Battlefield Marine. SHD_197 L3-37's When Played rescues it, and with a
#// Ritual Dragon and a Tatooine base the Marine must come back READY rather than exhausted.
#// ⚠ L3-37 herself is the OTHER new arrival in the same action, and she must arrive ready too — both
#// are asserted so a rule wired to only one entry point cannot pass.
## GIVEN
CommonSetup: yyk/rrk/{myBase:JTL_030;myResources:12}
P1OnlyActions: true
WithP1GroundArena: HMW_234:1:0
WithP2BaseCaptive: SOR_095
WithP1Hand: SHD_197
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:SHD_197
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:CARDID:SOR_095
P1GROUNDARENAUNIT:2:READY
