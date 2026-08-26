import http from 'http';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

// ============================================
// ENV LOADER
// Reads config/.env the same way the PHP
// Environment class does (comments, key=value,
// surrounding quotes stripped).
// ============================================
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ENV_PATH = path.join(__dirname, '..', 'config', '.env');

function loadEnv(filePath) {
    const vars = {};
    if (!fs.existsSync(filePath)) {
        return vars;
    }
    const lines = fs.readFileSync(filePath, 'utf8').split(/\r?\n/);
    for (const rawLine of lines) {
        const line = rawLine.trim();
        if (!line || line.startsWith('#')) continue;

        const eq = line.indexOf('=');
        if (eq === -1) continue;

        let key = line.slice(0, eq).trim();
        let value = line.slice(eq + 1).trim();

        // Remove surrounding quotes if present
        if (
            (value.startsWith('"') && value.endsWith('"')) ||
            (value.startsWith("'") && value.endsWith("'"))
        ) {
            value = value.slice(1, -1);
        }

        vars[key] = value;
    }
    return vars;
}

const env = loadEnv(ENV_PATH);

// ============================================
// CONFIGURATION (from config/.env)
// ============================================
const rawKeys = [
    ...(env.GEMINI_API_KEY ? env.GEMINI_API_KEY.split(',') : []),
    ...(env.GEMINI_FALLBACK_API_KEYS ? env.GEMINI_FALLBACK_API_KEYS.split(',') : []),
    ...(env.GEMINI_BACKUP_API_KEY ? env.GEMINI_BACKUP_API_KEY.split(',') : [])
].map(k => k.trim()).filter(Boolean);

const API_KEYS = [...new Set(rawKeys)];
const GEMINI_MODEL = env.GEMINI_MODEL || 'gemini-3.5-flash-lite';
const GEMINI_TEMPERATURE = parseFloat(env.GEMINI_TEMPERATURE) || 0.7;
const GEMINI_MAX_TOKENS = parseInt(env.GEMINI_MAX_TOKENS, 10) || 4096;
const PORT = 3000;

if (API_KEYS.length === 0) {
    console.error('❌ GEMINI_API_KEY is not set in ../config/.env');
    process.exit(1);
}

console.log(`🔑 Loaded ${API_KEYS.length} API key(s) (Primary: ${API_KEYS[0].substring(0, 15)}...)`);
console.log('🤖 Model:', GEMINI_MODEL);

const server = http.createServer(async (req, res) => {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') {
        res.writeHead(200);
        res.end();
        return;
    }

    if (req.method !== 'POST') {
        res.writeHead(405);
        res.end(JSON.stringify({ success: false, error: 'Use POST' }));
        return;
    }

    let body = '';
    req.on('data', chunk => { body += chunk; });
    req.on('end', async () => {
        try {
            const data = JSON.parse(body);

            if (data.prompt === 'ping') {
                res.writeHead(200);
                res.end(JSON.stringify({ success: true, content: 'pong' }));
                return;
            }

            if (!data.prompt) {
                res.writeHead(400);
                res.end(JSON.stringify({ success: false, error: 'Missing prompt' }));
                return;
            }

            console.log('📤 Processing:', data.prompt.substring(0, 60) + '...');

            let lastError = null;

            for (let i = 0; i < API_KEYS.length; i++) {
                const currentKey = API_KEYS[i];
                const url = `https://generativelanguage.googleapis.com/v1beta/models/${GEMINI_MODEL}:generateContent?key=${encodeURIComponent(currentKey)}`;

                const requestData = {
                    contents: [{
                        parts: [{ text: data.prompt }]
                    }],
                    generationConfig: {
                        temperature: GEMINI_TEMPERATURE,
                        maxOutputTokens: data.maxTokens || GEMINI_MAX_TOKENS
                    }
                };

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(requestData)
                    });

                    const responseData = await response.json();

                    if (!response.ok || responseData.error) {
                        lastError = responseData.error?.message || `HTTP ${response.status}`;
                        console.warn(`⚠️ API Key #${i + 1} failed: ${lastError}. Trying fallback if available...`);
                        continue;
                    }

                    const content = responseData.candidates?.[0]?.content?.parts?.[0]?.text || 'No response';
                    console.log('✅ Generated:', content.length, 'characters');

                    res.writeHead(200);
                    res.end(JSON.stringify({ success: true, content: content }));
                    return;
                } catch (fetchErr) {
                    lastError = fetchErr.message;
                    console.warn(`⚠️ API Key #${i + 1} network error: ${fetchErr.message}`);
                }
            }

            res.writeHead(500);
            res.end(JSON.stringify({
                success: false,
                error: `All ${API_KEYS.length} Gemini API key(s) failed: ${lastError}`
            }));

        } catch (error) {
            console.error('❌ Error:', error.message);
            console.error('Stack:', error.stack);
            res.writeHead(500);
            res.end(JSON.stringify({
                success: false,
                error: error.message
            }));
        }
    });
});

server.listen(PORT, '0.0.0.0', () => {
    console.log('\n========================================');
    console.log('🚀 Gemini Bridge RUNNING!');
    console.log('========================================');
    console.log(`📡 URL: http://localhost:${PORT}`);
    console.log(`🤖 Model: ${GEMINI_MODEL}`);
    console.log(`🔑 API Key: ${GEMINI_API_KEY.substring(0, 15)}...`);
    console.log('========================================');
    console.log('\n📝 Press Ctrl+C to stop\n');
});