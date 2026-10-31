<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;

/** The same authorized read models and Blade markup serve normal and live requests. */
class LiveTables
{
    public static function requested(Request $request): bool
    {
        return filled($request->header('X-Live-Tables'));
    }

    public static function actionRequested(Request $request): bool
    {
        return $request->header('X-Live-Action') === '1';
    }

    /** @param list<string> $allowedFragments */
    public static function view(Request $request, View $view, array $allowedFragments): View|JsonResponse
    {
        if (! self::requested($request)) {
            return $view;
        }
        $requested = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $request->header('X-Live-Tables'))))));
        abort_if($requested === [] || count($requested) > 8 || array_diff($requested, $allowedFragments) !== [], 400, 'Unknown table region.');

        // Capture every requested fragment during one render, before Blade flushes
        // its fragment state. Rendering each fragment separately repeats queries.
        $payload = $view->render(function ($rendered, string $contents) use ($request, $requested): array {
            $fragments = app('view')->getFragments();
            foreach ($requested as $name) {
                if (! isset($fragments[$name])) {
                    throw new LogicException('The table view does not define its declared fragment: '.$name);
                }
            }
            preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $contents, $title);

            return [
                'regions' => array_intersect_key($fragments, array_flip($requested)),
                'url' => $request->fullUrl(),
                'title' => isset($title[1]) ? html_entity_decode(strip_tags($title[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
            ];
        });

        return response()->json($payload)->header('Cache-Control', 'private, no-store')
            ->header('Vary', 'X-Live-Tables, Accept');
    }

    /** Preserve independent pagers and recover when a deletion empties the last page. */
    public static function paginate(Builder|Relation $query, int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        $request = request();
        $page = max(1, $request->integer($pageName, 1));
        $paginator = $query->paginate($perPage, ['*'], $pageName, $page);
        if ($page > $paginator->lastPage()) {
            $page = $paginator->lastPage();
            $request->query->set($pageName, $page);
            $request->server->set('QUERY_STRING', http_build_query($request->query->all(), '', '&', PHP_QUERY_RFC3986));
            $paginator = $query->paginate($perPage, ['*'], $pageName, $page);
        }

        return $paginator->withQueryString();
    }

    /** @param list<string> $topics */
    public static function mutation(Request $request, string $message, RedirectResponse $fallback, array $topics = [], string $type = 'success'): JsonResponse|RedirectResponse
    {
        if (! self::actionRequested($request)) {
            self::signal($request, $topics);

            return $fallback;
        }
        // A normal fallback may already have flashed its message. Do not replay
        // that toast when the following fragment fetch or navigation occurs.
        if ($request->hasSession()) {
            $keys = array_intersect($request->session()->get('_flash.new', []), ['success', 'error', 'warning', 'info', 'status', 'live-table-topics']);
            $request->session()->forget($keys);
            $request->session()->put('_flash.new', array_values(array_diff($request->session()->get('_flash.new', []), $keys)));
        }

        return response()->json([
            'message' => $message,
            'type' => in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info',
            'topics' => array_values(array_unique($topics)),
            'refresh' => true,
        ])->header('Cache-Control', 'private, no-store');
    }

    /** A normal navigation can still notify other tabs after its committed save. */
    public static function signal(Request $request, array $topics): void
    {
        if ($request->hasSession() && $topics !== []) {
            $request->session()->flash('live-table-topics', array_values(array_unique($topics)));
        }
    }
}
