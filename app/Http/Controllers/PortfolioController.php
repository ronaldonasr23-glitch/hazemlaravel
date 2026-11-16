<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Setting;
use App\Support\Seo;

class PortfolioController extends Controller
{
    private const BODY = 'wp-theme-indutri tribe-no-js elementor-default elementor-kit-6';

    public function index()
    {
        $portfolios = Portfolio::published()->orderBy('menu_order')->orderBy('title')->get();

        $content = view('dynamic.portfolio-index', compact('portfolios'))->render();

        return view('site', [
            'head' => Seo::head([
                'title'       => 'Our Projects - ' . Setting::get('site_name'),
                'description' => 'Selected industrial and engineering projects delivered by Grow Gas.',
                'url'         => route('portfolio.index'),
            ]),
            'bodyClass'   => 'archive ' . self::BODY,
            'contentHtml' => $content,
        ]);
    }

    public function show(string $slug)
    {
        $portfolio = Portfolio::published()->where('slug', $slug)->firstOrFail();

        $content = view('dynamic.portfolio-single', compact('portfolio'))->render();

        return view('site', [
            'head' => Seo::head([
                'seo'            => $portfolio->seo,
                'fallback_title' => $portfolio->title . ' - ' . Setting::get('site_name'),
                'image'          => $portfolio->featured_image,
                'url'            => route('portfolio.show', $portfolio->slug),
            ]),
            'bodyClass'   => 'single single-portfolio ' . self::BODY,
            'contentHtml' => $content,
        ]);
    }
}
