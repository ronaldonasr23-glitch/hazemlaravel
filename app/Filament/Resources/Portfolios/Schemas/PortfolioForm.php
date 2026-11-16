<?php

namespace App\Filament\Resources\Portfolios\Schemas;

use App\Filament\Support\SeoFields;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PortfolioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Portfolio')->columnSpanFull()->tabs(array_merge([
                Tab::make('Content')->icon('heroicon-o-briefcase')->schema([
                    TextInput::make('title')->required()->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set, $get) => filled($get('slug')) ?: $set('slug', Str::slug($state)))
                        ->columnSpanFull(),
                    TextInput::make('slug')->required()->live(onBlur: true)->unique(ignoreRecord: true)->columnSpanFull(),
                    RichEditor::make('content')->live(debounce: 800)->columnSpanFull(),
                    FileUpload::make('featured_image')->image()->directory('uploads/portfolios')->visibility('public'),
                    TextInput::make('client'),
                    TextInput::make('category'),
                    TextInput::make('menu_order')->numeric()->default(0),
                    Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->default('published')->required(),
                ]),
            ], SeoFields::seoTabs('content', 'title', 'slug'))),
        ]);
    }
}
