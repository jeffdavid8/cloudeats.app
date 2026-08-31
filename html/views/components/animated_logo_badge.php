<style>
  /* =========================
     Configurable variables
     ========================= */
  :root{
    --bg-1: #030317;             /* deep background base */
    --bg-2: #071129;             /* gradient end */
    --mb-primary: #04c7ff;       /* waveform core */
    --mb-accent: #0aa3ff;        /* accent glow */
    --mb-outline: rgba(4,199,255,0.12);
    --mb-text: #eaf6ff;
    --max-width: 680px;

    --dur-outline: 1.1s;
    --dur-wave: 1.6s;
    --dur-ripple: 0.95s;
    --dur-idle: 3.0s;
    --delay-wave: calc(var(--dur-outline) + 0s);
    --delay-ripple: calc(var(--dur-outline) + var(--dur-wave) - 0.15s);
    --delay-idle: calc(var(--delay-ripple) + var(--dur-ripple));
  }

  /* =========================
     Page layout
     ========================= */
  html,body{height:100%;margin:0;background: linear-gradient(180deg,var(--bg-1),var(--bg-2)); font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial;}
  .wrap{
    min-height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:36px;
    box-sizing:border-box;
  }
  .card{
    width:100%;
    max-width:var(--max-width);
    text-align:center;
    padding:28px;
    border-radius:14px;
    background: linear-gradient(180deg, rgba(255,255,255,0.018), rgba(255,255,255,0.01));
    box-shadow: 0 8px 40px rgba(2,8,20,0.48), inset 0 1px 0 rgba(255,255,255,0.02);
    backdrop-filter: blur(6px) saturate(120%);
    position:relative;
    overflow:visible;
  }

  /* small decorative vignette */
  .card::before{
    content:"";
    position:absolute; inset: -40% -40% auto -40%; height:220%; pointer-events:none;
    background: radial-gradient(60% 40% at 50% 20%, rgba(10,160,255,0.06), transparent 18%),
                radial-gradient(50% 30% at 10% 80%, rgba(5,100,160,0.03), transparent 22%);
    mix-blend-mode:screen;
    z-index:0;
  }

  /* wordmark */
  .mb-wordmark{
    font-weight:700;
    color:var(--mb-text);
    margin:18px 0 4px;
    letter-spacing:1px;
    font-size: clamp(20px, 3.4vw, 30px);
  }
  .mb-tag{
    margin:0 0 8px;
    color: rgba(234,246,255,0.72);
    font-size: clamp(12px,1.8vw,14px);
  }

  /* =========================
     SVG container
     ========================= */
  .logo-wrap{
    position:relative;
    width:100%;
    max-width:520px;
    margin: 6px auto 0;
    z-index:1;
    cursor:pointer;
  }
  svg.mb {
    width:100%;
    height:auto;
    display:block;
  }

  /* =========================
     Particle layer (subtle)
     ========================= */
  .particles {
    position:absolute;
    inset:0;
    pointer-events:none;
    z-index:0;
    overflow:hidden;
    mix-blend-mode: screen;
  }
  .particle {
    position:absolute;
    width:6px;height:6px;border-radius:50%;
    background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.95), rgba(255,255,255,0.25));
    opacity:0; transform: translate3d(0,0,0) scale(0.8);
    filter: blur(1px);
    box-shadow: 0 0 8px rgba(10,160,255,0.09);
    will-change: transform, opacity;
  }

  /* =========================
     SVG animation styles
     ========================= */
  /* Outline draw */
  .delta-outline {
    stroke: var(--mb-outline);
    stroke-width: 6;
    fill:none;
    stroke-linecap:round; stroke-linejoin:round;
    stroke-dasharray: 1200;
    stroke-dashoffset: 1200;
    filter: drop-shadow(0 10px 30px rgba(2,12,30,0.45));
    animation: outline-draw var(--dur-outline) cubic-bezier(.2,.9,.3,1) forwards;
  }
  @keyframes outline-draw { to { stroke-dashoffset: 0; } }

  /* Wave initial (left->right sweep) */
  .wave-init {
    stroke: var(--mb-primary);
    stroke-width: 6;
    stroke-linecap:round;
    stroke-linejoin:round;
    fill:none;
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
    filter: url(#glow-soft);
    animation: wave-in var(--dur-wave) cubic-bezier(.2,.9,.3,1) var(--delay-wave) forwards;
    opacity:1;
  }
  @keyframes wave-in { to { stroke-dashoffset: 0; } }

  /* Idle waveform - subtle breathing */
  .wave-idle {
    stroke: var(--mb-primary);
    stroke-width: 6;
    stroke-linecap:round;
    stroke-linejoin:round;
    fill:none;
    opacity:0.92;
    filter: url(#glow-soft);
    transform-origin:50% 50%;
    animation: wave-breath var(--dur-idle) ease-in-out var(--delay-idle) infinite;
    will-change: opacity, transform, filter;
  }
  @keyframes wave-breath {
    0% { opacity:.85; transform: translateY(0) scale(1); filter: drop-shadow(0 8px 20px rgba(10,160,255,0.06)); }
    50% { opacity:1; transform: translateY(-1.8px) scale(1.0025); filter: drop-shadow(0 20px 40px rgba(4,199,255,0.12)); }
    100%{ opacity:.85; transform: translateY(0) scale(1); }
  }

  /* ripple lock */
  .ripple {
    fill:none; stroke:var(--mb-accent); stroke-width:3; opacity:0; transform-origin:50% 50%;
    animation: ripple-anim var(--dur-ripple) ease var(--delay-ripple) forwards;
    filter: blur(8px);
  }
  @keyframes ripple-anim {
    0% { transform: scale(.85); opacity:.7; stroke-width:6; }
    100% { transform: scale(1.9); opacity:0; stroke-width:0.6; }
  }

  /* subtle glow outline after lock */
  .glow-outline {
    stroke: var(--mb-accent);
    stroke-width: 5;
    fill:none;
    opacity:0;
    filter: blur(10px);
    animation: glow-on .8s ease var(--delay-idle) forwards;
  }
  @keyframes glow-on { from { opacity:0; stroke-width:0 } to { opacity:.85; stroke-width:6 } }

  /* =========================
     reduced motion
     ========================= */
  @media (prefers-reduced-motion: reduce) {
    .delta-outline, .wave-init, .wave-idle, .ripple, .glow-outline { animation: none !important; stroke-dashoffset: 0 !important; opacity:1 !important; transform:none !important; }
    .particle { animation: none !important; opacity: 0.08 !important; transform: none !important; }
  }

  /* small screens */
  @media (max-width:520px){
    :root{ --max-width:420px; }
    .card{ padding:18px; border-radius:10px; }
  }

  /* =========================
     micro instructions
     ========================= */
  .hint{font-size:12px;color:rgba(255,255,255,0.22);margin-top:8px}
  .controls{display:flex;gap:8px;justify-content:center;margin-top:12px}
  .btn{
    background:transparent;border:1px solid rgba(255,255,255,0.06);color:rgba(255,255,255,0.9);
    padding:8px 12px;border-radius:10px;font-size:13px;cursor:pointer;
    transition:all .18s ease;
  }
  .btn:hover{transform:translateY(-2px); box-shadow: 0 8px 18px rgba(2,12,30,0.35);}
  .btn:active{transform:translateY(0);}

</style>
</head>
<body>
  <div class="wrap">
    <div class="card" role="region" aria-label="MediaBrain animated logo demo">
      <div class="logo-wrap" id="logoWrap" title="Click to replay logo animation" aria-hidden="false" role="img" aria-label="MediaBrain logo animation">
        <!-- Particles layer -->
        <div class="particles" id="particles" aria-hidden="true"></div>

        <!-- SVG Animated Logo -->
        <svg class="mb" viewBox="0 0 420 420" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
          <defs>
            <!-- Glow filter -->
            <filter id="glow-soft" x="-50%" y="-50%" width="200%" height="200%">
              <feGaussianBlur stdDeviation="6" result="coloredBlur"/>
              <feMerge>
                <feMergeNode in="coloredBlur"/>
                <feMergeNode in="SourceGraphic"/>
              </feMerge>
            </filter>

            <!-- subtle multi-pass glow for extra depth -->
            <filter id="multi-glow" x="-60%" y="-60%" width="220%" height="220%">
              <feGaussianBlur stdDeviation="3" result="g1"/>
              <feGaussianBlur stdDeviation="7" result="g2"/>
              <feMerge>
                <feMergeNode in="g2"/>
                <feMergeNode in="SourceGraphic"/>
              </feMerge>
            </filter>
          </defs>

          <!-- group scaled for comfortable padding -->
          <g transform="translate(30,18) scale(0.9)">
            <!-- delta outline - drawn path -->
            <path class="delta-outline"
                  d="M70,360
                     C70,360 30,260 80,190
                     C120,130 220,60 310,190
                     C360,260 320,360 320,360
                     Z"/>

            <!-- inner waveform initial sweep -->
            <path class="wave-init"
                  d="M86,254
                     C120,254 120,180 154,180
                     C180,180 186,212 212,212
                     C238,212 248,176 276,176"
                  stroke-linecap="round"/>

            <!-- idle waveform (same path) - starts looping after sequence -->
            <path class="wave-idle"
                  d="M86,254
                     C120,254 120,180 154,180
                     C180,180 186,212 212,212
                     C238,212 248,176 276,176"
                  stroke-linecap="round"/>

            <!-- ripple lock -->
            <ellipse class="ripple" cx="200" cy="210" rx="60" ry="48"/>

            <!-- subtle glow outline after lock -->
            <path class="glow-outline"
                  d="M70,360
                     C70,360 30,260 80,190
                     C120,130 220,60 310,190
                     C360,260 320,360 320,360
                     Z"/>
          </g>
        </svg>
      </div>

      <h1 class="mb-wordmark">MediaBrain</h1>
      <p class="mb-tag">Intelligence in Motion</p>

      <div class="controls" aria-hidden="true">
        <button class="btn" id="replayBtn" title="Replay animation">Replay</button>
        <button class="btn" id="toggleLoop" title="Toggle loop">Toggle loop</button>
      </div>
      <p class="hint">Tip: click the logo to replay. Reduced motion respected for accessibility.</p>
    </div>
  </div>

<script>
/* Small JS to add particles and control replay/looping.
   No external libs. Keeps animations accessible and replayable.
*/
(function(){
  const wrap = document.getElementById('logoWrap');
  const particlesContainer = document.getElementById('particles');
  const replayBtn = document.getElementById('replayBtn');
  const toggleLoop = document.getElementById('toggleLoop');
  const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Add a few subtle particles for depth (no particles if reduced motion)
  function spawnParticles(count = 10){
    if(prefersReduced) return;
    const box = wrap.getBoundingClientRect();
    particlesContainer.innerHTML = '';
    for(let i=0;i<count;i++){
      const el = document.createElement('div');
      el.className = 'particle';
      // random pos within container
      const left = Math.random()*100;
      const top = Math.random()*100;
      el.style.left = left + '%';
      el.style.top = top + '%';
      const delay = (Math.random()*1.2).toFixed(2) + 's';
      const dur = (1.8 + Math.random()*1.8).toFixed(2) + 's';
      el.style.opacity = 0;
      // animate via keyframes using Web Animations API for performance
      el.animate([
        { transform:'translateY(0) scale(.8)', opacity:0 },
        { transform:`translateY(-${6 + Math.random()*18}px) scale(1)`, opacity:0.9 },
        { transform:'translateY(0) scale(.8)', opacity:0 }
      ], { delay: parseFloat(delay)*1000, duration: dur*1000, iterations: Infinity, easing: 'ease-in-out' });
      particlesContainer.appendChild(el);
    }
  }

  // Replay animations by forcing reflow and toggling classes/inline style
  function replay(){
    if(prefersReduced) return;
    // reset by cloning and replacing SVG
    const svg = wrap.querySelector('svg.mb');
    const clone = svg.cloneNode(true);
    svg.parentNode.replaceChild(clone, svg);
    // respawn particles (restart)
    spawnParticles(12);
  }

  // toggle loop: adds/removes 'wave-idle' animation by toggling a data attribute
  let loopEnabled = true;
  function toggleLooping(){
    loopEnabled = !loopEnabled;
    const idle = wrap.querySelector('.wave-idle');
    if(!idle) return;
    if(loopEnabled){
      idle.style.animationPlayState = '';
    } else {
      idle.style.animationPlayState = 'paused';
    }
    toggleLoop.textContent = loopEnabled ? 'Toggle loop' : 'Loop paused';
  }

  // click handlers
  wrap.addEventListener('click', ()=>{ replay(); });
  replayBtn.addEventListener('click', ()=>{ replay(); });
  toggleLoop.addEventListener('click', ()=>{ toggleLooping(); });

  // initial spawn
  spawnParticles(12);

  // auto-replay on first load to ensure sequence plays (unless user requested reduced motion)
  if(!prefersReduced){
    // ensure the initial outline-draw and wave-in run from the start (they auto-run via CSS on load).
    // Nothing extra needed, but ensure particles show after a short delay so they don't clash.
    setTimeout(()=> spawnParticles(12), 800);
  }

  // resize: respawn particles for new layout
  let resizeTimer;
  window.addEventListener('resize', ()=> {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(()=> spawnParticles(10), 300);
  });
})();
</script>