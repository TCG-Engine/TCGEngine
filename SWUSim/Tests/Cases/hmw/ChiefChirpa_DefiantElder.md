# PrintedPowerWithNoOtherEwoks
#// HMW_164 Chief Chirpa (1/5, Ewok) — "This unit gets +1/+0 for each OTHER friendly Ewok unit."
#// Alone on the board he is at his printed 1/5. This is also the self-exclusion proof: Chirpa is himself
#// an Ewok, so a count that forgot "other" would read 1 and make him 2 power.

## GIVEN
CommonSetup: rrw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: HMW_164:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_164
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:5

---

# PowerScalesPerOtherFriendlyEwok
#// Two other friendly Ewoks (HMW_177 Adamant Ewoks, ASH_166 Ewok Warrior) → 1 + 2 = 3 power.
#// Two rather than one on purpose: a "+1 if you control another Ewok" misreading would also give 2 with a
#// single Ewok, so only a count ≥2 separates "for each" from "if any".

## GIVEN
CommonSetup: rrw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [HMW_164:1:0 HMW_177:1:0 ASH_166:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_164
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5

---

# BonusIsPowerOnly_HpUnchanged
#// "+1/+0" — the HP half is zero. With two other Ewoks his HP must still be the printed 5, not 7.

## GIVEN
CommonSetup: rrw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [HMW_164:1:0 HMW_177:1:0 ASH_166:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5

---

# FriendlyNonEwoksDoNotCount
#// The trait gate is load-bearing: SOR_095 Battlefield Marine (Rebel/Trooper) and SOR_046 Consular
#// Security Force (Rebel/Trooper) are friendly units but not Ewoks, so Chirpa stays at printed power.

## GIVEN
CommonSetup: rrw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [HMW_164:1:0 SOR_095:1:0 SOR_046:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_164
P1GROUNDARENAUNIT:0:POWER:1

---

# EnemyEwoksDoNotCount
#// "FRIENDLY Ewok unit" — two ENEMY Ewoks give Chirpa nothing. Pairs with the test above so both halves
#// of the filter (trait AND controller) are proven independently.

## GIVEN
CommonSetup: rrw/rrw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: HMW_164:1:0
WithP2GroundArena: [HMW_177:1:0 ASH_166:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_164
P1GROUNDARENAUNIT:0:POWER:1

---

# ADeployedEwokLEADERUnitCounts
#// "each other friendly Ewok UNIT" — a deployed leader IS a unit, and HMW_014 Wicket is an Ewok. With
#// Wicket deployed and no other Ewok, Chirpa is 1 + 1 = 2. (Guards the leader-unit half of the scan; a
#// count that only walked non-leader arena entries would leave him at 1.)

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014:1:1:1;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_164:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_164
P1GROUNDARENAUNIT:0:POWER:2

---

# TwoChirpasEachCountTheOther
#// Each copy sees the other as an "other friendly Ewok", so both sit at 2 — neither counts itself.

## GIVEN
CommonSetup: rrw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [HMW_164:1:0 HMW_164:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:1:POWER:2
