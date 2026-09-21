# Action_ReturnsSelfAndUpgrades
#// TWI_194 Ahsoka Tano — "Action [2 resources]: Return this unit and each upgrade on her to their
#// owners' hands." Ahsoka has a SOR_120 (+2/+2) attached. Using the action (paying 2 resources) returns
#// both Ahsoka and the upgrade to P1's hand: ground empties, hand gains 2, resources spent.

## GIVEN
CommonSetup: yyw/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: TWI_194:1:0
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1RESAVAILABLE:0

---

# Ambush_WhileFewerUnits
#// TWI_194 Ahsoka Tano (Unit 3/4, Ground) — "While you control fewer units than an opponent (including
#// this unit), this unit gains Ambush." Guard: P1 has only Ahsoka (1) vs P2's 2 units → HASKEYWORD Ambush.

## GIVEN
CommonSetup: yyw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_194:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# TwinSuns_FarSeatAlone_FewerUnitsThanTheFarSeatOnly
#// TWIN SUNS FAMILY FIX (2026-09-20, from the Duchess's Champion report on game 850132). "While you control fewer units than an opponent"
#// means ANY opponent, but KeywordEffects.php read it through OtherPlayer() — a TWO-SEAT helper that
#// answers 2 for seat 1 and 1 for every other seat, so at 3+ seats the far seat is invisible.
#// The enabling condition sits on P3 ONLY, with P2 deliberately clean: a one-seat read answers "no",
#// a correct read answers "yes". A 2-seat fixture cannot tell those apart.
#// P1 holds 1 unit (Ahsoka herself). P2 also holds 1 — NOT more than P1, so P2 alone never satisfies
#// it — while P3 holds 3. "fewer than AN opponent" needs only one to be above you.
## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: TWI_194:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: [SEC_080:1:0 SEC_080:1:0 SEC_080:1:0]

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TWI_194
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# TwinSuns_NeitherOpponent_FewerUnitsThanTheFarSeatOnly
#// The negative that keeps the section above honest: with the condition absent on BOTH opponents the
#// keyword must NOT appear, so a fix that grants it unconditionally reds here.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: TWI_194:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
