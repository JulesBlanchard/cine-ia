<?php
// src/HuggingFaceService.php

require_once __DIR__ . '/AiServiceInterface.php';

class HuggingFaceService implements AiServiceInterface
{
    private string $apiKey;

    // On utilise le modèle Mistral-7B (très bon et rapide)
    private string $modelUrl = "https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.3";

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function suggestMovie(string $userMood): string
    {
        // 1. Préparation du Prompt (Instruction)
        // Mistral a besoin d'un format spécifique [INST] ... [/INST]
        $promptText = "[INST] Tu es un expert cinéma. Suggère un film pour cette humeur : '$userMood'. 
        Réponds UNIQUEMENT avec un objet JSON strict formaté ainsi : 
        {\"title\": \"Titre\", \"director\": \"Realisateur\", \"year\": 2000, \"summary\": \"Résumé court\"} 
        Ne mets pas de texte avant ou après le JSON. [/INST]";

        // 2. Configuration cURL
        $ch = curl_init($this->modelUrl);

        $data = [
            'inputs' => $promptText,
            'parameters' => [
                'return_full_text' => false, // On veut juste la réponse, pas le prompt répété
                'max_new_tokens' => 500,     // Limite la longueur de la réponse
                'temperature' => 0.7         // Créativité
            ]
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: application/json"
        ]);

        // 3. Exécution
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("Erreur cURL : " . curl_error($ch));
        }
        curl_close($ch);

        // 4. Traitement de la réponse brute
        // Hugging Face renvoie un tableau de résultats
        $result = json_decode($response, true);

        if (isset($result['error'])) {
            // Si le modèle charge (fréquent sur le gratuit), on gère l'erreur proprement
            throw new Exception("Erreur HuggingFace : " . $result['error']);
        }

        // On récupère le texte généré (generated_text)
        $rawText = $result[0]['generated_text'] ?? '';

        // Nettoyage : Parfois l'IA ajoute du texte autour du JSON, on essaie de nettoyer
        // (C'est la partie "bidouille" nécessaire avec les IA gratuites parfois moins précises)
        $start = strpos($rawText, '{');
        $end = strrpos($rawText, '}');

        if ($start !== false && $end !== false) {
            return substr($rawText, $start, $end - $start + 1);
        }

        return $rawText; // Si on arrive pas à nettoyer, on renvoie tout
    }
}