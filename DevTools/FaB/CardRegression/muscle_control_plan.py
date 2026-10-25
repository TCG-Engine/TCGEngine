"""Pure readiness/planning for two exact Muscle Mutt defense controls."""
SLUG='card-muscle-mutt-yellow-defends-exactly-two-partial-damage'
CONTROL_KEYS={
 (SLUG,'p2Health to equal 18, got 19'):('muscle_defense_plus_one','WTR_DEFENSE:1'),
 (SLUG,'p2Health to equal 18, got 17'):('muscle_defense_minus_one','WTR_DEFENSE:-1'),
}
def build_muscle_control_plan(original,reason,actions,assertions):
    if(original,reason)not in CONTROL_KEYS:raise RuntimeError('Unregistered Muscle defense control')
    mutation,tag=CONTROL_KEYS[(original,reason)]
    def match(a,**fields):return all(a.get(k)==v for k,v in fields.items())
    def steps(**fields):return {a['step']for a in assertions if match(a,**fields)}
    prefixes=steps(type='card_property_equals',mzId='p2CombatChain-0',property='Role',value='DEFENSE')&steps(type='card_property_equals',mzId='p2CombatChain-0',property='FromZone',value='Hand')&steps(type='card_exists',zone='p2CombatChain',cardID='muscle_mutt_yellow')&steps(type='computed_power_equals',value=4)
    if len(prefixes)!=1:raise RuntimeError('Fresh fixture needs one post-block Muscle source/power checkpoint')
    prefix=next(iter(prefixes))
    if not isinstance(prefix,int)or isinstance(prefix,bool)or not 0<prefix<len(actions):raise RuntimeError('Invalid post-block prefix')
    click=actions[prefix-1]
    if click.get('mode')!=10002 or click.get('playerID')!=2 or click.get('cardID')!='p2Hand-0!FSM':raise RuntimeError('Post-block prefix must end with actual defender click')
    for key in ['pendingPayment','resolvingReaction']:
        if prefix not in steps(type='game_state_field',rootName='FaBSim',path=[key],comparison='missing_or_null'):raise RuntimeError('Fresh native cleanup checkpoint missing '+key)
    for zone in ['p1ActionPoints','p2ActionPoints','p1Resources','p2Resources']:
        if prefix not in steps(type='scalar_zone_equals',zone=zone,value=0):raise RuntimeError('Post-block native economy missing '+zone)
    if prefix not in steps(type='zone_count',zone='Stack',value=0):raise RuntimeError('Post-block Stack not pinned')
    hits=sorted(s for s in steps(type='scalar_zone_equals',zone='p2Health',value=18)if s>prefix)
    if not hits:raise RuntimeError('Fresh fixture lacks later native HP18 damage oracle')
    hit=hits[0]
    if hit>len(actions)or not actions[prefix:hit]or any(a.get('mode')!=10001 or a.get('cardID')!='!CustomInput!Pass'for a in actions[prefix:hit]):raise RuntimeError('Expected only actual priority passes between block and damage')
    kept=[dict(a,step=a['step']-prefix)for a in assertions if a['step']>=prefix]
    if not any(a['step']==hit-prefix and match(a,type='scalar_zone_equals',zone='p2Health',value=18)for a in kept):raise RuntimeError('Native damage checkpoint was lost')
    effect=dict(player=2,zone='CombatChain',cardID='muscle_mutt_yellow',tag=tag,mode='add')
    return dict(mutation=mutation,prefix=prefix,expected_failure_step=hit-prefix,object_effect=[effect],actions=actions[prefix:],assertions=kept)
