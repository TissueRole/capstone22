<?php

function forum_blocked_terms(): array
{
    return [
        'fuck',
        'fucking',
        'shit',
        'bitch',
        'asshole',
        'puta',
        'gago',
        'ulol',
        'tanga',
        'bobo',
        'putangina',
        'pakyu'
    ];
}

function forum_contains_profanity(string $text): bool
{
    $normalized = strtolower($text);
    foreach (forum_blocked_terms() as $term) {
        if (preg_match('/\b' . preg_quote($term, '/') . '\b/i', $normalized)) {
            return true;
        }
    }
    return false;
}

function forum_validate_clean_text(array $values): ?string
{
    foreach ($values as $label => $value) {
        if (forum_contains_profanity((string) $value)) {
            return "Please remove inappropriate language from the {$label}.";
        }
    }
    return null;
}
