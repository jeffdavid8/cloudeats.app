// 🧠 The Singleton Audio Brain
function mp3(filename, callback = null) {
  nh.audio.mp3(filename, callback);
}

// 🧠 Create the context, but we won't assume it's awake yet
let audioCtx = new (window.AudioContext || window.webkitAudioContext)();
nh.audio = {
  init: () => {
    if (mb.storage.apps.neighborhub.preferences.mute_audio) return null;
    if (!audioCtx) {
      audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (audioCtx.state === "suspended") {
      audioCtx.resume();
    }
    return audioCtx;
  },
  mp3: (filename, cb = null) => {
    if (mb.storage.apps.neighborhub.preferences.mute_audio) return null;
    const masterVol = nh.audio.getMasterVolume();
    mb.audio({
      source: filename,
      volume: masterVol,
      callback: cb,
    });
  },
  getMasterVolume: () => {
    return mb.storage.apps.neighborhub.preferences.audio_volume ?? 0.5;
  },

  // 🖖 THE REFINED LCARS CHIRP (Layered & Warm)
  lcars_access: () => {
    const ctx = nh.audio.init();
    if (!ctx) return;
    const now = ctx.currentTime;
    const masterVol = nh.audio.getMasterVolume();

    const playTrekTone = (freq, start, duration, slideTo) => {
      // LAYER 1: The Main Hollow Tone
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();

      osc.type = "triangle";
      osc.frequency.setValueAtTime(freq, start);
      // 🚀 THE SLIDE: This is the 'chirp' secret!
      osc.frequency.exponentialRampToValueAtTime(
        slideTo || freq * 1.05,
        start + duration,
      );

      gain.gain.setValueAtTime(0, start);
      gain.gain.linearRampToValueAtTime(0.15 * masterVol, start + 0.005);
      gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);

      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(start);
      osc.stop(start + duration);

      // LAYER 2: The "Thud" (A low-frequency percussive hit)
      const thud = ctx.createOscillator();
      const thudGain = ctx.createGain();
      thud.type = "sine";
      thud.frequency.setValueAtTime(freq / 2, start); // One octave lower
      thudGain.gain.setValueAtTime(0.1 * masterVol, start);
      thudGain.gain.exponentialRampToValueAtTime(0.0001, start + 0.05);
      thud.connect(thudGain);
      thudGain.connect(ctx.destination);
      thud.start(start);
      thud.stop(start + 0.05);
    };

    // Frequencies adjusted to match the "Tweedle" in your MP3
    playTrekTone(440, now, 0.08, 460);
    playTrekTone(659, now + 0.06, 0.1, 680);
  },

  lcars_stream: (freq) => {
    const ctx = nh.audio.init();
    if (!ctx) return;
    const now = ctx.currentTime;
    const masterVol = nh.audio.getMasterVolume();
    const safeFreq = typeof freq === "number" && isFinite(freq) ? freq : 300;

    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    const filter = ctx.createBiquadFilter();

    osc.type = "triangle";
    osc.frequency.setValueAtTime(safeFreq, now);
    // Quick downward chirp for the data stream
    osc.frequency.exponentialRampToValueAtTime(safeFreq * 0.8, now + 0.05);

    filter.type = "lowpass";
    filter.frequency.setValueAtTime(1000, now); // Slightly more muffled

    gain.gain.setValueAtTime(0.06 * masterVol, now);
    gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.05);

    osc.connect(filter);
    filter.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(now + 0.05);
  },

  // 🕸️ THE NEURAL LINK (Sliding Tone)
  link: () => {
    const ctx = nh.audio.init();
    if (!ctx) return;
    const now = ctx.currentTime;
    const masterVol = nh.audio.getMasterVolume();

    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.frequency.setValueAtTime(440, now);
    osc.frequency.exponentialRampToValueAtTime(880, now + 0.1);

    gain.gain.setValueAtTime(0, now);
    gain.gain.linearRampToValueAtTime(0.08 * masterVol, now + 0.01);
    gain.gain.linearRampToValueAtTime(0, now + 0.1);

    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(now + 0.1);
  },
};

function playSyncSequence() {
  let count = 0;
  const trekDataFreqs = [360, 440, 520, 660];

  nh.audio.lcars_access();

  const interval = setInterval(() => {
    // 🎲 Random frequency but forced into Trek harmonics
    const baseFreq =
      trekDataFreqs[Math.floor(Math.random() * trekDataFreqs.length)];

    // 🛰️ Occasional high "ping" just like the MP3
    if (Math.random() > 0.8) {
      nh.audio.lcars_stream(baseFreq * 2);
    } else {
      nh.audio.lcars_stream(baseFreq);
    }

    count++;
    if (count > 25) {
      clearInterval(interval);
      setTimeout(() => {
        nh.audio.lcars_access();
      }, 200);
    }
  }, 45); // Faster (45ms) matches the "jitter" of your source file better
}