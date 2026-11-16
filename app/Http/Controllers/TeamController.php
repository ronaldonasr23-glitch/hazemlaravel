<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TeamMember;
use App\Support\Seo;

class TeamController extends Controller
{
    private const BODY = 'wp-theme-indutri tribe-no-js elementor-default elementor-kit-6';

    public function show(string $slug)
    {
        $member = TeamMember::where('slug', $slug)->firstOrFail();

        $content = view('dynamic.team-single', compact('member'))->render();

        return view('site', [
            'head' => Seo::head([
                'fallback_title' => $member->name . ' - ' . Setting::get('site_name'),
                'description'    => \Illuminate\Support\Str::limit(strip_tags($member->bio), 160),
                'image'          => $member->photo,
                'url'            => route('team.show', $member->slug),
            ]),
            'bodyClass'   => 'single single-gva_team ' . self::BODY,
            'contentHtml' => $content,
        ]);
    }
}
