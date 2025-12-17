// import moveSelf from '../assets/sounds/move-self.mp3'; // You'll need these files
// import capture from '../assets/sounds/capture.mp3';
// import notify from '../assets/sounds/notify.mp3';

class SoundManager {
  constructor() {
    this.sounds = {};
    this.context = null;
    this.isInitialized = false;
  }

  // Initialize only on the first user interaction
  init() {
    if (this.isInitialized) return;

    try {
      // Modern browsers require AudioContext to be resumed after a gesture
      this.context = new (window.AudioContext || window.webkitAudioContext)();
      
      this.sounds = {
        move: new Audio(moveSelf) || new Audio(), // Fallback to empty Audio if not found
        capture: new Audio(capture) || new Audio(), // Fallback to empty Audio if not found
        notify: new Audio(notify) || new Audio(), // Fallback to empty Audio if not found
      };
      
      this.isInitialized = true;
    } catch (e) {
      console.warn("AudioContext blocked or failed:", e);
    }
  }

  play(type) {
    // 1. Check if global settings allow sound
    const settings = JSON.parse(localStorage.getItem('chess_settings') || '{}');
    if (settings.soundEnabled === false) return;

    // 2. Resume context if suspended (Browser Policy Fix)
    if (this.context && this.context.state === 'suspended') {
      this.context.resume();
    }

    // 3. Init if missing (Lazy Load)
    if (!this.isInitialized) {
      this.init();
    }

    // 4. Play Sound
    const sound = this.sounds[type];
    if (sound) {
      sound.currentTime = 0;
      sound.play().catch(err => console.warn("Audio play blocked:", err));
    }
  }
}

export const soundManager = new SoundManager();