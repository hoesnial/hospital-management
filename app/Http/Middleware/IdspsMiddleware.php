<?php

namespace App\Http\Middleware;

use App\Services\IdspsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdspsMiddleware
{
    protected IdspsService $idsps;

    public function __construct(IdspsService $idsps)
    {
        $this->idsps = $idsps;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->idsps->isEnabled()) {
            return $next($request);
        }

        $ip = $request->ip();
        $path = $request->path();

        if ($this->idsps->isIpWhitelisted($ip)) {
            return $next($request);
        }

        if ($this->idsps->isExcludedPath($path)) {
            return $next($request);
        }

        if ($this->idsps->isIpBlocked($ip)) {
            $this->idsps->detectAndLog(
                'ip_blocked',
                "Blocked request from blacklisted/temporary blocked IP: {$ip}",
                ['path' => $path]
            );

            if ($this->idsps->isPreventionMode()) {
                return response()->json([
                    'error' => 'Access denied',
                    'message' => 'Your IP has been blocked due to suspicious activity.',
                ], 403);
            }
        }

        // Check User-Agent
        $uaResult = $this->idsps->checkUserAgent($request->userAgent() ?? '');
        if ($uaResult) {
            $this->idsps->detectAndLog(
                'suspicious_user_agent',
                $uaResult,
                ['user_agent' => $request->userAgent()]
            );

            if ($this->idsps->isPreventionMode()) {
                return response()->json([
                    'error' => 'Request blocked',
                    'message' => 'Suspicious client detected.',
                ], 403);
            }
        }

        // Check request rate
        if ($this->idsps->checkRequestRate($ip)) {
            $this->idsps->detectAndLog(
                'excessive_request_rate',
                "IP {$ip} exceeded maximum request rate",
                ['rate' => $this->idsps->getSuspiciousCount($ip)]
            );

            if ($this->idsps->isPreventionMode()) {
                return response()->json([
                    'error' => 'Too many requests',
                    'message' => 'Request rate exceeded.',
                ], 429);
            }
        }

        // Check all input parameters
        $inputs = array_merge(
            $request->query(),
            $request->request(),
            $request->files->all()
        );

        foreach ($inputs as $key => $value) {
            if (is_array($value)) {
                $this->checkArrayInput($value, $key);
            } else {
                $result = $this->idsps->checkInput($value, $key);
                if ($result) {
                    $this->idsps->detectAndLog(
                        'malicious_input_detected',
                        $result,
                        ['field' => $key, 'value_sample' => substr((string)$value, 0, 200)]
                    );

                    if ($this->idsps->isPreventionMode()) {
                        return response()->json([
                            'error' => 'Malicious input detected',
                            'message' => 'Your request contains suspicious content and has been blocked.',
                        ], 400);
                    }
                }
            }
        }

        // Check headers
        $headerResult = $this->idsps->checkInput($request->header('Referer', ''), 'Referer');
        if ($headerResult) {
            $this->idsps->detectAndLog(
                'suspicious_header',
                $headerResult,
                ['header' => 'Referer']
            );
        }

        return $next($request);
    }

    protected function checkArrayInput(array $values, string $prefix): void
    {
        foreach ($values as $key => $value) {
            $fieldKey = "{$prefix}.{$key}";
            if (is_array($value)) {
                $this->checkArrayInput($value, $fieldKey);
            } else {
                $result = $this->idsps->checkInput($value, $fieldKey);
            }
        }
    }
}
