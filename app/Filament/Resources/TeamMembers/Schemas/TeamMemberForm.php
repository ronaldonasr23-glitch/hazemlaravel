<?php

namespace App\Filament\Resources\TeamMembers\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TeamMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $get) => filled($get('slug')) ?: $set('slug', Str::slug($state))),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            TextInput::make('position')->label('Job title'),
            Textarea::make('bio')->rows(5)->columnSpanFull(),
            TextInput::make('photo')->label('Photo URL')->columnSpanFull()
                ->helperText('Path or URL to the photo, e.g. /wp-content/uploads/...'),
            KeyValue::make('socials')
                ->label('Social links')
                ->keyLabel('Network')
                ->valueLabel('URL')
                ->columnSpanFull(),
            TextInput::make('menu_order')->numeric()->default(0),
        ]);
    }
}
