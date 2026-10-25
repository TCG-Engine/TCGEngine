"""Pure exact-fixture readiness and tail planning for three Blade Break faults."""
SLUG='card-ironrot-gauntlet-blade-break-fully-blocked-no-hit-still-destroys'
KEYS={
 (SLUG,'Expected card ironrot_gauntlet to exist in p2Graveyard'):'blade_break_missing_keyword',
 (SLUG,'p2Health to equal 20, got 19'):'blade_break_defense_minus_one',
 (SLUG,'Expected card ironrot_gauntlet to exist in p2CombatChain'):'blade_break_premature_clear',
}
def build_blade_break_plan(original,reason,actions,assertions):
 if(original,reason)not in KEYS:raise RuntimeError('Unregistered Blade Break control')
 kind=KEYS[(original,reason)]
 def match(a,**fields):return all(a.get(k)==v for k,v in fields.items())
 def steps(**fields):return {a['step']for a in assertions if match(a,**fields)}
 damage=steps(type='game_state_field',rootName='FaBSim',path=['damageDealt'],comparison='equals',valueJson='0')&steps(type='game_state_field',rootName='FaBSim',path=['attackHit'],comparison='equals',valueJson='false')&steps(type='card_exists',zone='p2CombatChain',cardID='ironrot_gauntlet')&steps(type='zone_count',zone='p2Graveyard',value=0)
 if len(damage)!=1:raise RuntimeError('Need one native no-hit/live-source RESOLUTION checkpoint')
 resolved=next(iter(damage))
 declared=steps(type='card_property_equals',mzId='p2CombatChain-0',property='Role',value='DEFENSE')&steps(type='card_property_equals',mzId='p2CombatChain-0',property='FromZone',value='Equipment')&steps(type='game_state_field',rootName='FaBSim',path=['defenseValue'],comparison='equals',valueJson='4')
 if len(declared)!=1:raise RuntimeError('Need one native exact four-defense checkpoint')
 declared=next(iter(declared))
 ready=steps(type='computed_power_equals',value=4)&steps(type='computed_go_again_equals',value=0)
 if len(ready)!=1:raise RuntimeError('Need one native plain power4/no-go-again checkpoint')
 ready=next(iter(ready))
 if not 0<ready<declared<resolved<len(actions):raise RuntimeError('Invalid native timing boundaries')
 if any(type(x)is not int for x in [ready,declared,resolved]):raise RuntimeError('Native steps must be integers')
 expected_clicks=[(2,10002,'p2Equipment-0!FSM'),(2,10002,'p2Hand-0!FSM')]
 if [(a.get('playerID'),a.get('mode'),a.get('cardID'))for a in actions[ready:declared]]!=expected_clicks:raise RuntimeError('Require exact equipment then hand-block actions')
 if [(a.get('playerID'),a.get('mode'),a.get('cardID'))for a in actions[resolved:]]!=[(1,10001,'!CustomInput!Pass'),(2,10001,'!CustomInput!Pass')]:raise RuntimeError('Require exactly two real Close priority passes')
 if any(a.get('mode')!=10001 or a.get('cardID')!='!CustomInput!Pass'for a in actions[declared:resolved]):raise RuntimeError('Only actual passes may lead from block to damage')
 closed=len(actions)
 for step in [ready,declared,resolved,closed]:
  for field in ['p1ActionPoints','p2ActionPoints','p1Resources','p2Resources']:
   if step not in steps(type='scalar_zone_equals',zone=field,value=0):raise RuntimeError('Missing native free-defense economy '+field)
  if step not in steps(type='zone_count',zone='Stack',value=0):raise RuntimeError('Missing native empty Stack')
  for key in ['pendingPayment','resolvingReaction']:
   if step not in steps(type='game_state_field',rootName='FaBSim',path=[key],comparison='missing_or_null'):raise RuntimeError('Missing native cleanup '+key)
 if closed not in steps(type='card_exists',zone='p2Graveyard',cardID='ironrot_gauntlet') or closed not in steps(type='zone_count',zone='p2Graveyard',value=2):raise RuntimeError('Missing exact owner Graveyard source/count')
 if resolved not in steps(type='scalar_zone_equals',zone='p2Health',value=20):raise RuntimeError('Missing native full-block health')
 prefix=ready if kind=='blade_break_missing_keyword'else declared if kind=='blade_break_defense_minus_one'else resolved
 kept=[dict(a,step=a['step']-prefix)for a in assertions if a['step']>=prefix]
 plan=dict(mutation=kind,prefix=prefix,actions=actions[prefix:],assertions=kept,
           expected_failure_step=(closed if kind=='blade_break_missing_keyword'else resolved)-prefix)
 if kind=='blade_break_premature_clear':plan['blade_break_clear']=True
 else:plan['object_effect']=[dict(player=2,zone='Equipment'if kind=='blade_break_missing_keyword'else'CombatChain',cardID='ironrot_gauntlet',tag='NO_ABILITIES'if kind=='blade_break_missing_keyword'else'WTR_DEFENSE:-1',mode='add')]
 return plan
