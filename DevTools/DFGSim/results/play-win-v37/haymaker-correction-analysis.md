# Haymaker attack correction - v37

Haymaker is restored from 8/0 to 7/0. Its miss penalty (-2 final block next round after zero combat damage, minimum 0), Purple objective, bonus, no reaction, and routes remain unchanged. No other catalog field changed. Both 2,160-game batches use identical seed/opening/priority/profile scenarios.

v37: 1766 decisive games, 394 draws, 0 caps; mean 16.90 rounds. All 479,619 replay/accounting checks passed. Main-play win association: 41.2% (1,040/2,524) before, 38.2% (597/1564) after. Selection and game paths change; this is not a causal strength estimate. Draws count as non-wins; repeated use is deduplicated per player-game.

## How often does the miss penalty trigger?

| Version | Bonus burn | Main plays | Zero-combat-damage plays | Miss rate |
|---|---|---:|---:|---:|
| v36 (8 attack) | No | 1423 | 34 | 2.4% |
| v36 (8 attack) | Yes | 3124 | 61 | 2.0% |
| v37 (7 attack) | No | 928 | 41 | 4.4% |
| v37 (7 attack) | Yes | 1185 | 26 | 2.2% |

Counts include terminal rounds. A miss sets the penalty, but it has no future cost when the game ends that round.

## One-round pair coverage

All 201 opposing main-plus-optional-bonus plans are uniformly weighted; no reactions or held advantages. Edge is damage dealt minus damage taken, not match win rate.

| Haymaker plan | v36 mean edge | v37 mean edge | v37 hit rate |
|---|---:|---:|---:|
| No bonus | -0.22 | -1.17 | 92.5% |
| Charged Fist | +1.51 | +0.54 | 100.0% |
| Crushing Blow | +1.25 | +0.28 | 100.0% |
| Iron Guard | +2.04 | +1.10 | 91.5% |
| Trade Blows | +1.51 | +0.54 | 100.0% |
| Heavy Feint | +1.51 | +0.54 | 100.0% |

[Same-scenario comparison](comparison-v36.md) · [All main win rates](play-win-report.md) · [Full-game pair win rates](pair-play-win-report.md) · [Updated pair explorer](../pairs-v37/explorer.html)
