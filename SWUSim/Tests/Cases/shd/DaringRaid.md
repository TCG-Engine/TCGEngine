# Deal2ToUnit
#// SHD_178 Daring Raid — Event, cost 1, [Aggression], trait Tactic. "Deal 2 damage to a unit or base."
#// COVERAGE: offer=the pool's breadth is proven by RESOLUTION rather than by a pending SELECTABLEEXACT —
#//           Deal2ToUnit lands it on an ENEMY unit and Deal2ToOwnBase lands it on the caster's OWN base,
#//           so both halves of "a unit or base" and both sides of the table are shown legal ·
#//           request boundary=N/A (one target, one damage step, no state re-read after the pick) ·
#//           control=N/A (an event with no lasting object) ·
#//           boundary pair=Deal2ToUnit + Deal2ToOwnBase (unit vs base, enemy vs friendly) ·
#//           decline=N/A (mandatory "deal 2 damage", no "you may" and no zero-target legal).
#// SHD_178 Daring Raid (1-cost event, Aggression) — "Deal 2 damage to a unit or base." P1 targets the enemy
#// SOR_046 (7 HP → 2 damage).

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_178
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Deal2ToOwnBase
#// SHD_178 Daring Raid — "a unit or base" is UNRESTRICTED: it may be pointed at a base, and at the
#// CASTER'S OWN base at that. Same fixture as Deal2ToUnit, but P1 aims the 2 damage at its own base;
#// the enemy SOR_046 is left untouched, proving the pick really went to the base and not to the only
#// unit on the board.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_178
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2
P2BASEDMG:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
