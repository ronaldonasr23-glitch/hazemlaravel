<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Support\SeoFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Post')
                ->columnSpanFull()
                ->tabs(array_merge([
                    Tab::make('Content')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            TextInput::make('title')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, $set, $get) => filled($get('slug')) ?: $set('slug', Str::slug($state)))
                                ->columnSpanFull(),
                            TextInput::make('slug')
                                ->required()
                                ->live(onBlur: true)
                                ->unique(ignoreRecord: true)
                                ->columnSpanFull(),
                            Textarea::make('excerpt')->rows(2)->columnSpanFull(),
                            RichEditor::make('content')
                                ->live(debounce: 800)
                                ->columnSpanFull(),
                            Placeholder::make('current_featured_image')
                                ->label('Current featured image')
                                ->visible(fn ($record) => (bool) ($record?->featured_image))
                                ->content(fn ($record) => new HtmlString(
                                    '<img src="' . e($record->featured_image) . '" alt="" '
                                    . 'style="max-height:150px;border-radius:8px;border:1px solid #e5e7eb;">'
                                    . '<div style="font-size:12px;color:#6b7280;margin-top:6px;">'
                                    . e($record->featured_image) . '</div>'
                                )),
                            FileUpload::make('featured_image')
                                ->label('Replace featured image')
                                ->image()
                                ->disk('public')
                                ->directory('uploads/posts')
                                ->visibility('public')
                                ->helperText('Upload here only to change the image. The current image is shown above.'),
                            Select::make('status')
                                ->options(['draft' => 'Draft', 'published' => 'Published'])
                                ->default('published')->required(),
                            DateTimePicker::make('published_at')->default(now()),
                        ]),
                ], SeoFields::seoTabs('content', 'title', 'slug'))),
        ]);
    }
}
