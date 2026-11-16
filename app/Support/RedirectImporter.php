<?php

namespace App\Support;

use App\Models\Redirect;

class RedirectImporter
{
    /**
     * Parse a CSV of old URLs and create/refresh 301 redirects.
     *
     * @return array{created:int,updated:int,skipped:int}
     */
    public static function fromCsv(string $path, bool $overwrite = true): array
    {
        $resolver = new RedirectResolver();
        $created = $updated = $skipped = 0;
        $seen = [];

        $h = fopen($path, 'r');
        if ($h === false) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        while (($row = fgetcsv($h)) !== false) {
            foreach ($row as $cell) {
                $cell = trim((string) $cell);
                if ($cell === '' || (stripos($cell, 'http') !== 0 && ! str_starts_with($cell, '/'))) {
                    continue; // header, date or empty cell
                }

                $res = $resolver->resolve($cell);
                if (! $res) {
                    $skipped++;
                    break;
                }
                if (isset($seen[$res['from']])) {
                    break;
                }
                $seen[$res['from']] = true;

                $existing = Redirect::where('from_path', $res['from'])->first();
                if ($existing) {
                    if ($overwrite && $existing->to_path !== $res['to']) {
                        $existing->update(['to_path' => $res['to'], 'source' => 'csv-import']);
                        $updated++;
                    }
                    break;
                }

                Redirect::create([
                    'from_path'   => $res['from'],
                    'to_path'     => $res['to'],
                    'status_code' => 301,
                    'is_active'   => true,
                    'source'      => 'csv-import',
                ]);
                $created++;
                break; // first URL-like cell per row is the subject
            }
        }
        fclose($h);

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
    }
}
