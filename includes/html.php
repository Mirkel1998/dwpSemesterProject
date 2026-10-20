<?php

function e(?string $value): string
{
    // Escape dynamic values before placing them in HTML text or attributes.
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
