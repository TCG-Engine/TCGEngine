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
