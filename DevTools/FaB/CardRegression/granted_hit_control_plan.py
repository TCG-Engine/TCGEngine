"""Pure inspection/planning for four disposable controls; no imports with engine work."""

CONTROL_KEYS = {
 ('card-pummel-red-two-reactions-two-hit-triggers-two-discards','Expected Stack count 2, got 1'):'remove_one_pummel',
 ('card-pummel-red-hand-cost-two-hit-defender-chooses-0',"Expected p2DecisionQueue-0.Type to equal 'MZCHOOSE', got ''"):'wrong_pummel_victim',
 ('card-razor-reflex-red-hand-cost-zero-hit-trigger-go-again','computed go again to equal 0, got 1'):'early_razor_go_again',
 ('card-razor-reflex-red-hand-cost-zero-hit-trigger-go-again','computed go again to equal 1, got 0'):'inert_razor_layer',
}

def build_granted_hit_plan(original,reason,actions,assertions):
 key=(original,reason)
 if key not in CONTROL_KEYS:raise RuntimeError('Unregistered granted-hit control')
 mutation=CONTROL_KEYS[key]
 def unique(steps,label):
  steps=set(steps)
  if len(steps)!=1:raise RuntimeError('Expected one exact '+label+' checkpoint')
  return next(iter(steps))
 def matches(a,**fields):return all(a.get(k)==v for k,v in fields.items())
 if mutation=='remove_one_pummel':
  prefix=unique((a['step']for a in assertions if matches(a,type='computed_power_equals',value=12)),'two-Pummel power12')
  hit=unique((a['step']for a in assertions if matches(a,type='zone_count',zone='Stack',value=2)),'two-Pummel Stack2')
  keep_boundary=False
 elif mutation=='wrong_pummel_victim':
  prefix=unique((a['step']for a in assertions if matches(a,type='zone_count',zone='Stack',value=1)),'single-Pummel Stack1')
  hit=unique((a['step']for a in assertions if matches(a,type='card_property_equals',mzId='p2DecisionQueue-0',property='Type',value='MZCHOOSE')),'persisted victim chooser')
  keep_boundary=False
 else:
  zero={a['step']for a in assertions if matches(a,type='computed_go_again_equals',value=0)}
  one_layer={a['step']for a in assertions if matches(a,type='zone_count',zone='Stack',value=1)}
  prefix=unique(zero & one_layer,'pending Razor Stack1/go-again0')
  hit=prefix if mutation=='early_razor_go_again' else unique((a['step']for a in assertions if matches(a,type='computed_go_again_equals',value=1)),'resolved Razor go-again1')
  keep_boundary=mutation=='early_razor_go_again'
 if not isinstance(prefix,int)or isinstance(prefix,bool)or not 0<prefix<len(actions):raise RuntimeError('Invalid granted-hit prefix')
 if not keep_boundary and not prefix<hit<=len(actions):raise RuntimeError('Missing later semantic checkpoint')
 if mutation!='early_razor_go_again':
  between=actions[prefix:hit]
  if not between or any(a.get('mode')!=10001 or a.get('cardID')!='!CustomInput!Pass'for a in between):raise RuntimeError('Expected only real priority passes before semantic failure')
 retained=[dict(a,step=a['step']-prefix)for a in assertions if a['step']>=prefix]if keep_boundary else[dict(a,step=a['step']-prefix)for a in assertions if a['step']>prefix]
 if not retained or not any(a['step']==hit-prefix for a in retained):raise RuntimeError('Semantic checkpoint was lost')
 return dict(mutation=mutation,prefix=prefix,keep_boundary=keep_boundary,expected_failure_step=hit-prefix,
             actions=actions[prefix:],assertions=retained)
