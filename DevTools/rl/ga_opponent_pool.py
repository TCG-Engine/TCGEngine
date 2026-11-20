"""Versioned chooser snapshots and balanced evaluation against multiple frozen versions.
Snapshots freeze chooser policies only; the engine and legal enumeration remain shared.
"""
import argparse
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timezone
import importlib.util
import json
from pathlib import Path
import re
import shutil
import subprocess
from evaluate_ga_parallel import identity, digest

ROOT = Path(__file__).resolve().parents[2]
SPEC = importlib.util.spec_from_file_location('ga_freezer', ROOT/'DevTools/ga-bot-evaluate.py')
FREEZER = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(FREEZER)


def verify(path):
    path = Path(path).resolve()
    manifest = json.loads((path/'manifest.json').read_text())
    if set(manifest['files']) != set(FREEZER.FILES):
        raise ValueError('Snapshot must contain exactly the four chooser files')
    for name, sha in manifest['files'].items():
        if digest(path/name) != sha:
            raise ValueError(f'Snapshot changed: {path/name}')
    return manifest


def snapshot(output, label):
    output = Path(output)
    FREEZER.freeze(output)  # Refuses replacement of any existing snapshot.
    metadata = {'label': label, 'status': 'candidate', 'createdAt': datetime.now(timezone.utc).isoformat(),
                'gitCommit': subprocess.check_output(['git','rev-parse','HEAD'],cwd=ROOT,text=True).strip(),
                'sourceHashes': {n:digest(ROOT/'GrandArchiveSim'/n) for n in FREEZER.FILES},
                'note': 'Candidate opponent only; no reference baseline or live policy was promoted.'}
    (output/'snapshot.json').write_text(json.dumps(metadata,indent=2)+'\n')
    return verify(output)


def evaluate_pool(pool, config, checkpoint, output, archetypes, games=40, seed=25000001, max_steps=2000, workers=2):
    pool, config, checkpoint, output = map(lambda p:Path(p).resolve(), (pool,config,checkpoint,output))
    entries=json.loads(pool.read_text())['opponents']
    if not entries or len({e['id'] for e in entries}) != len(entries):
        raise ValueError('Opponent IDs must be unique and nonempty')
    if games < 2 or games % 2 or workers < 1 or max_steps < 1 or not archetypes or len(set(archetypes)) != len(archetypes):
        raise ValueError('Use unique archetypes, positive workers/steps and positive even games')
    known={d.get('archetypeId',d['name']) for d in json.loads(config.read_text())['decks']}
    if not set(archetypes)<=known:
        raise ValueError('Unknown learner archetype')
    for entry in entries:
        if not re.fullmatch(r'[a-z0-9][a-z0-9_-]*',entry['id']):
            raise ValueError('Invalid opponent ID')
        entry['path']=str((pool.parent/entry['path']).resolve())
        entry['manifest']=verify(entry['path'])
    expected={e['id']:identity(config,e['path'],checkpoint,games,seed,max_steps) for e in entries}
    output.mkdir(parents=True,exist_ok=False)
    # Copy all frozen opponents into the result, retaining reference/candidate labels.
    for entry in entries:
        shutil.copytree(entry['path'],output/'opponents'/entry['id'])
    (output/'pool.json').write_text(json.dumps(entries,indent=2)+'\n')
    def run(job):
        index,archetype,entry=job
        dest=output/f'archetype-{index:02d}'/entry['id'];dest.parent.mkdir(exist_ok=True)
        cmd=['php',str(ROOT/'DevTools/rl/train_ga_tournament.php'),'--config',str(config),
             '--baseline',str(output/'opponents'/entry['id']),'--checkpoint',str(checkpoint),
             '--output',str(dest),'--games',str(games),'--seed',str(seed),'--max-steps',str(max_steps),
             '--learner-archetype',archetype,'--evaluate','--learner-heuristic','current']
        with dest.with_suffix('.log').open('w') as log:
            subprocess.run(cmd,cwd=ROOT,stdout=log,stderr=log,check=True)
        report=json.loads((dest/'report.json').read_text())
        return {'archetype':archetype,'opponentVersion':entry['id'],'role':entry.get('role','candidate'),
                'scheduled':games,'completed':report['completed'],'wins':report['learnerWins'],
                'report':str(dest.relative_to(output)/'report.json')}
    with ThreadPoolExecutor(max_workers=workers) as executor:
        rows=list(executor.map(run,[(i,a,e) for i,a in enumerate(archetypes) for e in entries]))
    for entry in entries:
        verify(output/'opponents'/entry['id'])
        if identity(config,entry['path'],checkpoint,games,seed,max_steps)!=expected[entry['id']]:
            raise ValueError('Evaluation inputs changed during pool run')
    result={'results':rows,'seed':seed,'gamesPerVersionAndArchetype':games,
            'note':'Equal-weight opponent versions; both seats per matchup. Descriptive evaluation only; no automatic promotion.',
            'inputIdentities':expected}
    (output/'report.json').write_text(json.dumps(result,indent=2)+'\n')
    return rows


def main():
    parser=argparse.ArgumentParser(description=__doc__);commands=parser.add_subparsers(dest='command',required=True)
    snap=commands.add_parser('snapshot');snap.add_argument('--output',required=True);snap.add_argument('--label',required=True)
    ev=commands.add_parser('evaluate')
    for name in ['pool','config','checkpoint','output']:ev.add_argument('--'+name,required=True)
    ev.add_argument('--archetype',action='append',required=True)
    for name,default in [('games',40),('seed',25000001),('max-steps',2000),('workers',2)]:ev.add_argument('--'+name,type=int,default=default)
    args=parser.parse_args()
    if args.command=='snapshot':snapshot(args.output,args.label)
    else:print(json.dumps(evaluate_pool(args.pool,args.config,args.checkpoint,args.output,args.archetype,args.games,args.seed,args.max_steps,args.workers)))

if __name__=='__main__':main()
