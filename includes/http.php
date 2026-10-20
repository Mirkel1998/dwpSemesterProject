<?php

function redirect(string $url): never
{
    // Send the browser to another page and stop the current request.
    header('Location: ' . $url);
    exit;
}
