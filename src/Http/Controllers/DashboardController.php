<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $config = [
            'basePath' => '/'.config('wame-telescope-dashboard.path', 'telescope-dashboard'),
            'telescopePath' => '/'.config('wame-telescope-dashboard.telescope_path', 'telescope'),
            'perPage' => config('wame-telescope-dashboard.per_page', 50),
        ];

        $locale = app()->getLocale();
        $translationFile = __DIR__.'/../../../resources/lang/'.$locale.'/telescope-dashboard.php';

        if (! File::exists($translationFile)) {
            $translationFile = __DIR__.'/../../../resources/lang/en/telescope-dashboard.php';
        }

        $translations = File::exists($translationFile) ? require $translationFile : [];

        return view('telescope-dashboard::dashboard', [
            'config' => $config,
            'translations' => $translations,
        ]);
    }
}
