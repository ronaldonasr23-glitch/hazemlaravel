<?php

namespace App\Filament\Concerns;

use App\Filament\Support\SeoFields;
use App\Support\SeoAnalyzer;

trait InteractsWithSeo
{
    protected array $seoData = [];

    protected function pullSeo(array $data): array
    {
        foreach (SeoFields::KEYS as $k) {
            if (array_key_exists($k, $data)) {
                $this->seoData[$k] = $data[$k];
                unset($data[$k]);
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->pullSeo($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->pullSeo($data);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        if ($record && method_exists($record, 'seo') && $record->seo) {
            foreach (SeoFields::KEYS as $k) {
                $data[$k] = $record->seo->{$k};
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->saveSeo();
    }

    protected function afterSave(): void
    {
        $this->saveSeo();
    }

    protected function saveSeo(): void
    {
        $record = $this->getRecord();
        if (! $record || ! method_exists($record, 'seo')) {
            return;
        }

        $content = $record->content ?? $record->content_html ?? '';
        $analysis = SeoAnalyzer::analyze([
            'focus_keyphrase'  => $this->seoData['focus_keyphrase'] ?? null,
            'seo_title'        => $this->seoData['seo_title'] ?? null,
            'title'            => $record->title ?? $record->name ?? '',
            'meta_description' => $this->seoData['meta_description'] ?? null,
            'slug'             => $record->slug ?? '',
            'content'          => $content,
        ]);

        $this->seoData['seo_score'] = $analysis['seo_score'];
        $this->seoData['readability_score'] = $analysis['readability_score'];

        $record->seo()->updateOrCreate([], $this->seoData);
    }
}
