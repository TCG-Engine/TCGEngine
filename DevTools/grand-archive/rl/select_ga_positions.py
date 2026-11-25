"""Shortlist replay decisions using recorded player observations, never private state."""
import argparse
import hashlib
import json
from pathlib import Path


def select_positions(paths, handlers, limit=10):
    positions = []
    seen = set()
    for path in sorted(map(Path, paths)):
        raw = path.read_bytes()
        replay_hash = hashlib.sha256(raw).hexdigest()
        if replay_hash in seen:
            continue
        seen.add(replay_hash)
        replay = json.loads(raw)
        if not replay.get('initialSnapshotHash'):
            continue  # The continuation worker cannot verify older captures.
        for action in replay.get('actions', []):
            observation = action.get('observation')
            if not observation:
                continue
            features = json.loads(observation)
            matched = [key[8:] for key in features if key.startswith('handler:')
                       and any(handler in key[8:] for handler in handlers)]
            keys = list(dict.fromkeys(action.get('actionKeys', [])))
            if not matched or len(keys) < 2:
                continue
            hp = features.get('ownHP')
            low_life = isinstance(hp, (int, float)) and hp <= 5 / 30
            reasons = []
            if low_life:
                reasons.append('remaining life at most five')
            if features.get('combatLethal'):
                reasons.append('recorded lethal combat pressure')
            if len(keys) >= 3:
                reasons.append('multiple alternatives beyond a binary choice')
            chosen = json.loads(action['actionKey'])
            declined = chosen[2] in ('NO', 'PASS')
            if declined:
                reasons.append('recorded policy declined')
            score = 100 * low_life + 100 * bool(features.get('combatLethal')) + min(len(keys)-2, 10) + 10 * declined
            positions.append({'replay': str(path), 'replayHash': replay_hash,
                              'step': action['step'], 'seat': action['turnPlayer'],
                              'handlers': matched, 'ownHPFeature': hp,
                              'actionKeys': keys, 'recordedChoice': action['actionKey'],
                              'publicGraveyardChoices': action.get('publicGraveyardChoices', {}),
                              'priority': score, 'reasons': reasons,
                              'qualityLabel': 'unreviewed'})
    return sorted(positions, key=lambda p: (-p['priority'], p['replay'], p['step']))[:limit]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('directory', type=Path)
    parser.add_argument('--handlers', nargs='+', default=['KindleProcess', 'RedHareDiscard', 'ek7r2d7uz4:0:CardActivated'])
    parser.add_argument('--limit', type=int, default=10)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    if args.limit < 1:
        parser.error('--limit must be positive')
    result = {'positions': select_positions(args.directory.rglob('game-*.json'), args.handlers, args.limit),
              'note': 'Review priority only, not action quality. Uses recorded public/player observations. Adjacent steps may belong to the same decision sequence.'}
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(result, indent=2) + '\n')
    print(json.dumps({'positions': len(result['positions'])}))


if __name__ == '__main__':
    main()
