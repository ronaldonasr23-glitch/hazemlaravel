<?php

namespace App\Support;

use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Imports articles from a CSV (Title, Content, Focus_Keyword, Meta_Description,
 * Keyphrase_Synonyms, AI_Summary, Schema_Type) and schedules them to publish
 * automatically at a fixed cadence (e.g. one per day, one every other day).
 *
 * Imported posts are created with status "scheduled" and a future published_at.
 * The `articles:publish-due` command flips them to "published" when due.
 */
class ArticleImporter
{
    /** CSV header aliases → canonical field. Keys are normalized (lowercase, alnum only). */
    protected const COLUMNS = [
        'title'              => 'title',
        'content'            => 'content',
        'body'               => 'content',
        'focuskeyword'       => 'focus_keyphrase',
        'focuskeyphrase'     => 'focus_keyphrase',
        'metadescription'    => 'meta_description',
        'keyphrasesynonyms'  => 'keyphrase_synonyms',
        'synonyms'           => 'keyphrase_synonyms',
        'aisummary'          => 'ai_summary',
        'summary'            => 'ai_summary',
        'schematype'         => 'schema_type',
    ];

    /**
     * @return array{created:int,skipped:int,first:?Carbon,last:?Carbon,interval:int}
     */
    public static function fromCsv(string $path, int $intervalDays, Carbon $startAt, ?string $imagesZipPath = null): array
    {
        $intervalDays = max(1, $intervalDays);
        $created = 0;
        $skipped = 0;
        $first = null;
        $last = null;

        $authorId = User::query()->orderBy('id')->value('id');

        // Thumbnails ZIP: files named 1,2,3… are matched to CSV rows 1,2,3…
        [$imageIndex, $imageTmpDir] = $imagesZipPath
            ? self::extractImages($imagesZipPath)
            : [[], null];
        $rowNum = 0;

        $h = fopen($path, 'r');
        if ($h === false) {
            if ($imageTmpDir) {
                self::rrmdir($imageTmpDir);
            }

            return ['created' => 0, 'skipped' => 0, 'first' => null, 'last' => null, 'interval' => $intervalDays];
        }

        $map = null; // header index → canonical field

        while (($row = fgetcsv($h)) !== false) {
            // First non-empty row is the header.
            if ($map === null) {
                $map = self::mapHeader($row);
                continue;
            }

            if (self::rowIsEmpty($row)) {
                continue;
            }
            $rowNum++;

            $data = self::rowToFields($row, $map);
            $title = trim((string) ($data['title'] ?? ''));
            if ($title === '') {
                $skipped++;
                continue;
            }

            $slug = self::uniqueSlug($title);
            $content = self::buildContent($data['ai_summary'] ?? '', $data['content'] ?? '');
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
            $words = $plain === '' ? 0 : count(preg_split('/\s+/', $plain));

            $publishAt = $startAt->copy()->addDays($created * $intervalDays);

            $featured = isset($imageIndex[$rowNum])
                ? self::storeThumbnail($imageIndex[$rowNum], $slug)
                : null;

            $post = Post::create([
                'title'          => $title,
                'slug'           => $slug,
                'excerpt'        => Str::limit(trim((string) ($data['meta_description'] ?? '')) ?: $plain, 200),
                'content'        => $content,
                'featured_image' => $featured,
                'author_id'      => $authorId,
                'status'         => 'scheduled',
                'reading_time'   => max(1, (int) ceil($words / 200)),
                'published_at'   => $publishAt,
            ]);

            self::saveSeo($post, $data);

            $created++;
            $first ??= $publishAt;
            $last = $publishAt;
        }
        fclose($h);

        if ($imageTmpDir) {
            self::rrmdir($imageTmpDir);
        }

        return [
            'created'  => $created,
            'skipped'  => $skipped,
            'first'    => $first,
            'last'     => $last,
            'interval' => $intervalDays,
        ];
    }

    /**
     * Attach a thumbnails ZIP to articles already imported, matching each image to
     * the article from the SAME row of the original CSV (image N → CSV row N → the
     * post with that title/slug). Robust against other posts and ordering.
     *
     * @return array{updated:int,images:int,not_found:int}
     */
    public static function attachThumbnails(string $csvPath, string $zipPath): array
    {
        [$imageIndex, $tmpDir] = self::extractImages($zipPath);

        $updated = 0;
        $notFound = 0;
        $rowNum = 0;

        $h = fopen($csvPath, 'r');
        if ($h === false) {
            if ($tmpDir) {
                self::rrmdir($tmpDir);
            }

            return ['updated' => 0, 'images' => count($imageIndex), 'not_found' => 0];
        }

        $map = null;
        while (($row = fgetcsv($h)) !== false) {
            if ($map === null) {
                $map = self::mapHeader($row);
                continue;
            }
            if (self::rowIsEmpty($row)) {
                continue;
            }
            $rowNum++;

            $title = trim((string) (self::rowToFields($row, $map)['title'] ?? ''));
            if ($title === '' || ! isset($imageIndex[$rowNum])) {
                continue;
            }

            $post = Post::where('slug', Str::slug($title))->first()
                ?? Post::where('title', $title)->first();
            if (! $post) {
                $notFound++;
                continue;
            }

            if ($path = self::storeThumbnail($imageIndex[$rowNum], $post->slug)) {
                $post->update(['featured_image' => $path]);
                $updated++;
            }
        }
        fclose($h);

        if ($tmpDir) {
            self::rrmdir($tmpDir);
        }

        return ['updated' => $updated, 'images' => count($imageIndex), 'not_found' => $notFound];
    }

    /** @return array<int,string> header column index → canonical field name */
    protected static function mapHeader(array $row): array
    {
        $map = [];
        foreach ($row as $i => $name) {
            $key = self::norm($name);
            if (isset(self::COLUMNS[$key])) {
                $map[$i] = self::COLUMNS[$key];
            }
        }

        return $map;
    }

    /** @param array<int,string> $map */
    protected static function rowToFields(array $row, array $map): array
    {
        $out = [];
        foreach ($map as $i => $field) {
            $out[$field] = $row[$i] ?? '';
        }

        return $out;
    }

    protected static function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /** Prepend an "AI summary / key takeaways" callout to the article body. */
    protected static function buildContent(string $aiSummary, string $content): string
    {
        $content = trim($content);
        $box = self::takeawaysHtml($aiSummary);

        return $box . $content;
    }

    protected static function takeawaysHtml(string $summary): string
    {
        $summary = trim($summary);
        if ($summary === '') {
            return '';
        }

        $parts = preg_split('/<br\s*\/?>|\r\n|\n/i', $summary) ?: [];
        $items = [];
        foreach ($parts as $part) {
            $t = trim(preg_replace('/^[\-\*\x{2022}\s]+/u', '', trim($part)));
            if ($t !== '') {
                $items[] = '<li>' . $t . '</li>';
            }
        }

        if (! $items) {
            return '';
        }

        return '<div class="key-takeaways" style="background:#f3f6fb;border-left:4px solid #f05a28;'
            . 'padding:18px 22px;border-radius:8px;margin:0 0 28px;">'
            . '<strong style="display:block;color:#0c2340;text-transform:uppercase;letter-spacing:.5px;'
            . 'font-size:13px;margin-bottom:10px;">Key takeaways</strong>'
            . '<ul style="margin:0;padding-left:20px;color:#374151;">' . implode('', $items) . '</ul>'
            . '</div>';
    }

    protected static function saveSeo(Post $post, array $data): void
    {
        $seo = [
            'seo_title'          => $post->title,
            'focus_keyphrase'    => trim((string) ($data['focus_keyphrase'] ?? '')) ?: null,
            'meta_description'   => trim((string) ($data['meta_description'] ?? '')) ?: null,
            'keyphrase_synonyms' => trim((string) ($data['keyphrase_synonyms'] ?? '')) ?: null,
        ];

        $schema = trim((string) ($data['schema_type'] ?? ''));
        if ($schema !== '') {
            if (Str::endsWith($schema, 'Page')) {
                $seo['schema_page_type'] = $schema;
            } else {
                $seo['schema_article_type'] = $schema;
            }
        }

        $analysis = SeoAnalyzer::analyze([
            'focus_keyphrase'  => $seo['focus_keyphrase'],
            'seo_title'        => $seo['seo_title'],
            'title'            => $post->title,
            'meta_description' => $seo['meta_description'],
            'slug'             => $post->slug,
            'content'          => $post->content,
        ]);
        $seo['seo_score'] = $analysis['seo_score'] ?? null;
        $seo['readability_score'] = $analysis['readability_score'] ?? null;

        $post->seo()->create($seo);
    }

    protected static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $n = 2;
        while (Post::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    protected static function norm(string $s): string
    {
        // Strip BOM + non-alphanumeric, lowercase.
        $s = preg_replace('/^\xEF\xBB\xBF/', '', $s);

        return preg_replace('/[^a-z0-9]/', '', strtolower($s));
    }

    /**
     * Unzip a thumbnails archive to a temp dir and index images by their number.
     * Files named 1.jpg, 2.png, 03.webp… map to CSV rows 1, 2, 3…
     *
     * @return array{0:array<int,string>,1:?string} [number => file path, temp dir]
     */
    /** Diagnostics from the most recent ZIP extraction (for user-facing messages). */
    protected static array $lastZipDiag = [];

    public static function lastZipDiag(): array
    {
        return self::$lastZipDiag;
    }

    protected static function extractImages(string $zipPath): array
    {
        self::$lastZipDiag = ['ext_zip' => class_exists(\ZipArchive::class), 'opened' => false, 'files' => [], 'images' => 0, 'error' => null];

        if (! class_exists(\ZipArchive::class)) {
            self::$lastZipDiag['error'] = 'The PHP "zip" extension is not enabled on the server.';

            return [[], null];
        }

        if (! is_file($zipPath)) {
            self::$lastZipDiag['error'] = 'The uploaded ZIP could not be read on the server.';

            return [[], null];
        }

        $dir = storage_path('app/article-images-' . uniqid());
        if (! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            self::$lastZipDiag['error'] = 'The server could not create a temp folder (storage not writable).';

            return [[], null];
        }

        $zip = new \ZipArchive();
        $open = $zip->open($zipPath);
        if ($open !== true) {
            self::$lastZipDiag['error'] = 'The ZIP file could not be opened (code ' . $open . ').';
            self::rrmdir($dir);

            return [[], null];
        }
        $zip->extractTo($dir);
        $zip->close();
        self::$lastZipDiag['opened'] = true;

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
        $map = [];
        $files = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if (! $file->isFile() || str_starts_with($file->getFilename(), '._')) {
                continue;
            }
            $files[] = $file->getFilename();
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, $allowed, true)) {
                continue;
            }
            $num = self::numberFromName(pathinfo($file->getFilename(), PATHINFO_FILENAME));
            if ($num !== null) {
                $map[$num] = $file->getPathname();
            }
        }

        self::$lastZipDiag['files'] = $files;
        self::$lastZipDiag['images'] = count($map);

        return [$map, $dir];
    }

    /** Pull the article number out of an image filename (1, 01, article-1, img_1…). */
    protected static function numberFromName(string $stem): ?int
    {
        if (ctype_digit($stem)) {
            return (int) $stem;
        }
        // Exactly one run of digits → use it; more than one is too ambiguous.
        if (preg_match_all('/\d+/', $stem, $m) === 1) {
            return (int) $m[0][0];
        }

        return null;
    }

    /** Copy a thumbnail into public/uploads/articles and return its web path. */
    protected static function storeThumbnail(string $src, string $slug): ?string
    {
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION)) ?: 'jpg';
        $dir = public_path('uploads/articles');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $dest = $dir . DIRECTORY_SEPARATOR . $slug . '.' . $ext;
        if (! @copy($src, $dest)) {
            return null;
        }

        return '/uploads/articles/' . $slug . '.' . $ext;
    }

    protected static function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
