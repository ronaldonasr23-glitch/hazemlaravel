<?php

namespace App\Filament\Resources\Redirects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('from_path')
                ->label('From (old path)')
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('e.g. /old-article-2  — the path visitors hit.')
                ->columnSpanFull(),
            TextInput::make('to_path')
                ->label('To (new path)')
                ->required()
                ->helperText('e.g. /old-article  — where to send them.')
                ->columnSpanFull(),
            Select::make('status_code')
                ->label('Type')
                ->options([301 => '301 — Permanent', 302 => '302 — Temporary'])
                ->default(301)
                ->required(),
            Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }
}
