# 191_AuraBuff
#// LOF_191 BD-1 — When Played: Choose another friendly unit; while BD-1 is in play, the chosen unit gets
#// +1/+0 and gains Saboteur. P1 plays BD-1 and chooses Plo Koon, who becomes 7/8 with Saboteur.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_191}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# 191_AuraEndsWhenBd1LeavesPlay
#// LOF_191 BD-1 — the grant is "WHILE THIS UNIT IS IN PLAY", so it must end when BD-1 leaves. BD-1 buffs
#// Plo Koon (6/8 → 7/8 with Saboteur), then BD-1 (1/3) attacks the enemy 3/7 and dies to the counter —
#// Plo Koon drops straight back to 6/8 with no Saboteur. Without this the aura could be a permanent
#// one-shot buff and the existing section would still pass. (BD-1 is defeated with It's Worse (LOF_264)
#// rather than by attacking — a unit played this turn cannot attack.)
## GIVEN
CommonSetup: yyw/ggk/{myResources:9;handCardIds:LOF_191,LOF_264}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# 191_ChoosesANOTHERUnit_NotItself
#// LOF_191 BD-1 — "Choose ANOTHER friendly unit" excludes BD-1 himself. With Plo Koon the only other
#// friendly unit, the offer contains exactly Plo Koon and never BD-1's own slot.
## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_191}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# 191_NoOtherFriendlyUnit_NoOp
#// LOF_191 BD-1 — with no OTHER friendly unit there is no legal choice: BD-1 still enters play, nothing is
#// buffed, and no decision is left dangling.
## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_191}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_191
P1NODECISION

---

# 191_BuffSurvivesBd1LosingItsAbilities
#// CR 14.3: "An ability that has already begun to resolve continues to resolve even if the card that had
#// that ability loses that ability or leaves play." BD-1's buff is created by a WHEN PLAYED (triggered)
#// ability that has already resolved; its duration is "while this unit is IN PLAY". Blanking BD-1 is not
#// the same as BD-1 leaving play, so the buff continues.
#//
#// The official Admiral Yularen ruling (10/31/2025 errata + 03/06/2025) is the same printed shape —
#// "When Played: Choose ... While this unit is in play, each Vehicle unit you control gains it" — and says
#// the effect lasts "until Yularen LEAVES PLAY" and "is not changed if an opponent takes control of
#// Yularen". Duration is tied to leaving play, not to the source still possessing its abilities.
#//
#// SWUSim agreed with that for Huyang (TWI_110) and Yularen (JTL_047) — neither reader checks
#// LostAbilities — but BD-1's reader skipped a blanked source, so P1 blanking their own BD-1 with
#// SOR_138 Force Lightning silently cancelled the buff it had already granted. Fixed 2026-08-27.
#//
#// BD-1 enters (the lone other friendly SOR_095 auto-resolves as the target) -> 4 power + Saboteur.
#// P1 then plays SOR_138 on their OWN BD-1 ("choose a unit" is unqualified, so a friendly is legal).
#// Neither unit is a Force unit, so no "pay any number of resources" follow-up is offered.

## GIVEN
CommonSetup: yrw/bbk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: [LOF_191 SOR_138]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:1:CARDID:LOF_191
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
