/**
 * Sintetizador de Voz Natural Femenina para Turnero TV
 * Integración Híbrida:
 * 1. Audio Stream HD (Voz Femenina Natural Agradable y Fluida vía /api/tts-voice)
 *    Compatible con 100% de Smart TVs (Challenger, Samsung, LG, Kalley, Android TV, etc.)
 * 2. Chime Sonoro Institucional "Ding-Dong" (AudioContext)
 * 3. Fallback a Web Speech API si no hay conexión a internet.
 */

class TurneroSpeech {
    constructor() {
        this.synth = window.speechSynthesis || null;
        this.voices = [];
        this.audioCtx = null;
        this.currentAudio = null;
        this.audioUnlocked = false;
        this.initVoices();
    }

    initVoices() {
        if (!this.synth) return;
        try {
            this.voices = this.synth.getVoices();
            if (this.synth.onvoiceschanged !== undefined) {
                this.synth.onvoiceschanged = () => {
                    this.voices = this.synth.getVoices();
                };
            }
        } catch (e) {
            console.warn("SpeechSynthesis init error:", e);
        }
    }

    /**
     * Desbloquea el audio en navegadores de Smart TV tras la primera interacción
     */
    unlockAudio() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!this.audioCtx && AudioContext) {
                this.audioCtx = new AudioContext();
            }
            if (this.audioCtx && this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }
            // Pequeño dummy sound para desbloquear política de reproducción en Smart TVs
            if (!this.audioUnlocked) {
                const dummy = new Audio('data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA');
                dummy.play().then(() => {
                    this.audioUnlocked = true;
                }).catch(() => {});
            }
        } catch (e) {
            console.warn("Audio unlock:", e);
        }
    }

    selectBestVoice() {
        if (!this.voices || this.voices.length === 0) {
            if (this.synth) this.voices = this.synth.getVoices();
        }

        const filters = [
            v => (v.name.toLowerCase().includes('google') || v.name.toLowerCase().includes('natural') || v.name.toLowerCase().includes('neural')) && (v.lang === 'es-CO' || v.lang === 'es-419' || v.lang === 'es-MX' || v.lang === 'es-ES'),
            v => v.lang === 'es-CO' || v.lang === 'es_CO',
            v => v.lang === 'es-419',
            v => v.lang.startsWith('es-MX') || v.lang.startsWith('es-ES'),
            v => (v.name.includes('Sabina') || v.name.includes('Dalia') || v.name.includes('Helena') || v.name.includes('Laura') || v.name.includes('Salome') || v.name.includes('Paulina') || v.name.includes('Lupe') || v.name.includes('Mia')),
            v => v.lang.startsWith('es') && !v.lang.toLowerCase().includes('us'),
            v => v.lang.startsWith('es')
        ];

        for (const filter of filters) {
            const voice = (this.voices || []).find(filter);
            if (voice) return voice;
        }
        return null;
    }

    /**
     * Timbre Institucional "Ding-Dong" de 2 tonos armónicos
     */
    playChime() {
        try {
            this.unlockAudio();
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!this.audioCtx && AudioContext) {
                this.audioCtx = new AudioContext();
            }
            if (this.audioCtx && this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }

            if (!this.audioCtx) return;

            const now = this.audioCtx.currentTime;

            // Nota 1: Sol 5 (783.99 Hz)
            const osc1 = this.audioCtx.createOscillator();
            const gain1 = this.audioCtx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(783.99, now);
            gain1.gain.setValueAtTime(0.28, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.65);
            osc1.connect(gain1);
            gain1.connect(this.audioCtx.destination);
            osc1.start(now);
            osc1.stop(now + 0.65);

            // Nota 2: Mi 5 (659.25 Hz) - Armonía Ding-Dong
            const osc2 = this.audioCtx.createOscillator();
            const gain2 = this.audioCtx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(659.25, now + 0.38);
            gain2.gain.setValueAtTime(0.28, now + 0.38);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 1.25);
            osc2.connect(gain2);
            gain2.connect(this.audioCtx.destination);
            osc2.start(now + 0.38);
            osc2.stop(now + 1.25);

        } catch (e) {
            console.warn("AudioContext Chime:", e);
        }
    }

    /**
     * Pitido sonoro de alerta rápida para rellamados
     */
    playAlertBeep() {
        try {
            this.unlockAudio();
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!this.audioCtx && AudioContext) {
                this.audioCtx = new AudioContext();
            }
            if (this.audioCtx && this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }

            if (!this.audioCtx) return;

            const now = this.audioCtx.currentTime;

            // Triple pitido nítido (880 Hz - La 5)
            [0, 0.22, 0.44].forEach((offset) => {
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, now + offset);
                gain.gain.setValueAtTime(0.35, now + offset);
                gain.gain.exponentialRampToValueAtTime(0.001, now + offset + 0.16);
                osc.connect(gain);
                gain.connect(this.audioCtx.destination);
                osc.start(now + offset);
                osc.stop(now + offset + 0.16);
            });
        } catch (e) {
            console.warn("AudioContext alerta error:", e);
        }
    }

    normalizarTextoParaVoz(texto) {
        if (!texto) return '';
        let limpio = texto.toString().trim();
        // Eliminar asteriscos de habeas data si los hubiera
        limpio = limpio.replace(/\*+/g, '');
        limpio = limpio.replace(/[^a-záéíóúüñA-ZÁÉÍÓÚÜÑ0-9\s]/g, ' ');
        limpio = limpio.replace(/\s+/g, ' ').trim();
        
        // Convertir de MAYÚSCULAS a Formato Nombre Propio (Title Case)
        let formateado = limpio.toLowerCase().split(' ').map(function(w) {
            if (!w) return '';
            return w.charAt(0).toUpperCase() + w.slice(1);
        }).join(' ');

        // Mapeo fonético de nombres comunes en español para asegurar pronunciación nativa 100% correcta
        const mapaNombresEspanol = {
            '\\bJesus\\b': 'Jesús',
            '\\bMaria\\b': 'María',
            '\\bJose\\b': 'José',
            '\\bAngel\\b': 'Ángel',
            '\\bRamon\\b': 'Ramón',
            '\\bAndres\\b': 'Andrés',
            '\\bSebastian\\b': 'Sebastián',
            '\\bRaul\\b': 'Raúl',
            '\\bIvan\\b': 'Iván',
            '\\bJulian\\b': 'Julián',
            '\\bHernan\\b': 'Hernán',
            '\\bRuben\\b': 'Rubén',
            '\\bHector\\b': 'Héctor',
            '\\bOscar\\b': 'Óscar',
            '\\bCesar\\b': 'César',
            '\\bVictor\\b': 'Víctor',
            '\\bSofia\\b': 'Sofía',
            '\\bLucia\\b': 'Lucía',
            '\\bMatias\\b': 'Matías',
            '\\bMartin\\b': 'Martín',
            '\\bDamian\\b': 'Damián',
            '\\bAgustin\\b': 'Agustín',
            '\\bJoaquin\\b': 'Joaquín',
            '\\bMonica\\b': 'Mónica',
            '\\bVeronica\\b': 'Verónica',
            '\\bAlvaro\\b': 'Álvaro'
        };

        for (const [patron, reemplazo] of Object.entries(mapaNombresEspanol)) {
            formateado = formateado.replace(new RegExp(patron, 'gi'), reemplazo);
        }

        return formateado;
    }

    /**
     * Llamado hablado por Nombre Completo con Voz Femenina Natural Agradable
     */
    speak(nombrePaciente, modulo = '') {
        // 1. Reproducir el timbre institucional Ding-Dong
        this.playChime();

        // 2. Normalizar paciente y módulo
        const pacienteNom = this.normalizarTextoParaVoz(nombrePaciente);
        let moduloNom = this.normalizarTextoParaVoz(modulo);

        if (!pacienteNom) return;

        // Formatear frase natural, respetuosa y clara (sin la palabra "Paciente")
        let text = '';
        if (moduloNom && !moduloNom.toLowerCase().includes('sin modulo') && !moduloNom.toLowerCase().includes('null') && !moduloNom.toLowerCase().includes('entrega') && moduloNom !== '--') {
            text = `Atención. ${pacienteNom}, favor pasar a ${moduloNom}`;
        } else {
            text = `Atención. ${pacienteNom}, favor acercarse para la entrega de sus medicamentos`;
        }

        // 3. Detener audio o habla en curso
        if (this.currentAudio) {
            try {
                this.currentAudio.pause();
                this.currentAudio.currentTime = 0;
            } catch (e) {}
            this.currentAudio = null;
        }

        if (this.synth) {
            try {
                this.synth.cancel();
            } catch (e) {}
        }

        // 4. Reproducir Voz Femenina Natural (Esperar 700ms a que termine el Ding-Dong)
        setTimeout(() => {
            const audioUrl = `/api/tts-voice?text=${encodeURIComponent(text)}`;
            const audio = new Audio(audioUrl);
            this.currentAudio = audio;

            audio.play().catch(err => {
                console.warn("Fallo reproducción de audio TTS Stream, usando fallback WebSpeech:", err);
                
                // Fallback secundario si el navegador del TV bloquea Audio stream o no hay internet
                if (this.synth) {
                    try {
                        const utterance = new SpeechSynthesisUtterance(text);
                        utterance.lang = 'es-CO';
                        utterance.rate = 0.88;
                        utterance.pitch = 1.05;

                        const bestVoice = this.selectBestVoice();
                        if (bestVoice) {
                            utterance.voice = bestVoice;
                        }
                        this.synth.speak(utterance);
                    } catch (e) {
                        console.error("Fallo fallback WebSpeech:", e);
                    }
                }
            });
        }, 700);
    }
}

window.turneroSpeech = new TurneroSpeech();