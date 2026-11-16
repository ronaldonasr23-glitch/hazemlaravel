<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Support\SeoFields;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Page')->columnSpanFull()->tabs(array_merge([
                Tab::make('Content')->icon('heroicon-o-document')->schema([
                    TextInput::make('title')->required()->live(onBlur: true)->columnSpanFull(),
                    TextInput::make('path')
                        ->label('URL path (no leading/trailing slash; empty = home)')
                        ->unique(ignoreRecord: true)
                        ->helperText('The exact original WordPress path, e.g. services/spine-deformity-correction/scoliosis-correction')
                        ->columnSpanFull(),
                    \Filament\Forms\Components\Select::make('language')
                        ->options(['en' => 'English', 'ar' => 'العربية'])
                        ->default('en')->required(),
                    TextInput::make('slug')->helperText('WordPress post_name (reference only)')->columnSpanFull(),
                    Textarea::make('content_html')
                        ->label('Page content (HTML)')
                        ->rows(18)
                        ->live(debounce: 1000)
                        ->extraInputAttributes(['style' => 'font-family:monospace;font-size:12px;'])
                        ->columnSpanFull(),
                    TextInput::make('menu_order')->numeric()->default(0),
                    Toggle::make('is_published')->default(true),
                ]),
                Tab::make('Advanced HTML')->icon('heroicon-o-code-bracket-square')->schema([
                    Textarea::make('head_html')
                        ->label('Head HTML (page-specific styles/meta)')
                        ->rows(10)
                        ->extraInputAttributes(['style' => 'font-family:monospace;font-size:12px;'])
                        ->columnSpanFull(),
                    TextInput::make('body_class')->columnSpanFull(),
                ]),
            ], SeoFields::seoTabs('content_html', 'title', 'slug'))),
        ]);
    }
}
