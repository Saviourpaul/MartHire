<?php

namespace App\Http\Middleware;

use App\Support\ApplicationUploadLimits;
use Closure;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApplicationPostSize extends ValidatePostSize
{
    public function handle($request, Closure $next): Response
    {
        if ($request instanceof Request && $this->isApplicationEndpoint($request)) {
            $limit = ApplicationUploadLimits::requestBodyLimitBytes();
            $contentLength = $request->server('CONTENT_LENGTH');

            if ($limit !== null && is_numeric($contentLength) && (int) $contentLength > $limit) {
                $message = __('The application upload is larger than the server allows. Remove a file or choose smaller files, then try again.');

                return $request->expectsJson()
                    ? response()->json(['message' => $message], 413)
                    : response($message, 413);
            }
        }

        return parent::handle($request, $next);
    }

    private function isApplicationEndpoint(Request $request): bool
    {
        return $request->is('jobs/*/apply') || $request->is('workable-jobs/*/apply');
    }
}
