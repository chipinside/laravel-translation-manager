<?php

namespace Barryvdh\TranslationManager\Http\Middleware;

use Barryvdh\TranslationManager\Translator;

class Authenticate
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|null
     */
    public function handle($request, $next)
    {
        return Translator::check($request) ? $next($request) : abort(403);
    }
}
