# VISUAL CHECK — does a Piloting card light up when a CREDIT is what makes it affordable?
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it in the Test Schema
# Editor as SEAT 1 and just LOOK at the hand — there are no WHEN steps. This one is a QUESTION, not a
# regression guard: it exists so the behaviour can be eyeballed before deciding whether it is a bug.
#
# THE QUESTION
# JTL_103 Chewbacca is unit cost 5 / Piloting cost 3. This board gives P1 TWO ready resources and ONE
# Credit token, and puts a pilotable X-Wing in the space arena.
#   • Total PAYMENT CAPACITY is 3 — Credits pay play costs (CR 3.13), and SWUTotalPaymentCapacity counts
#     ready resources + defeatable Credits + SEC_122 Droids.
#   • The Piloting cost is 3.
# So by the rules the pilot play looks payable, and the card should arguably glow green.
#
# WHAT IT DOES TODAY: Chewbacca stays DARK.
# The hand glow now understands Piloting (fixed this session — Cases/jtl/Chewbacca_PilotingHandGlow.md),
# but it decides host legality through SWUGetPilotValidTargets, and that helper counts only REAL ready
# resources:
#       if (SWUIsCreditToken($r->CardID ?? '')) continue; // Credit tokens aren't resources
#       if (empty($r->removed) && intval($r->Status) === 1) $ready++;
#       if ($ready < $cost) return [];
# Two ready resources against a cost of 3 → no legal hosts → no glow.
#
# ⚠ AND IT IS NOT ONLY THE GLOW. That same helper builds the OFFER — the list of Vehicles you may attach
# to when you play the card. So if this is wrong, the fix is not cosmetic: it changes whether the pilot
# play is available at all, which is why it wants a deliberate decision rather than a quiet patch.
#
# WHAT TO LOOK AT
#   1. P1's resource row reads: real / real / CREDIT (the Credit is appended last by WithP1Credits).
#      Both real resources are READY. If they are not, the fixture did not load.
#   2. The space arena holds SOR_237 Alliance X-Wing with NO pilot attached — a legal Piloting host.
#   3. Chewbacca in hand: TODAY he is dark/unhighlighted. The question is whether he SHOULD be green.
#   4. Now the control — click him anyway. The server is the authority, and it uses a different code
#      path from the glow, so this tells you what the RULES currently allow as opposed to what the UI
#      advertises. Whatever happens here is the real answer; the glow is only advisory.
#
# THE COMPARISON THAT MAKES IT CONCRETE
# Swap `WithP1Credits: 1` for a third real resource (`WithP1Resources: 3:SOR_095:1`, no Credits) and
# reload. Capacity is 3 either way — but with three REAL resources Chewbacca lights up green. If a Credit
# and a resource are meant to be interchangeable for paying a Piloting cost, those two boards must look
# identical, and today they do not.
#
# BOARD SHAPE (why each element is here)
#   Leader/base  — `rgw` = Aggression base + Leia (Command/Heroism). Chewbacca is Command+Heroism and his
#                  Piloting cost is [3 resources, Command, Heroism], so the leader covers BOTH pips and
#                  there is no aspect penalty inflating 3 into something else. The Aggression base is
#                  irrelevant to him and is just the requested setup.
#   P1 resources — exactly 2 real (ready) + 1 Credit. Two is deliberately ONE SHORT of the Piloting cost,
#                  so the Credit is the only thing that could close the gap; with 3 real resources the
#                  question would not arise, and with 1 the card is unaffordable either way.
#   P1 space     — SOR_237 Alliance X-Wing, a Vehicle with no Pilot. Without a legal host Chewbacca would
#                  correctly stay dark for a completely different reason and this check would prove
#                  nothing.
#   P1 hand      — JTL_103 Chewbacca, the card from the report (unit 5 / Piloting 3).
#
# CROSS-BROWSER: not layout-sensitive — this is a single card's highlight colour, not a modal or a grid.
# One engine is enough unless the answer turns into a real UI change.

## GIVEN
CommonSetup: rgw/bbw
WithP1Resources: 2:SOR_095:1
WithP1Credits: 1
WithP1SpaceArena: [SOR_237:1:0]
WithP1Hand: [JTL_103]
WithActivePlayer: 1
WithInitiativePlayer: 1

## WHEN

## EXPECT
P1RESAVAILABLE:2
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1
