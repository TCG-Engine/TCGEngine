"""Write the reviewable Kling job list for the four existing enemy sprites. No paid calls."""
import json
from pathlib import Path

root = Path(__file__).resolve().parents[1]
assets = root / 'DFGSim' / 'roguelike' / 'assets'
enemies = {
    'fox': ('Huo the Ember Fox', 'russet fox head, three flame-shaped tails, cream gi, amber sash and brown trousers'),
    'tortoise': ('Shan the Jade Tortoise', 'rounded green head, jade shell, olive gi, dark green sash and brown trousers'),
    'tengu': ('Sora the Crane Tengu', 'white crane head, long red beak, black feathered forearms, indigo gi and raised-knee stance'),
    'dragon': ('Ao the Azure Dragon', 'azure dragon head, antlers, whiskers, curved tail, navy gi, gold sash and brown trousers'),
}
motions = {
    'light': 'ONE light unseen impact: a small sharp head-and-shoulder flinch backwards, slight torso recoil and softened knees. Keep the guard close. Small recoil only.',
    'medium': 'ONE solid unseen impact: a clear backward torso recoil, shoulders jerk back and knees compress. Recover balance and raise the original guard. Stronger than a small flinch, no falling.',
    'heavy': 'ONE powerful unseen impact: a large backward torso recoil and deep knee compression. Head and shoulders snap back, guard briefly opens as balance is nearly lost. Remain standing within the original footprint, visibly gather balance and recover the original guard. No knockdown.',
    'block': 'BLOCK ONE unseen incoming strike: bring the forearms into a compact high guard in front of chest and head, brace with small knee compression and a tiny impact jolt, then return to the original guard. No injury or large recoil.',
}
jobs = []
for key, (name, identity) in enemies.items():
    preserve = f'Preserve the exact supplied {identity}, anatomy, simple cartoon silhouette, thick dark outlines, flat colors, proportions, scale and left-facing direction. No added detail or realistic textures.'
    framing = 'Locked camera and unchanged framing. Keep the complete body, head, hands, feet, tail, shell, wings and horns as applicable visible with safe margin from every canvas edge. Uniform perfectly pure black background, no scenery, shadows, text, audio, attacker, particles or effects.'
    for kind in ['idle', 'light', 'medium', 'heavy', 'block', 'knockout']:
        seconds = 5 if kind in ('idle', 'knockout') else 3
        if kind == 'idle':
            motion = 'A seamless five-second fighting-game idle breathing loop. Preserve the exact supplied ready stance. One quiet natural breath with very small ribcage expansion, tiny knee compression and body bob of about one percent of body height. Supporting feet remain planted; preserve a raised foot if present. Head and shoulders move with the torso. Forearms and fists remain fixed relative to the torso throughout, with no independent arm action. Tails, wings, shell and horns retain their shapes. Return precisely to the identical supplied last-frame pose. No flexing, punching, stepping, turning, changing guard or cloth flapping.'
        elif kind == 'knockout':
            motion = 'A single five-second fighting-game knockout. Start in the supplied left-facing guard. In the first two seconds one unseen final blow from screen-left makes the creature recoil toward screen-right, lose balance, knees buckle and collapse naturally onto its back or side on the invisible ground. Settle into a clearly defeated lying-down pose by the third second and remain motionless for the rest of the clip. DO NOT stand up or return to guard. One fall only, no rolling out of frame, repeated impacts, blood, wounds or gore. Leave room for the final horizontal body and any tail, shell or wings. Final pose stays down permanently.'
        else:
            motion = f'A single three-second fighting-game {kind}-hit reaction. Start in the supplied left-facing guard. The unseen impact arrives from screen-left; recoil goes toward screen-right. Immediately react to {motions[kind]} One brief reaction and recovery, no repeated hits or idle flexing. Return exactly to the identical supplied last-frame guard. No counterattack, punching, stepping, turning or falling. Preserve the original supporting-foot arrangement.'
        jobs.append({
            'id': f'{key}-{kind}', 'enemy': key, 'kind': kind,
            'reference': f'DFGSim/roguelike/assets/{key}-v2.png',
            'firstFrame': f'DFGSim/roguelike/assets/animations/inputs/{key}-black.png',
            'lastFrame': None if kind == 'knockout' else f'DFGSim/roguelike/assets/animations/inputs/{key}-black.png',
            'settings': {'model': 'Kling 4.0', 'mode': 'Keyframes', 'resolution': '720p', 'seconds': seconds, 'ratio': 'adaptive', 'outputs': 1},
            'estimatedCredits': seconds * 9,
            'source': f'sources/{key}-{kind}.mp4',
            'conversion': {'height': 480, 'fps': 12, 'speed': 2.5 if kind in motions else 1.5, 'loopCount': 0 if kind == 'idle' else 1},
            'prompt': f'DFG {name} {kind}. {motion} {preserve} {framing}',
        })
destination = assets / 'animation-jobs.json'
destination.write_text(json.dumps({'status': 'prepared-not-submitted', 'jobs': jobs, 'estimatedTotalCredits': sum(job['estimatedCredits'] for job in jobs)}, indent=2) + '\n', encoding='utf-8')
print(f'Prepared {len(jobs)} jobs, {sum(job["estimatedCredits"] for job in jobs)} estimated credits: {destination}')
