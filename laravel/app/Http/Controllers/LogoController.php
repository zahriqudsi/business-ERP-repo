<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves the business's uploaded logo from its own (non-public) storage.
 */
class LogoController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        // The URL carries ?v= for the current logo, so a new upload gets a new URL
        return response()->file(logo_path(), ['Cache-Control' => 'private, max-age=86400']);
    }
}
