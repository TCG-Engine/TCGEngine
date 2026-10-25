# Specialized advantage spending — rules v37

10050 paired comparisons (20100 engine resolutions). Each comparison spends versus saves the matching token with the same cards committed.

| Main | Immediate life-edge gain | Hit rate when spent | Additional payoff observed |
|---|---:|---:|---|
| Rushdown | +0.000 | 89.8% | Next-round +2 attack: 89.8% |
| Clinch Throw | +0.000 | 94.5% | On-hit next-round bonus/reaction burn lock: 94.5% |
| Driving Tackle | +1.596 | 98.9% | Choice of one route to cut: 98.9% |
| Driving Kick | +0.000 | 80.1% | Choose the opposing destination: 80.1% |
| Shield Bash | +0.944 | 74.2% | Can stay Green: 100% |
| Brace | +0.000 | 36.7% | Suppresses opposing carried attack and all new next-round attack bonuses; no carried pressure in this matrix |
| Mirror Step | +0.000 | 63.3% | Removes held opposing advantage; opponent has none in this matrix (see revision35-tests.php) |
| Counterpunch | +1.606 | 84.7% | Opposing objective award denied: 6.6% |
| Charged Fist | +0.000 | 88.4% | Opposing main permanently burned: 88.4% |

Immediate life-edge gain is not total token value. Rushdown, Driving Kick, and Charged Fist intentionally have no immediate stat payoff from spending; pressure, destination control, and permanent card destruction are reported separately. Rates are uniformly weighted one-round coverage, not human play rates or match win probabilities. Route cuts and forced destinations are listed as choices; their future value is not priced.

- Both players start at 40 life with intact hands; one round, no reactions
- Compare each specialized spender with and without spending its one matching held token; opponent holds no tokens
- Own main/bonus and opposing main/bonus are identical between each paired comparison
- All own bonus choices and all 201 opponent plans receive equal weight
- Uses authoritative commit/reveal/damage resolution; damage and capped recovery are included in immediate life edge
- Route cuts are listed as available choices, not selected; later turns and permanent resource value are not priced
- Combination Strike is excluded from this specialized-spender comparison; its flexible combinations remain covered by functional tests

[Full paired comparisons](spender-matchups.csv) · [Summary data](spender-summary.json) · [Baseline matrix](explorer.html)
