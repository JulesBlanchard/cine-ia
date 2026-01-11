<?php
// src/MockAiService.php

require_once __DIR__ . '/AiServiceInterface.php';

class MockAiService implements AiServiceInterface
{
    public function suggestMovie(string $userMood): string
    {
        // On simule un petit temps de réflexion (0.5 seconde) pour faire "vrai"
        usleep(500000);

        // On renvoie une réponse en JSON simulé
        // Note : Je renvoie du JSON car c'est ce que notre contrôleur attendra pour le décoder
        return json_encode([
            'title' => 'Inception (Mode Simulation)',
            'director' => 'Christopher Nolan',
            'year' => 2010,
            'summary' => "Ceci est une fausse réponse générée localement car l'API n'est pas connectée. Humeur détectée : $userMood"
        ]);
    }
}