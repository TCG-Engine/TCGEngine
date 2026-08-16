import { readFile, mkdir, access, writeFile, unlink } from 'node:fs/promises';
import { constants as fsConstants } from 'node:fs';
import { spawn } from 'node:child_process';
import path from 'node:path';
import process from 'node:process';

const repoRoot = path.resolve(path.dirname(new URL(import.meta.url).pathname.replace(/^\/(?:[A-Za-z]:)/, match => match.slice(1))), '..');
const manifestArgIndex = process.argv.indexOf('--manifest');
const manifestPath = manifestArgIndex >= 0
  ? path.resolve(repoRoot, String(process.argv[manifestArgIndex + 1] || ''))
  : path.join(repoRoot, 'AzukiSim', 'Assets', 'Sounds', 'generation-manifest.json');
const soundDir = path.dirname(manifestPath);
const manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
const apiKey = process.env.ELEVENLABS_API_KEY;

if (!apiKey) {
  throw new Error('ELEVENLABS_API_KEY is not set.');
}

const force = process.argv.includes('--force');
const onlyArgIndex = process.argv.indexOf('--only');
const only = new Set((onlyArgIndex >= 0 ? String(process.argv[onlyArgIndex + 1] || '') : '')
  .split(',')
  .map(value => value.trim())
  .filter(Boolean));
await mkdir(soundDir, { recursive: true });

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

async function exists(filePath) {
  try {
    await access(filePath, fsConstants.F_OK);
    return true;
  } catch {
    return false;
  }
}

async function normalizeAudio(rawPath, outputPath) {
  const settings = manifest.normalization || {};
  const integratedLoudness = Number(settings.integratedLoudness ?? -20);
  const loudnessRange = Number(settings.loudnessRange ?? 7);
  const truePeak = Number(settings.truePeak ?? -3);
  const filter = `loudnorm=I=${integratedLoudness}:LRA=${loudnessRange}:TP=${truePeak}`;
  await new Promise((resolve, reject) => {
    const child = spawn('ffmpeg', [
      '-hide_banner', '-loglevel', 'error', '-y', '-i', rawPath,
      '-af', filter, '-ar', String(manifest.sampleRate ?? 44100),
      '-b:a', String(manifest.deliveryBitrate ?? '192k'), outputPath
    ], { stdio: ['ignore', 'ignore', 'pipe'] });
    let errorOutput = '';
    child.stderr.on('data', chunk => { errorOutput += String(chunk).slice(0, 2000); });
    child.on('error', reject);
    child.on('exit', code => code === 0 ? resolve() : reject(new Error(`ffmpeg exited ${code}: ${errorOutput}`)));
  });
}

async function generate(sound) {
  const targetPath = path.join(soundDir, sound.file);
  if (!force && await exists(targetPath)) {
    process.stdout.write(`skip ${sound.file}\n`);
    return;
  }

  await mkdir(path.dirname(targetPath), { recursive: true });
  const promptText = `${manifest.style} ${sound.prompt}`;
  if (promptText.length > 450) {
    throw new Error(`Prompt for ${sound.file} is ${promptText.length} characters; ElevenLabs allows 450.`);
  }
  const requestBody = {
    text: promptText,
    loop: false,
    duration_seconds: sound.duration,
    prompt_influence: manifest.promptInfluence,
    model_id: manifest.model
  };

  for (let attempt = 1; attempt <= 4; ++attempt) {
    const response = await fetch(`https://api.elevenlabs.io/v1/sound-generation?output_format=${encodeURIComponent(manifest.outputFormat)}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'xi-api-key': apiKey
      },
      body: JSON.stringify(requestBody)
    });

    if (response.ok) {
      const bytes = new Uint8Array(await response.arrayBuffer());
      if (bytes.length < 256) throw new Error(`Generated audio for ${sound.file} was unexpectedly small.`);
      if (manifest.normalize === false) {
        await writeFile(targetPath, bytes);
        process.stdout.write(`generated ${sound.file} (${bytes.length} bytes, source master)\n`);
      } else {
        const rawPath = `${targetPath}.elevenlabs.mp3`;
        const normalizedPath = `${targetPath}.normalized.mp3`;
        await writeFile(rawPath, bytes);
        try {
          await normalizeAudio(rawPath, normalizedPath);
          const normalizedBytes = await readFile(normalizedPath);
          await writeFile(targetPath, normalizedBytes);
          process.stdout.write(`generated ${sound.file} (${normalizedBytes.length} bytes, normalized)\n`);
        } finally {
          await unlink(rawPath).catch(() => {});
          await unlink(normalizedPath).catch(() => {});
        }
      }
      return;
    }

    const errorText = (await response.text()).slice(0, 500);
    if ((response.status === 429 || response.status >= 500) && attempt < 4) {
      await sleep(attempt * 1500);
      continue;
    }
    throw new Error(`ElevenLabs request failed for ${sound.file}: ${response.status} ${errorText}`);
  }
}

for (const sound of manifest.sounds) {
  if (only.size && !only.has(sound.file)) continue;
  await generate(sound);
}
