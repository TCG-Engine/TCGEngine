<?php /* zzButtonTest.php — THROWAWAY Clarent (GrandArchiveSim) interface style study.
   "Dante's Inferno meets cyberpunk" on the existing navy+gold Clarent palette. Serve at
   /TCGEngine/zzButtonTest.php. Self-contained (only the shared clarent.webp background). Delete when done. */ ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Clarent — Button Study</title>
<style>
:root{
  /* Existing Clarent palette (from ClarentMenuStyles.css) — kept intact. */
  --navy-0:#081321; --navy-1:#11233a; --navy-2:#203b5d; --navy-hi:#2b4f7c;
  --gold:#c9a84c; --gold-hi:#f4e2a4; --gold-deep:#d6b86d; --cream:#f4ead1;
  /* Hybrid accents — molten ember (inferno) + a whisper of neon (cyber). */
  --ember:#e8933a; --ember-hot:#ff6a2b; --cyber:#57d7ff;
  --ui:"Bahnschrift","Segoe UI Variable","Trebuchet MS",sans-serif;
  /* Seamless gold honeycomb (Hero Patterns "hexagons") + cyan circuit variant. */
  --hex-gold:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='49' viewBox='0 0 28 49'%3E%3Cg fill='none' stroke='%23c9a84c' stroke-opacity='0.5'%3E%3Cpath d='M13.99 9.25l13 7.5v15l-13 7.5L1 31.75v-15l12.99-7.5zM3 17.9v12.7l11 6.35 11-6.35V17.9l-11-6.34L3 17.9zM0 15l12.98-7.5V0h-2v6.35L0 12.69v2.3zm0 18.5L12.98 41v8h-2v-6.85L0 35.81v-2.3zM15 0v7.5L27.99 15H28v-2.31h-.01L17 6.35V0h-2zm0 49v-8l12.99-7.5H28v2.31h-.01L17 42.15V49h-2z'/%3E%3C/g%3E%3C/svg%3E");
  --hex-cyan:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='49' viewBox='0 0 28 49'%3E%3Cg fill='none' stroke='%2357d7ff' stroke-opacity='0.5'%3E%3Cpath d='M13.99 9.25l13 7.5v15l-13 7.5L1 31.75v-15l12.99-7.5zM3 17.9v12.7l11 6.35 11-6.35V17.9l-11-6.34L3 17.9zM0 15l12.98-7.5V0h-2v6.35L0 12.69v2.3zm0 18.5L12.98 41v8h-2v-6.85L0 35.81v-2.3zM15 0v7.5L27.99 15H28v-2.31h-.01L17 6.35V0h-2zm0 49v-8l12.99-7.5H28v2.31h-.01L17 42.15V49h-2z'/%3E%3C/g%3E%3C/svg%3E");
  --scan:repeating-linear-gradient(0deg, rgba(0,0,0,0.16) 0 1px, transparent 1px 3px);
}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;color:var(--cream);font-family:var(--ui);
  background:#081321 url('/TCGEngine/Assets/Backgrounds/clarent.webp') center/cover fixed no-repeat;}
.wrap{min-height:100vh;background:linear-gradient(180deg, rgba(6,14,26,0.72), rgba(4,10,20,0.86));padding:38px 30px 90px;}
h1{margin:0 0 2px;font-size:26px;letter-spacing:0.18em;text-transform:uppercase;color:var(--gold-hi);
   text-shadow:0 0 18px rgba(232,147,58,0.35),0 2px 0 rgba(0,0,0,0.6);}
.sub{margin:0 0 26px;color:rgba(214,184,109,0.8);font-size:13px;letter-spacing:0.06em;max-width:760px;line-height:1.5;}
.swatches{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 34px;}
.sw{width:74px;height:46px;border-radius:6px;border:1px solid rgba(255,255,255,0.12);display:flex;align-items:flex-end;
    font-size:9.5px;letter-spacing:0.04em;padding:4px 5px;color:#fff;text-shadow:0 1px 2px #000;}
.card{background:rgba(9,19,33,0.6);border:1px solid rgba(201,168,76,0.22);border-radius:12px;padding:24px 26px 30px;
      margin:0 0 26px;box-shadow:0 16px 44px rgba(0,0,0,0.4);backdrop-filter:blur(2px);}
.card h2{margin:0 0 3px;font-size:17px;letter-spacing:0.13em;text-transform:uppercase;color:var(--gold-hi);}
.card .desc{margin:0 0 20px;font-size:12.5px;color:rgba(224,214,190,0.72);letter-spacing:0.03em;line-height:1.55;max-width:720px;}
.row{display:flex;gap:16px;align-items:center;flex-wrap:wrap;}
.tag{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:rgba(201,168,76,0.55);width:100%;margin:14px 0 -2px;}

/* ============================================================= *
 *  Shared button skeleton — chamfered double-layer (rim + fill)  *
 * ============================================================= */
.cbtn{position:relative;z-index:0;isolation:isolate;border:0;background:transparent;cursor:pointer;
  padding:13px 30px;font-family:var(--ui);font-weight:700;font-size:15px;letter-spacing:0.11em;text-transform:uppercase;
  color:var(--cream);text-shadow:0 1px 2px rgba(0,0,0,0.6);transition:transform .12s, filter .2s, color .2s;}
.cbtn:active{transform:translateY(1px);}
.cbtn[disabled]{cursor:default;opacity:.42;filter:saturate(.4);}

/* ---------- V1 · MOLTEN FORGE — chamfer + rising ember + honeycomb ---------- */
.v1{--cut:14px;}
.v1::before{content:'';position:absolute;inset:0;z-index:-2;
  background:linear-gradient(135deg,var(--gold-hi),var(--gold) 40%,var(--gold-deep));
  clip-path:polygon(var(--cut) 0,100% 0,100% calc(100% - var(--cut)),calc(100% - var(--cut)) 100%,0 100%,0 var(--cut));
  filter:drop-shadow(0 0 6px rgba(201,168,76,0.35));}
.v1::after{content:'';position:absolute;inset:2px;z-index:-1;
  background:
    var(--scan),
    linear-gradient(0deg, rgba(232,147,58,0.42), rgba(232,147,58,0.05) 24%, transparent 46%),
    var(--hex-gold),
    linear-gradient(135deg,var(--navy-2),var(--navy-1) 55%,#0c1a2c);
  background-blend-mode:overlay,normal,overlay,normal;
  clip-path:polygon(12px 0,100% 0,100% calc(100% - 12px),calc(100% - 12px) 100%,0 100%,0 12px);}
.v1:hover{color:#fff;filter:drop-shadow(0 0 14px rgba(255,106,43,0.5));}
.v1:hover::before{background:linear-gradient(135deg,#fff6dd,var(--gold-hi) 45%,var(--ember));}
.v1.primary::after{background:
    var(--scan),
    linear-gradient(0deg, rgba(255,106,43,0.55), rgba(232,147,58,0.12) 30%, transparent 52%),
    var(--hex-gold),
    linear-gradient(135deg,#2a3c52,#1a2a40 55%,#0e1c2e);
  background-blend-mode:overlay,normal,overlay,normal;}
.v1.primary{animation:emberpulse 2.6s ease-in-out infinite;}
@keyframes emberpulse{0%,100%{filter:drop-shadow(0 0 5px rgba(232,147,58,0.35));}50%{filter:drop-shadow(0 0 15px rgba(255,106,43,0.55));}}

/* ---------- V2 · CIRCUIT SIGIL — octagonal HUD panel + hex/cyan traces ---------- */
.v2{--cut:10px;}
.v2::before{content:'';position:absolute;inset:0;z-index:-2;background:linear-gradient(180deg,var(--gold),var(--gold-deep));
  clip-path:polygon(var(--cut) 0,calc(100% - var(--cut)) 0,100% var(--cut),100% calc(100% - var(--cut)),calc(100% - var(--cut)) 100%,var(--cut) 100%,0 calc(100% - var(--cut)),0 var(--cut));}
.v2::after{content:'';position:absolute;inset:1.6px;z-index:-1;
  background:
    var(--hex-cyan),
    linear-gradient(180deg, rgba(87,215,255,0.10), transparent 40%),
    linear-gradient(160deg,var(--navy-1),#0b1a2c);
  background-blend-mode:screen,normal,normal;background-size:22px,auto,auto;
  clip-path:polygon(9px 0,calc(100% - 9px) 0,100% 9px,100% calc(100% - 9px),calc(100% - 9px) 100%,9px 100%,0 calc(100% - 9px),0 9px);
  box-shadow:inset 0 0 0 1px rgba(87,215,255,0.22), inset 0 0 16px rgba(9,19,33,0.7);}
.v2{color:var(--gold-hi);text-shadow:0 0 8px rgba(87,215,255,0.25);}
.v2:hover{color:#fff;filter:drop-shadow(0 0 12px rgba(87,215,255,0.4));}
.v2:hover::after{box-shadow:inset 0 0 0 1px rgba(87,215,255,0.55), inset 0 0 18px rgba(9,19,33,0.5);}
.v2.primary::before{background:linear-gradient(180deg,var(--gold-hi),var(--ember));}
.v2.primary::after{background:
    var(--hex-gold),
    linear-gradient(180deg, rgba(232,147,58,0.28), transparent 45%),
    linear-gradient(160deg,#20344c,#0c1c30);
  background-blend-mode:overlay,normal,normal;background-size:22px,auto,auto;}

/* ---------- V3 · INFERNAL EDGE — slashed corner + molten seam + scanlines ---------- */
.v3{--slash:18px;}
.v3::before{content:'';position:absolute;inset:0;z-index:-2;background:linear-gradient(120deg,var(--gold-deep),var(--gold-hi));
  clip-path:polygon(0 0,calc(100% - var(--slash)) 0,100% var(--slash),100% 100%,var(--slash) 100%,0 calc(100% - var(--slash)));
  filter:drop-shadow(0 0 8px rgba(232,147,58,0.4));}
.v3::after{content:'';position:absolute;inset:2px;z-index:-1;
  background:
    var(--scan),
    linear-gradient(90deg, transparent 14%, rgba(255,106,43,0.55) 20%, rgba(255,180,90,0.85) 21.5%, rgba(255,106,43,0.5) 23%, transparent 30%),
    linear-gradient(120deg,#14263c,#0a1728 60%);
  background-blend-mode:overlay,screen,normal;
  clip-path:polygon(0 0,calc(100% - 16px) 0,100% 16px,100% 100%,16px 100%,0 calc(100% - 16px));}
.v3{color:var(--gold-hi);}
.v3:hover{color:#fff;filter:drop-shadow(0 0 16px rgba(255,106,43,0.6));}
.v3:hover::after{background:
    var(--scan),
    linear-gradient(90deg, transparent 12%, rgba(255,130,60,0.8) 19%, #ffd89a 21.5%, rgba(255,130,60,0.7) 24%, transparent 32%),
    linear-gradient(120deg,#1a2e46,#0a1728 60%);
  background-blend-mode:overlay,screen,normal;}
.v3.primary::after{background:
    var(--scan),
    linear-gradient(90deg, rgba(255,106,43,0.4) 0%, rgba(255,180,90,0.9) 22%, rgba(255,106,43,0.35) 40%, transparent 62%),
    linear-gradient(120deg,#241a14,#120d0a 60%);
  background-blend-mode:overlay,screen,normal;}
</style></head>
<body><div class="wrap">
  <h1>Clarent Interface — Button Study</h1>
  <p class="sub">Three treatments of "Dante's Inferno meets cyberpunk" on the existing Clarent palette (navy + gold + cream — untouched). Molten-ember gold reads as infernal fire; chamfers, honeycomb circuitry and a whisper of neon cyan read as cyber. Hover each to see the heat/glow response. Pick a direction and I'll build it into the full ClarentMenuStyles guide.</p>

  <div class="swatches">
    <div class="sw" style="background:#081321">#081321</div>
    <div class="sw" style="background:#11233a">#11233a</div>
    <div class="sw" style="background:#203b5d">#203b5d</div>
    <div class="sw" style="background:#c9a84c;color:#221a08">#c9a84c gold</div>
    <div class="sw" style="background:#f4ead1;color:#221a08">#f4ead1 cream</div>
    <div class="sw" style="background:#e8933a;color:#221a08">#e8933a ember</div>
    <div class="sw" style="background:#ff6a2b;color:#221a08">#ff6a2b hot</div>
    <div class="sw" style="background:#57d7ff;color:#08202b">#57d7ff cyber</div>
  </div>

  <div class="card">
    <h2>V1 · Molten Forge</h2>
    <p class="desc">Chamfered (cut) corners for a forged-plate silhouette. A honeycomb circuit texture is embossed into the navy body; molten ember rises from the base like heat off a blade fresh from the forge. The gold rim glows hotter on hover; the <b>primary</b> action breathes with a slow ember pulse.</p>
    <div class="row">
      <button class="cbtn v1 primary">Join Queue</button>
      <button class="cbtn v1">Create Private Game</button>
      <button class="cbtn v1">Start Goldfish</button>
      <button class="cbtn v1" disabled>Disabled</button>
    </div>
  </div>

  <div class="card">
    <h2>V2 · Circuit Sigil</h2>
    <p class="desc">Octagonal HUD panel — all four corners clipped like a cyberdeck key. Cyan honeycomb traces glow through the navy fill behind a thin neon inner-frame; the <b>primary</b> swaps its neon for molten gold, tipping the same shape from cyber toward inferno.</p>
    <div class="row">
      <button class="cbtn v2 primary">Join Queue</button>
      <button class="cbtn v2">Create Private Game</button>
      <button class="cbtn v2">Start Goldfish</button>
      <button class="cbtn v2" disabled>Disabled</button>
    </div>
  </div>

  <div class="card">
    <h2>V3 · Infernal Edge</h2>
    <p class="desc">Aggressive slashed corners (top-right / bottom-left) with a molten seam of lava cracked through the plate and fine scanlines over the top. The most overtly "inferno" of the three; the seam flares white-hot on hover.</p>
    <div class="row">
      <button class="cbtn v3 primary">Join Queue</button>
      <button class="cbtn v3">Create Private Game</button>
      <button class="cbtn v3">Start Goldfish</button>
      <button class="cbtn v3" disabled>Disabled</button>
    </div>
  </div>

</div></body></html>
