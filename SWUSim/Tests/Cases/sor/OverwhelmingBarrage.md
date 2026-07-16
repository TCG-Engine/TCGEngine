# BuffThenSplit
#// SOR_092 Overwhelming Barrage (Event, cost 5) — give a friendly unit +2/+2 this phase, then it
#// deals damage equal to its (BUFFED) power divided among any number of OTHER units. P1's only
#// friendly is a 3/3 → buffed to 5/5 → deals 5, split 3 to one enemy + 2 to another. Proves the
#// buff is applied BEFORE power is read (total dealt = 5, not 3). Dealer auto-picked (only friendly).

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # 3/3 dealer → buffed to 5/5
WithP2GroundArena: SOR_046:1:0    # 3/7 — takes 3
WithP2GroundArena: SOR_095:1:0    # 3/3 — takes 2, survives

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:3
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P2GROUNDARENACOUNT:0

---

# ChooseDealer
#// SOR_092 — two friendly units; the player CHOOSES which gets the +2/+2 (becoming the dealer).
#// P1 picks the 3/3 (→5/5); the unchosen 3/7 friendly stays 3/7 and is itself a valid "other unit"
#// split target. Splits the 5 power: 2 onto the unchosen friendly + 3 onto an enemy. Proves the buff
#// hits ONLY the chosen unit, the dealer is excluded from targets, and friendly units are legal targets.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # 3/3 — chosen dealer → 5/5
WithP1GroundArena: SOR_046:1:0    # 3/7 — unchosen friendly; takes 2, NOT buffed
WithP2GroundArena: SOR_046:1:0    # 3/7 — enemy; takes 3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1:2,theirGroundArena-0:3

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# DelayedDefeatWithoutBuff
## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # 3/3 dealer → buffed to 5/5
WithP2GroundArena: SOR_046:1:0    # 3/7 — takes 3
WithP2GroundArena: SOR_095:1:0    # 3/3 — takes 2, survives

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:3
- P1>AttackGroundArena:0:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# NoFriendly_Fizzle
#// SOR_092 — no friendly unit to buff: the event fizzles (no decision) and goes to discard.
#// Absence guard.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0    # enemy present, but no friendly to choose as dealer

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OnlyDealer_BuffNoDamage
#// SOR_092 — only one unit in play (the dealer), no OTHER units to damage. The +2/+2 is still
#// applied (the buff is NOT gated on having split targets), and no MZSPLITASSIGN is queued. Guards
#// the buff-before-target-check ordering.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0    # 3/3 → 5/5; no other units anywhere

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1NODECISION
