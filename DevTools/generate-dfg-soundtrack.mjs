// Offline asset generation only. The browser never sees the ElevenLabs key.
import {readFile,writeFile,mkdir,access} from 'node:fs/promises';
import {fileURLToPath} from 'node:url';
import {spawn} from 'node:child_process';
import path from 'node:path';
const root=fileURLToPath(new URL('../',import.meta.url));
const dir=path.join(root,'DFGSim/adventure/audio');
const manifest=JSON.parse(await readFile(path.join(dir,'generation-manifest.json'),'utf8'));
const key=process.env.ELEVENLABS_API_KEY;
if(!key)throw Error('Set ELEVENLABS_API_KEY in the environment.');
const only=process.argv.includes('--only')?process.argv[process.argv.indexOf('--only')+1]:null;
await mkdir(path.join(dir,'masters'),{recursive:true});
for(const track of manifest.tracks.filter(t=>!only||t.id===only)){
 const master=path.join(dir,'masters',track.file),target=path.join(dir,track.file),receipt=path.join(dir,track.id+'.receipt.json');
 let exists=false;try{await access(target);exists=true;}catch{}
 if(exists){console.log('skip '+track.id);continue;}
 let masterExists=false;try{await access(master);masterExists=true;}catch{}
 if(!masterExists){
  console.log('compose '+track.id+' ('+track.durationMs/1000+' seconds)');
  const response=await fetch(manifest.endpoint+'?output_format='+manifest.outputFormat,{method:'POST',headers:{'xi-api-key':key,'Content-Type':'application/json'},body:JSON.stringify({model_id:manifest.model,prompt:manifest.style+' '+track.prompt,music_length_ms:track.durationMs,force_instrumental:true}),signal:AbortSignal.timeout(300000)});
  if(!response.ok){const body=await response.json().catch(()=>({}));throw Error('ElevenLabs '+response.status+': '+JSON.stringify(body.detail??body).slice(0,600));}
  const bytes=new Uint8Array(await response.arrayBuffer());if(bytes.length<10000)throw Error('Unexpectedly short music response');
  await writeFile(master,bytes);
  await writeFile(receipt,JSON.stringify({provider:'ElevenLabs',model:manifest.model,songId:response.headers.get('song-id'),requestId:response.headers.get('request-id'),durationRequestedMs:track.durationMs,generatedAt:new Date().toISOString(),bytes:bytes.length},null,2)+'\n');
 }
 const n=manifest.normalization;
 await new Promise((resolve,reject)=>{const ff=spawn('ffmpeg',['-hide_banner','-loglevel','error','-y','-i',master,'-af',`loudnorm=I=${n.integratedLoudness}:LRA=${n.loudnessRange}:TP=${n.truePeak}`,'-ar','44100','-b:a','160k',target],{stdio:['ignore','ignore','pipe']});let error='';ff.stderr.on('data',c=>error+=String(c).slice(0,2000));ff.on('error',reject);ff.on('exit',c=>c===0?resolve():reject(Error('ffmpeg: '+error)));});
 console.log('saved '+track.id);
}
