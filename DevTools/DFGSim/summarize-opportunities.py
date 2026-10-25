"""Render the isolated balance comparisons; does not edit the live catalog."""
import json
from pathlib import Path

folder = Path(__file__).parent / 'results' / 'opportunities-v7'
data = json.loads((folder / 'comparison.json').read_text())
template = (Path(__file__).parent / 'pair-analysis-template.html').read_text(encoding='utf-8')
for result in [folder.parent / 'pairs-v7', *[folder / name for name in data['scenarios']]]:
    payload = (result / 'data.json').read_text(encoding='utf-8').replace('</', '<\\/')
    (result / 'explorer.html').write_text(template.replace('__PAIR_DATA__', payload), encoding='utf-8')
proposals = [
    ('chain-trigger-2', 59, 'Chain Grapple', 'Conditional attack +3 → +2', 'First trial'),
    ('watchful-block-4', 144, 'Watchful Guard', 'Printed block 5 → 4', 'Counter-risk'),
    ('watchful-clean-objective', 144, 'Watchful Guard', 'Objective also requires taking no damage', 'Economy trial'),
    ('covering-read-5', 30, 'Covering Step', 'Read printed attack 5+, instead of 6+', 'Small buff'),
    ('covering-big-read', 30, 'Covering Step', 'Keep 6+ read, increase block gain +2 → +3', 'Alternative'),
]
small = {}
for profile in ['uniform', 'half-burn', 'no-burn', 'burn-only']:
    rows = []
    for name, pair, card, change, verdict in proposals:
        base = data['scenarios']['baseline']['profiles'][profile][pair]
        trial = data['scenarios'][name]['profiles'][profile][pair]
        rows.append(dict(scenario=name, card=card, change=change, verdict=verdict, base=base, trial=trial))
    small[profile] = rows

html = '''<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>DFG balance opportunities</title><style>
:root{color-scheme:dark;font:14px system-ui,sans-serif;background:#111519;color:#edf0ee}*{box-sizing:border-box}body{margin:0}main{max-width:1100px;padding:24px;margin:auto}h1{font-size:26px;margin:0 0 8px}h2{font-size:19px;margin-top:28px}h3{font-size:15px;margin:0 0 5px}p,li{color:#b6c0c7;line-height:1.6}a{color:#77bbdb}select{font:inherit;color:inherit;background:#24313c;padding:9px;border:1px solid #43515c;border-radius:5px;max-width:100%}label{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:22px 0 8px}.comparison{display:grid;grid-template-columns:minmax(0,1fr) minmax(190px,.8fr);gap:20px;padding:16px 0;border-bottom:1px solid #34414a}.comparison p{margin:5px 0;font-size:12px}.bars{font-size:12px}.barline{display:flex;align-items:center;gap:7px;margin:9px 0}.barline>span{width:40px}.track{position:relative;flex:1;height:12px;background:#202a32}.track:after{position:absolute;content:'';left:37.5%;height:100%;width:1px;background:#9ca8af}.bar{position:absolute;height:100%;background:#71b3d0}.bar.trial{background:#e2bc6c}.barline strong{width:47px;text-align:right;font-weight:500}.legend{font-size:12px;color:#b6c0c7}.tag{font-size:11px;color:#e2bc6c}.stats{display:flex;flex-wrap:wrap;gap:10px;font-size:12px}.footer{margin-top:24px;display:flex;gap:18px;flex-wrap:wrap}@media(max-width:650px){main{padding:16px}.comparison{grid-template-columns:minmax(0,1fr);gap:6px}.comparison p{font-size:13px}select{font-size:16px}}.status{color:#e2bc6c;font-size:12px}
</style></head><body><main>
<h1>Balance opportunities</h1><p class="status">Isolated trials only · live cards unchanged · rules v7</p><p>Eight catalogs × 40,401 matchups = <strong>323,208 engine-verified outcomes</strong>. One round, no reactions or held advantage tokens. These comparisons measure damage exchanges and token rewards, not match win rates.</p>
<label>Opponent choices <select id="profile"><option value="uniform">Original matrix: every legal plan equally likely</option><option value="half-burn">Each main equally likely, 50% bonus burn</option><option value="no-burn">Each main equally likely, no bonus burns</option><option value="burn-only">Each main equally likely, always burns a bonus</option></select></label><p id="weight"></p>
<p class="legend">Bars show mean damage edge for the same named plan before and after a change. Blue = current; gold = trial. Zero is the vertical line; the fixed scale is −3 to +5 damage points. Burns still cost a permanent card.</p><div id="comparisons"></div>
<h2>What I would try first</h2><ol><li><strong>Chain Grapple +3 → +2:</strong> reduce the extra attack from committing a bonus, preserving its aggressive identity while expanding the counter set.</li><li><strong>Covering Step reads 5+ attack:</strong> separately broaden this underperforming defensive read. This is a modest buff, not a broad Red stat increase.</li><li><strong>Preserve Watchful Guard's combat counter:</strong> if token production is excessive in real games, test the stricter objective rather than immediately reducing its block.</li></ol>
<h2>A nerf can remove another card's counter</h2><p>Firm Counter + Shield Bash loses to only Watchful Guard + Punish the Setup in the current one-round matrix. Reducing Watchful's block to 4 removes that losing matchup. The combined trial does the same. This argues for changing one lever at a time and watching the next strongest defensive line.</p>
<h2>Other opportunities to monitor</h2><ul><li><strong>Quick Jab as a bonus:</strong> adding +1 block raises its average marginal damage-edge benefit from +0.45 to +1.25 across the other Red mains. It buffs those mains' burn options, not Quick Jab's own main play. Test separately.</li><li><strong>Objective economy:</strong> with 50/50 opposing burns, no-burn Measured Advance earns in 64.0% of cases and Haymaker in 70.3%, versus 10.8% for Sidestep/Brace. Their risk and token payoff differ; equal reward rates are not the goal.</li><li><strong>Spenders:</strong> don't buff Combination Strike, Rushdown, Counterpunch, or other spenders from this zero-token matrix alone. It omits their payoffs and future hand routes.</li></ul>
<h2>Next validation</h2><p>Reaction-enabled full matches, both initial priorities, fresh seeds, and several play policies. Track token earnings/spending, burns, hand switching, damage, and match completion. The combined trial is a warning about interactions, not a proposed patch.</p>
<div class="footer"><a href="findings.md">Detailed findings</a><a href="comparison.json">All comparison data</a><a href="../pairs-v7/explorer.html">Original matrix</a></div>
<script type="application/json" id="data">__DATA__</script><script>
const data=JSON.parse(document.getElementById('data').textContent),select=document.getElementById('profile');
const signed=n=>(n>0?'+':'')+n.toFixed(2),pct=n=>(100*n).toFixed(1)+'%';
function bar(v,trial){const left=(Math.min(0,v)+3)/8*100,width=Math.abs(v)/8*100;return `<div class="barline"><span>${trial?'Trial':'Now'}</span><div class="track"><div class="bar ${trial?'trial':''}" style="left:${left}%;width:${width}%"></div></div><strong>${signed(v)}</strong></div>`;}
function draw(){document.getElementById('weight').textContent=select.value==='uniform'?'The original matrix includes a bonus in 82.1% of opposing plans.':select.value==='half-burn'?'Equal weight for each main; half its probability goes to no burn and half is shared across legal bonuses.':select.value==='no-burn'?'All opponents conserve their bonus cards. The row plan may still spend a card.':'All opponents burn one legal bonus; main cards remain equally weighted.';document.getElementById('comparisons').innerHTML=data[select.value].map(r=>`<section class="comparison"><div><span class="tag">${r.verdict}</span><h3>${r.card}: ${r.change}</h3><p>${r.base.label}</p><div class="stats"><span>Positive edge: ${pct(r.base.positiveRate)} → ${pct(r.trial.positiveRate)}</span><span>Earn token: ${pct(r.base.objectiveRate)} → ${pct(r.trial.objectiveRate)}</span><span>Worst edge: ${signed(r.base.worst)} → ${signed(r.trial.worst)}</span></div><p><a href="${r.scenario}/explorer.html">Explore trial matrix</a></p></div><div class="bars">${bar(r.base.meanNet,false)}${bar(r.trial.meanNet,true)}</div></section>`).join('');}select.addEventListener('change',draw);draw();
</script></main></body></html>'''
(folder / 'index.html').write_text(html.replace('__DATA__', json.dumps(small)), encoding='utf-8')
print(folder / 'index.html')
