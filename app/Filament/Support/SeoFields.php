<?php

namespace App\Filament\Support;

use App\Support\SeoAnalyzer;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

class SeoFields
{
    /** SEO field names handled outside the model's own columns. */
    public const KEYS = [
        'seo_title', 'meta_description', 'focus_keyphrase', 'keyphrase_synonyms',
        'canonical_url', 'breadcrumb_title', 'og_title', 'og_description', 'og_image',
        'twitter_title', 'twitter_description', 'twitter_image',
        'schema_page_type', 'schema_article_type',
        'is_cornerstone', 'noindex', 'nofollow',
    ];

    public static function tabs(string $contentField = 'content', string $titleField = 'title', string $slugField = 'slug'): Tabs
    {
        return Tabs::make('SEO')
            ->columnSpanFull()
            ->tabs(self::seoTabs($contentField, $titleField, $slugField));
    }

    /** @return array<Tab> */
    public static function seoTabs(string $contentField = 'content', string $titleField = 'title', string $slugField = 'slug'): array
    {
        return [
                Tab::make('SEO')
                    ->icon('heroicon-o-magnifying-glass')
                    ->schema([
                        TextInput::make('focus_keyphrase')
                            ->label('Focus keyphrase')
                            ->helperText('The main term you want this content to rank for.')
                            ->live(debounce: 600)
                            ->columnSpanFull(),
                        TextInput::make('keyphrase_synonyms')
                            ->label('Keyphrase synonyms')
                            ->columnSpanFull(),
                        TextInput::make('seo_title')
                            ->label('SEO title')
                            ->helperText('Supports variables: %%title%%, %%sitename%%, %%sep%%')
                            ->live(debounce: 600)
                            ->columnSpanFull(),
                        Textarea::make('meta_description')
                            ->label('Meta description')
                            ->rows(3)
                            ->live(debounce: 600)
                            ->columnSpanFull(),
                        Toggle::make('is_cornerstone')
                            ->label('Cornerstone content'),
                        Placeholder::make('seo_analysis')
                            ->label('Analysis')
                            ->columnSpanFull()
                            ->content(fn (Get $get) => new HtmlString(
                                view('filament.seo-analysis', [
                                    'result' => SeoAnalyzer::analyze([
                                        'focus_keyphrase'  => $get('focus_keyphrase'),
                                        'seo_title'        => $get('seo_title'),
                                        'title'            => $get($titleField),
                                        'meta_description' => $get('meta_description'),
                                        'slug'             => $get($slugField),
                                        'content'          => $get($contentField),
                                    ]),
                                    'snippet' => [
                                        'title' => $get('seo_title') ?: (string) $get($titleField),
                                        'desc'  => (string) $get('meta_description'),
                                        'url'   => url('/' . ltrim((string) $get($slugField), '/')),
                                    ],
                                ])->render()
                            )),
                    ]),

                Tab::make('Social')
                    ->icon('heroicon-o-share')
                    ->schema([
                        TextInput::make('og_title')->label('Facebook / OG title')->columnSpanFull(),
                        Textarea::make('og_description')->label('Facebook / OG description')->rows(2)->columnSpanFull(),
                        TextInput::make('og_image')->label('Facebook / OG image URL')->columnSpanFull(),
                        TextInput::make('twitter_title')->label('X / Twitter title')->columnSpanFull(),
                        Textarea::make('twitter_description')->label('X / Twitter description')->rows(2)->columnSpanFull(),
                        TextInput::make('twitter_image')->label('X / Twitter image URL')->columnSpanFull(),
                    ]),

                Tab::make('Schema')
                    ->icon('heroicon-o-code-bracket')
                    ->schema([
                        Select::make('schema_page_type')
                            ->label('Page type')
                            ->options([
                                'WebPage' => 'Web Page', 'ItemPage' => 'Item Page',
                                'AboutPage' => 'About Page', 'ContactPage' => 'Contact Page',
                                'CollectionPage' => 'Collection Page', 'FAQPage' => 'FAQ Page',
                            ])->default('WebPage'),
                        Select::make('schema_article_type')
                            ->label('Article type')
                            ->options([
                                'Article' => 'Article', 'BlogPosting' => 'Blog Post',
                                'NewsArticle' => 'News Article', 'TechArticle' => 'Technical Article',
                            ])->default('Article'),
                    ]),

                Tab::make('Advanced')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Toggle::make('noindex')->label('Do not show this content in search results (noindex)'),
                        Toggle::make('nofollow')->label('Do not follow links on this content (nofollow)'),
                        TextInput::make('canonical_url')->label('Canonical URL')->columnSpanFull(),
                        TextInput::make('breadcrumb_title')->label('Breadcrumb title')->columnSpanFull(),
                    ]),
        ];
    }
}
