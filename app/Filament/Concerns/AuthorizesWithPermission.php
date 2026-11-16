<?php

namespace App\Filament\Concerns;

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;

trait AuthorizesWithPermission
{
    protected static array $permissionMap = [
        Post::class       => 'manage_posts',
        Article::class    => 'manage_posts',
        Faq::class        => 'manage_pages',
        CaseStudy::class  => 'manage_pages',
        Page::class       => 'manage_pages',
        Service::class    => 'manage_services',
        TeamMember::class => 'manage_team',
        Portfolio::class  => 'manage_portfolios',
        Redirect::class   => 'manage_redirects',
        User::class       => 'manage_users',
    ];

    public static function canAccess(): bool
    {
        $perm = static::$permissionMap[static::$model] ?? null;

        return $perm ? (bool) auth()->user()?->can($perm) : true;
    }
}
