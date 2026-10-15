<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Secondary platform hosts (e.g. www.) send every request to the main platform domain, same path.
 */
class RedirectToPlatformController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $port = in_array($request->getPort(), [80, 443], true) ? '' : ':'.$request->getPort();

        return redirect()->away($request->getScheme().'://'.config('tenancy.central_domains')[0].$port.$request->getRequestUri(), 301);
    }
}
