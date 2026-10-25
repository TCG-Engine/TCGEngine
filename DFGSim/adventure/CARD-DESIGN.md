# Shared foundations and champion signatures

The starter is the exact 36-card PvP set, unchanged. “Baseline” identifies a common teaching vocabulary, not a separate power tier or a restriction on deckbuilding. Every challenger starts with all 36 equipped, six in each color.

## Twelve baseline techniques

| Color | Shared cards | Teaching role |
| --- | --- | --- |
| Red | Quick Jab; Covering Step | Pressure and protection |
| Orange | Body Lock; Counter Throw | Commitment and counterplay |
| Yellow | Sidestep; Glancing Step | Defensive reads and movement |
| Green | Forearm Check; Measured Advance | Guard and measured pressure |
| Blue | Read the Strike; Catch the Opening | Prediction and openings |
| Purple | Crushing Blow; Trade Blows | Heavy strikes and exchanges |

These twelve stay in every opponent’s deck. Each opponent replaces six other PvP cards with six character variations, one per color, for a full 36-card moveset. The exact cards they use become your unlocks after the first victory. You replace an equipped card to fit a new move into a full color.

## Four headline signatures

Attack/block below are printed values. Conditional bonuses, burns, advantage objectives, and routes remain important parts of the cost.

| Opponent | Signature | Payoff | Cost / comparison |
| --- | --- | --- | --- |
| Iddin | **Reed Step**, Yellow 3/3 | +1 attack with no bonus burn; damage without bonus or reaction burns earns Orange advantage. | Compared with Sidestep, loses the high-attack defense bonus and has a weaker block burn. Keeping resources makes the clean-exchange objective harder to protect. |
| Imdugud | **Sky Step**, Yellow 2/3 | +3 block against an opposing bonus commitment; taking no damage gives +1 attack next round. | Compared with Bait and Slip, gives up relocation, one attack, and one point of conditional guard for more base guard and delayed pressure. |
| Huwawa | **Sevenfold Guard**, Green 1/5 | +2 attack against printed attack 5+; a hit recovers 1 life after damage. Guarding a heavy attack earns Green advantage. | Compared with Rooted Guard, less block and weaker burns; recovery requires damage and cannot save a knockout. The advantage reward also changes. |
| Bull of Heaven | **Horizon Charge**, Purple 6/0 | A hit after spending Purple advantage grants +2 attack next round. Dealing 4+ damage without a bonus earns Purple advantage for later. | Compared with Haymaker, one less immediate attack and a weaker attack burn; retains the miss penalty. Earning advantage does not spend it in the same exchange. |

The other twenty variations retain their PvP source abilities and exchange one printed attack for one block, or vice versa. `ADSignatureSpecs()` records every source, stat pair, and tradeoff. The public moveset and unlocked-card library display those tradeoffs; headline signatures receive a highlighted border.

## Balance status

These target the PvP repertoire’s power range, with situational payoffs and costs instead of unconditional upgrades. Equal printed totals alone do not establish equal power: effects, routes, burns, armor, and the opposing pool can change a card’s value. Playtesting should compare those matchups before calling the cards balanced.

The automated checks preserve all 36 PvP definitions, verify every opponent’s six-per-color deck and twelve shared foundations, compare supporting stat budgets, exercise the headline conditions, and check migration of old saves. Public-observation bot smoke tests cover all four matchups; they are integration checks rather than balance measurements.
