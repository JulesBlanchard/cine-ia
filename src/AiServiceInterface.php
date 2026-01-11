<?php

interface AiServiceInterface
{
    /**
     * Envoie une invite (prompt) à l'IA et retourne sa réponse textuelle.
     * * @param string $prompt La question ou la demande de l'utilisateur
     * @return string La réponse générée par l'IA
     */
    public function suggestMovie(string $userMood): string;
}