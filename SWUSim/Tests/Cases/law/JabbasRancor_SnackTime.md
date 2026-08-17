# OnAttackOpponentPicksDeal7
#// LAW_216 Jabba's Rancor (7/7, Hidden) — On Attack: an opponent chooses a ground unit they control;
#// you may deal 7 damage to that unit. P2 has one ground unit (SOR_046, auto-chosen); deal 7 -> it dies.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_216:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0

---

# OppChooserPoolIsTheirOwnGroundUnitsOnly
#// LAW_216 Jabba's Rancor — "An OPPONENT chooses a ground unit THEY control": both the chooser and the
#// pool resolve from the ability's CONTROLLER, never from the card's owner. Here the Rancor sits in P1's
#// ground arena but is OWNED by P2 (the end state after a control-take), so an owner-derived "opponent"
#// would be P1 and the pool would be P1's own board. The two seats are made distinguishable — P1 also
#// fields SOR_046 at index 0, P2 fields SOR_128 and SEC_080 — and giving P2 two units keeps the choice
#// from auto-resolving, which is what hides this axis. The decision is left PENDING so the offer can be
#// read: it is P2 who is asked, and the exact legal set is P2's own two ground units (P2's frame, so
#// "my…" means P2's). Nothing of P1's is offered.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaControlled: LAW_216:2
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2HASDECISION
P2SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# ControlledRancorDamagesTheOpponentsChosenUnit
#// LAW_216 Jabba's Rancor — the full resolution across an owner/controller split. The Rancor is
#// P1-CONTROLLED but P2-OWNED; P2's lone ground unit is their forced choice, and P1 — the ability's
#// controller, who is the "you" in "You may deal 7 damage" — accepts. The 7 lands on P2's SEC_080, which
#// dies into P2's (its OWNER's) discard, while BOTH units in P1's arena, the P2-owned Rancor included, end
#// at 0 damage. Reading the seat from the Rancor's owner instead of its controller would have pointed the
#// choose and the damage at P1's own side, so the two zero-damage assertions are load-bearing.
#//
#// COVERAGE: control=this section + OppChooserPoolIsTheirOwnGroundUnitsOnly (Rancor P1-controlled /
#//           P2-owned: chooser, pool and damage all resolve from the CONTROLLER; the defeated unit goes to
#//           its OWNER's discard) · offer=OppChooserPoolIsTheirOwnGroundUnitsOnly (pending SELECTABLEEXACT
#//           over the chooser's own ground units, P1's board excluded) · decline=not encoded (no NO branch
#//           on the "you may deal 7" YESNO) · reqboundary=not encoded · boundary pair=not encoded

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaControlled: LAW_216:2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P2DISCARDCOUNT:1

---

# OpponentsChoiceIsResolvedInTHEIRFrameNotTheCasters
#// LAW_216 — "An opponent chooses a ground unit they control. You may deal 7 damage to that unit."
#// The chooser answers with a mzID written in THEIR OWN frame (`myGroundArena-N`), and the caster's
#// handler must resolve it under the chooser before acting. This section is the regression guard: the
#// handler used to call GetZoneObject() on that token BEFORE setting the frame, so `my…` resolved in
#// the CASTER's frame and the 7 damage hit the caster's own unit at the same index (or vanished when
#// that index was empty).
#// DISCRIMINATING BOARD: both seats hold TWO ground units, and the chosen index (1) exists on BOTH
#// sides — so a frame error cannot fail safe, it silently kills the wrong unit. P2 picks its own
#// SEC_080 at index 1; P1's own index-1 unit is the Rancor itself, so the old behaviour had the
#// Rancor eat itself and leave P2 untouched (the observed GOT: P2 count 2, P1 count 1).
#// ⚠ Only reachable with 2+ enemy ground units — with exactly one, LAW_216#0 takes a separate branch
#// that already resolved the UID under the opponent correctly, which is why this survived undetected.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: LAW_216:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P2>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:1:CARDID:LAW_216
