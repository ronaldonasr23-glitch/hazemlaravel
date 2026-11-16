<?php

namespace App\Support;

use App\Models\Service;

/**
 * Turns plain service-name items (Elementor icon-list spans) into links to the
 * matching service page. Idempotent: it strips its own previous links first.
 */
class ServiceLinker
{
    public const MARKER = 'svc-auto-link';

    public static function link(?string $html): ?string
    {
        if (! $html) {
            return $html;
        }

        $html = self::strip($html);

        $map = self::map();

        return preg_replace_callback(
            '/<span class="elementor-icon-list-text">(.*?)<\/span>/is',
            function ($m) use ($map) {
                $text = trim(html_entity_decode(strip_tags($m[1])));
                $key = self::norm($text);
                if ($key !== '' && isset($map[$key])) {
                    return '<a href="/service/' . e($map[$key]) . '" class="' . self::MARKER . '">' . $m[0] . '</a>';
                }

                return $m[0];
            },
            $html
        );
    }

    /** Remove any service-link wrapper around an icon-list span, so linking can be re-run safely. */
    public static function strip(string $html): string
    {
        // Run twice to also undo any accidental nesting.
        for ($i = 0; $i < 2; $i++) {
            $html = preg_replace(
                '/<a href="\/service\/[^"]*"[^>]*>(<span class="elementor-icon-list-text">.*?<\/span>)<\/a>/is',
                '$1',
                $html
            );
        }

        return $html;
    }

    /** @return array<string,string> normalized service title/alias => slug */
    protected static function map(): array
    {
        $map = [];
        foreach (Service::get(['title', 'slug']) as $svc) {
            $map[self::norm($svc->title)] = $svc->slug;
        }

        $aliases = [
            'vendorsandmanufacturesselection'                                        => 'licensesselectionvendorsandmanufacturesselection',
            'fullsupporttoownerduringallstagesofplantstartupoperationandmaintenance' => 'ownerssupport',
            'daybydayplantoperation'                                                 => 'maintenancemanagement',
            'daybydayandhourbyhourmaintenance'                                       => 'maintenancemanagement',
            'troubleshootingforoperatingplant'                                       => 'troubleshooting',
            'preparationofitbinvitationtobid'                                        => 'preparationofitpinvitationtobid',
            'industrialplantrelocationservices'                                      => 'industrialplantrelocationservices',
        ];
        foreach ($aliases as $from => $to) {
            if (isset($map[$to])) {
                $map[$from] = $map[$to];
            }
        }

        return $map;
    }

    protected static function norm(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(html_entity_decode($s)));
    }
}
