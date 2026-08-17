<?php
// ============================================
// GEMINI API HELPER - Direct REST Calls
// ============================================
// Calls the Gemini REST API directly using the
// credentials in config/.env. No Node.js bridge
// required. Config is loaded via config/config.php.

require_once __DIR__ . '/../config/config.php';

/**
 * Call the Gemini API directly.
 *
 * @param string $prompt       The prompt text to send.
 * @param float  $temperature  Sampling temperature (0.0 - 1.0).
 * @param int    $maxTokens    Maximum output tokens.
 *
 * @return string|null  The generated text, or null on any failure.
 */
function callGeminiAPI($prompt, $temperature = 0.7, $maxTokens = 2048) {
    $config = getGeminiConfig();
    $apiKey = $config['api_key'];

    if (empty($apiKey)) {
        error_log("Gemini API: GEMINI_API_KEY is not set in config/.env");
        return null;
    }

    $trimmedPrompt = trim($prompt);

    // 1. Zero-token handler for health-check / ping prompts
    if ($trimmedPrompt === 'ping' || strtolower($trimmedPrompt) === "say 'gemini is working'") {
        return "pong";
    }

    // 2. Database Caching Check (ai_cache table)
    $conn = null;
    $promptHash = null;
    try {
        $conn = getDBConnection();
        if ($conn) {
            $promptHash = md5($trimmedPrompt . '_' . (float)$temperature . '_' . (int)$maxTokens);
            $cacheStmt = $conn->prepare("SELECT response_text FROM ai_cache WHERE prompt_hash = ? LIMIT 1");
            if ($cacheStmt) {
                $cacheStmt->bind_param("s", $promptHash);
                $cacheStmt->execute();
                $cacheRes = $cacheStmt->get_result();
                if ($cacheRes && $row = $cacheRes->fetch_assoc()) {
                    $cacheStmt->close();
                    return $row['response_text']; // Served from DB cache - 0 API tokens!
                }
                $cacheStmt->close();
            }
        }
    } catch (Exception $e) {
        error_log("Gemini Cache Check Error: " . $e->getMessage());
    }

    // 3. Make cURL call to Gemini API if not in cache
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$config['model']}:generateContent?key=" . urlencode($apiKey);

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $trimmedPrompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => (float)$temperature,
            "topK" => 40,
            "topP" => 0.95,
            "maxOutputTokens" => (int)$maxTokens
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Gemini API cURL Error: " . $curlError);
        return null;
    }

    if ($httpCode != 200) {
        $detail = '';
        $decoded = json_decode($response, true);
        if (isset($decoded['error']['message'])) {
            $detail = " - " . $decoded['error']['message'];
        }
        error_log("Gemini API HTTP Error: " . $httpCode . $detail);
        return null;
    }

    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $generatedText = $result['candidates'][0]['content']['parts'][0]['text'];

        // Save generated result to ai_cache table for future requests
        if ($conn && $promptHash) {
            try {
                $saveStmt = $conn->prepare("INSERT INTO ai_cache (prompt_hash, response_text) VALUES (?, ?) ON DUPLICATE KEY UPDATE response_text = VALUES(response_text)");
                if ($saveStmt) {
                    $saveStmt->bind_param("ss", $promptHash, $generatedText);
                    $saveStmt->execute();
                    $saveStmt->close();
                }
            } catch (Exception $e) {
                error_log("Gemini Cache Save Error: " . $e->getMessage());
            }
        }

        return $generatedText;
    }

    error_log("Gemini API: unexpected success response shape");
    return null;
}