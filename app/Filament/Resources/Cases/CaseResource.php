<?php

namespace App\Filament\Resources\Cases;

use App\Filament\Resources\Cases\Pages\CreateCaseStudy;
use App\Filament\Resources\Cases\Pages\EditCaseStudy;
use App\Filament\Resources\Cases\Pages\ListCaseStudies;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Filament\Resources\Pages\Tables\PagesTable;
use App\Models\CaseStudy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CaseResource extends Resource
{
    use \App\Filament\Concerns\AuthorizesWithPermission;

    protected static ?string $model = CaseStudy::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Cases';

    protected static ?string $pluralModelLabel = 'Cases';

    protected static ?string $modelLabel = 'Case';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCaseStudies::route('/'),
            'create' => CreateCaseStudy::route('/create'),
            'edit' => EditCaseStudy::route('/{record}/edit'),
        ];
    }
}
