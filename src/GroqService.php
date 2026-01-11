<?php

require_once __DIR__ . '/AiServiceInterface.php';

class GroqService implements AiServiceInterface
{
    private string $apiKey;

    // L'URL de l'API Groq
    private string $apiUrl = "https://api.groq.com/openai/v1/chat/completions";

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function suggestMovie(string $userMood): string
    {
        // --- CORRECTION ICI ---
        // On utilise le dernier modèle stable et polyvalent de Groq
        $model = "llama-3.3-70b-versatile";

        // Prompt strict pour avoir du JSON
        $prompt = "Tu es un expert cinéma. Suggère un film correspondant à cette humeur : '$userMood'.
        Réponds UNIQUEMENT avec un objet JSON valide contenant ces clés : title, director, year, summary.
        Le résumé doit être en français.
        IMPORTANT : Ne mets AUCUN texte avant ou après le JSON. Juste l'objet JSON.";

        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that outputs JSON only.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.5 // On baisse la température pour être plus précis
        ];

        $ch = curl_init($this->apiUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("Erreur cURL Groq : " . curl_error($ch));
        }
        curl_close($ch);

        $result = json_decode($response, true);

        // Gestion des erreurs renvoyées par l'API
        if (isset($result['error'])) {
            throw new Exception("Erreur API Groq : " . $result['error']['message']);
        }

        // Récupération du contenu
        $content = $result['choices'][0]['message']['content'] ?? '';

        // Nettoyage final pour être sûr d'avoir juste le JSON
        return $this->extractJson($content);
    }

    private function extractJson(string $text): string
    {
        // Cherche le début { et la fin } du JSON
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            return $matches[0];
        }
        return $text;
    }
}