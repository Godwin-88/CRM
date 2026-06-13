<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TranslationController extends Controller
{
    public function available(): JsonResponse
    {
        $locales = config('app.available_locales', ['en']);

        return response()->json([
            'locales' => $locales,
            'default' => config('app.locale', 'en'),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $locale = $request->get('locale', config('app.locale', 'en'));
        $group = $request->get('group', '*');

        $translations = $this->getTranslations($locale, $group);

        return response()->json([
            'locale' => $locale,
            'translations' => $translations,
        ]);
    }

    public function setLanguage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language' => 'required|string|size:2',
        ]);

        $locales = config('app.available_locales', ['en']);
        if (! in_array($validated['language'], $locales)) {
            return response()->json(['message' => 'Unsupported language'], 422);
        }

        $user = $request->user();
        $user->update(['interface_language' => $validated['language']]);

        return response()->json(['message' => 'Language updated.', 'language' => $validated['language']]);
    }

    private function getTranslations(string $locale, string $group): array
    {
        $cacheKey = "translations:{$locale}:{$group}";

        return Cache::remember($cacheKey, 3600, function () use ($locale, $group) {
            $path = lang_path($locale);
            $files = glob("{$path}/{$group}.php");

            $translations = [];
            foreach ($files as $file) {
                $key = str_replace([$path.'/', '.php'], '', $file);
                $translations[$key] = include $file;
            }

            return $translations;
        });
    }
}
