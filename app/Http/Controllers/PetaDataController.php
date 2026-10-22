<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GeoJSON tanah wakaf publik untuk peta. Diisi pada Fase 4 (peta).
 */
class PetaDataController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['type' => 'FeatureCollection', 'features' => []]);
    }
}
