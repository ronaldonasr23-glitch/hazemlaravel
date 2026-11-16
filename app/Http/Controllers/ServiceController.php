<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Setting;
use App\Support\Seo;

class ServiceController extends Controller
{
    private const BODY = 'wp-theme-indutri tribe-no-js elementor-default elementor-kit-6';

    public function index()
    {
        $services = Service::published()->orderBy('menu_order')->orderBy('title')->get();

        $content = view('dynamic.services-index', compact('services'))->render();

        return view('site', [
            'head' => Seo::head([
                'title'       => 'Services - ' . Setting::get('site_name'),
                'description' => 'Full-scope engineering, procurement, commissioning and operation services for chemical and gas plants.',
                'url'         => route('services.index'),
            ]),
            'bodyClass'   => 'archive ' . self::BODY,
            'contentHtml' => $content,
        ]);
    }

    public function show(string $slug)
    {
        $service = Service::published()->where('slug', $slug)->firstOrFail();
        $others = Service::published()->where('id', '!=', $service->id)->orderBy('menu_order')->take(8)->get();

        $content = view('dynamic.service-single', compact('service', 'others'))->render();

        return view('site', [
            'head' => Seo::head([
                'seo'            => $service->seo,
                'fallback_title' => $service->title . ' - ' . Setting::get('site_name'),
                'description'    => $service->excerpt,
                'image'          => $service->featured_image,
                'url'            => route('services.show', $service->slug),
            ]),
            'bodyClass'   => 'single single-service ' . self::BODY,
            'contentHtml' => $content,
        ]);
    }
}
