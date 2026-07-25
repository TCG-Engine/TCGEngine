# ChooseTwoSith
#// LOF_042 Always Two — Choose 2 friendly Sith units; give each 2 Shield + 2 Experience tokens; defeat all
#// other friendly units. P1 has two Sith (SOR_038, SOR_087) and one non-Sith (LOF_050). The two Sith are
#// chosen → kept with 2 shields each; LOF_050 is defeated.

## GIVEN
CommonSetup: bbk/ggw/{myResources:4;handCardIds:LOF_042}
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0
WithP1GroundArena: SOR_087:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:2
P1GROUNDARENAUNIT:1:SHIELDCOUNT:2

---

# SelectableOnlyUniqueSith
#// LOF_042 Always Two — only friendly unique Sith units are selectable targets. P1 has three unique Sith
#// (SOR_135 Emperor Palpatine ground, SOR_087 Darth Vader ground, LOF_233 Scimitar space) plus a non-Sith
#// (LOF_050). The choose-2 prompt offers exactly the three unique Sith; the non-Sith is not selectable.

## GIVEN
CommonSetup: bbk/ggw/{myResources:8;handCardIds:LOF_042}
P1OnlyActions: true
WithP1GroundArena: SOR_135:1:0
WithP1GroundArena: SOR_087:1:0
WithP1GroundArena: LOF_050:1:0
WithP1SpaceArena: LOF_233:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0

---

# GiveTokensDefeatOthers_CrossArena
#// LOF_042 Always Two — with three unique Sith available (two ground + one space) plus a non-Sith, P1 chooses
#// Emperor Palpatine (ground) and Scimitar (space). Each chosen unit gets 2 Shield + 2 Experience tokens
#// (4 upgrades). All OTHER friendly units are defeated: Darth Vader and the non-Sith LOF_050 — two unique
#// Sith are chosen and every other friendly unit is defeated across both arenas.

## GIVEN
CommonSetup: bbk/ggw/{myResources:8;handCardIds:LOF_042}
P1OnlyActions: true
WithP1GroundArena: SOR_135:1:0
WithP1GroundArena: SOR_087:1:0
WithP1GroundArena: LOF_050:1:0
WithP1SpaceArena: LOF_233:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&mySpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_135
P1GROUNDARENAUNIT:0:SHIELDCOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:4
P1SPACEARENAUNIT:0:CARDID:LOF_233
P1SPACEARENAUNIT:0:UPGRADECOUNT:4

---

# NonUniqueSith_NotSelectable
#// LOF_042 Always Two — only <uq> Sith are selectable. P1 has 2 unique Sith (Count Dooku SOR_038, Darth Maul
#// TWI_135), a NON-unique Sith (Acolyte of the Beyond LOF_129), and a non-Sith (Plo Koon LOF_050). Only the
#// two unique Sith are selectable; the non-unique Sith and non-Sith are excluded.
## GIVEN
CommonSetup: bbk/ggw/{myResources:8;handCardIds:LOF_042}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0
WithP1GroundArena: TWI_135:1:0
WithP1GroundArena: LOF_129:1:0
WithP1GroundArena: LOF_050:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# FewerThanTwoUniqueSith_DefeatsAllFriendly
#// LOF_042 Always Two — "Defeat all other friendly units" is unconditional. With only ONE unique Sith
#// (Count Dooku SOR_038) — a non-unique Sith (LOF_129) doesn't count — P1 can't choose 2, so NONE are spared
#// and ALL friendly units are defeated (no buff). Ground arena ends empty.
## GIVEN
CommonSetup: bbk/ggw/{myResources:8;handCardIds:LOF_042}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0
WithP1GroundArena: LOF_129:1:0
WithP1GroundArena: LOF_050:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0
