<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Yoast-style SEO & readability analysis.
 * Returns colored "traffic-light" assessments and 0-100 scores.
 */
class SeoAnalyzer
{
    public const GOOD = 'good';
    public const OK = 'ok';
    public const BAD = 'bad';

    public static function analyze(array $d): array
    {
        $keyphrase = trim((string) ($d['focus_keyphrase'] ?? ''));
        $title     = trim((string) ($d['seo_title'] ?? $d['title'] ?? ''));
        $metaDesc  = trim((string) ($d['meta_description'] ?? ''));
        $slug      = trim((string) ($d['slug'] ?? ''));
        $contentHtml = (string) ($d['content'] ?? '');
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($contentHtml)));
        $words = $text === '' ? [] : preg_split('/\s+/', $text);
        $wordCount = count($words);
        $kp = Str::lower($keyphrase);

        $seo = [];

        if ($keyphrase === '') {
            $seo[] = self::r(self::BAD, 'No focus keyphrase set. Set a keyphrase to analyze this content.');
        } else {
            // Keyphrase in SEO title
            $seo[] = Str::contains(Str::lower($title), $kp)
                ? self::r(self::GOOD, 'Keyphrase appears in the SEO title.')
                : self::r(self::BAD, 'Keyphrase does not appear in the SEO title.');

            // Keyphrase at beginning of title
            $seo[] = Str::startsWith(Str::lower($title), $kp)
                ? self::r(self::GOOD, 'The SEO title starts with the keyphrase.')
                : self::r(self::OK, 'Try to place the keyphrase at the beginning of the SEO title.');

            // Keyphrase in meta description
            $seo[] = Str::contains(Str::lower($metaDesc), $kp)
                ? self::r(self::GOOD, 'Keyphrase appears in the meta description.')
                : self::r(self::BAD, 'Keyphrase does not appear in the meta description.');

            // Keyphrase in slug
            $seo[] = Str::contains(Str::lower($slug), Str::slug($keyphrase))
                ? self::r(self::GOOD, 'Keyphrase appears in the URL slug.')
                : self::r(self::OK, 'Keyphrase is not in the URL slug.');

            // Keyphrase in first paragraph
            $first = Str::lower(Str::words($text, 60, ''));
            $seo[] = Str::contains($first, $kp)
                ? self::r(self::GOOD, 'Keyphrase appears in the first paragraph.')
                : self::r(self::BAD, 'Keyphrase does not appear in the first paragraph of the content.');

            // Keyphrase density
            $occurrences = $kp !== '' ? substr_count(Str::lower($text), $kp) : 0;
            $density = $wordCount > 0 ? ($occurrences * str_word_count($keyphrase)) / $wordCount * 100 : 0;
            if ($density == 0) {
                $seo[] = self::r(self::BAD, 'Keyphrase density is 0%. Use the keyphrase in the content.');
            } elseif ($density < 0.5) {
                $seo[] = self::r(self::OK, sprintf('Keyphrase density is %.1f%% — a bit low (aim for 0.5–3%%).', $density));
            } elseif ($density <= 3.0) {
                $seo[] = self::r(self::GOOD, sprintf('Keyphrase density is %.1f%% — great.', $density));
            } else {
                $seo[] = self::r(self::OK, sprintf('Keyphrase density is %.1f%% — may be too high.', $density));
            }

            // Keyphrase in subheadings
            preg_match_all('/<h[2-4][^>]*>(.*?)<\/h[2-4]>/is', $contentHtml, $subs);
            $inSub = false;
            foreach ($subs[1] ?? [] as $s) {
                if (Str::contains(Str::lower(strip_tags($s)), $kp)) { $inSub = true; break; }
            }
            $seo[] = $inSub
                ? self::r(self::GOOD, 'Keyphrase appears in one or more subheadings.')
                : self::r(self::OK, 'Keyphrase is not used in any subheading.');

            // Keyphrase in image alt
            preg_match_all('/<img[^>]*alt=["\']([^"\']*)["\']/i', $contentHtml, $alts);
            $imgCount = substr_count(Str::lower($contentHtml), '<img');
            if ($imgCount === 0) {
                $seo[] = self::r(self::OK, 'No images found. Add an image with the keyphrase in its alt text.');
            } else {
                $altHit = false;
                foreach ($alts[1] ?? [] as $a) {
                    if (Str::contains(Str::lower($a), $kp)) { $altHit = true; break; }
                }
                $seo[] = $altHit
                    ? self::r(self::GOOD, 'An image alt attribute contains the keyphrase.')
                    : self::r(self::OK, 'No image alt attribute contains the keyphrase.');
            }
        }

        // SEO title length
        $tl = mb_strlen($title);
        $seo[] = ($tl >= 40 && $tl <= 60)
            ? self::r(self::GOOD, "SEO title length is good ($tl chars).")
            : self::r($tl === 0 ? self::BAD : self::OK, "SEO title length is $tl chars (aim for 40–60).");

        // Meta description length
        $ml = mb_strlen($metaDesc);
        if ($ml === 0) {
            $seo[] = self::r(self::BAD, 'No meta description. Add one.');
        } elseif ($ml < 120) {
            $seo[] = self::r(self::OK, "Meta description is short ($ml chars; aim for 120–156).");
        } elseif ($ml <= 156) {
            $seo[] = self::r(self::GOOD, "Meta description length is good ($ml chars).");
        } else {
            $seo[] = self::r(self::OK, "Meta description is long ($ml chars; keep under 156).");
        }

        // Content length
        if ($wordCount >= 300) {
            $seo[] = self::r(self::GOOD, "Content is $wordCount words — good length.");
        } elseif ($wordCount >= 150) {
            $seo[] = self::r(self::OK, "Content is $wordCount words — consider adding more (300+).");
        } else {
            $seo[] = self::r(self::BAD, "Content is only $wordCount words — too thin.");
        }

        // Links
        $hasLink = Str::contains($contentHtml, '<a ');
        $seo[] = $hasLink
            ? self::r(self::GOOD, 'Content contains links.')
            : self::r(self::OK, 'No links found in the content. Add internal/external links.');

        /* ---- readability ---- */
        $read = self::readability($contentHtml, $text, $words, $wordCount);

        return [
            'seo'         => $seo,
            'readability' => $read['checks'],
            'seo_score'   => self::score($seo),
            'readability_score' => $read['score'],
        ];
    }

    protected static function readability(string $html, string $text, array $words, int $wordCount): array
    {
        $checks = [];

        // Sentence length
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $long = 0;
        foreach ($sentences as $s) {
            if (str_word_count($s) > 20) { $long++; }
        }
        $pctLong = $sentences ? $long / count($sentences) * 100 : 0;
        $checks[] = $pctLong <= 25
            ? self::r(self::GOOD, sprintf('%.0f%% of sentences are long — good.', $pctLong))
            : self::r(self::OK, sprintf('%.0f%% of sentences are over 20 words — try shortening.', $pctLong));

        // Paragraph length
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $paras);
        $longP = 0;
        foreach ($paras[1] ?? [] as $p) {
            if (str_word_count(strip_tags($p)) > 150) { $longP++; }
        }
        $checks[] = $longP === 0
            ? self::r(self::GOOD, 'No overly long paragraphs.')
            : self::r(self::OK, "$longP paragraph(s) exceed 150 words.");

        // Subheading distribution
        $subs = preg_match_all('/<h[2-4][^>]*>/i', $html);
        if ($wordCount > 300 && $subs === 0) {
            $checks[] = self::r(self::BAD, 'Long content with no subheadings. Add H2/H3 headings.');
        } else {
            $checks[] = self::r(self::GOOD, 'Subheading distribution is fine.');
        }

        // Transition words
        $transitions = ['however', 'therefore', 'moreover', 'furthermore', 'in addition', 'for example',
            'as a result', 'consequently', 'finally', 'first', 'second', 'next', 'because', 'although', 'meanwhile'];
        $tw = 0;
        foreach ($transitions as $t) { $tw += substr_count(Str::lower($text), $t); }
        $pctTrans = count($sentences) ? $tw / count($sentences) * 100 : 0;
        $checks[] = $pctTrans >= 30
            ? self::r(self::GOOD, 'Good use of transition words.')
            : self::r(self::OK, sprintf('Only %.0f%% of sentences contain transition words (aim for 30%%).', $pctTrans));

        // Passive voice (rough heuristic)
        $passive = preg_match_all('/\b(is|are|was|were|be|been|being)\s+\w+(ed|en)\b/i', $text);
        $pctPassive = count($sentences) ? $passive / count($sentences) * 100 : 0;
        $checks[] = $pctPassive <= 10
            ? self::r(self::GOOD, 'Passive voice usage is low — good.')
            : self::r(self::OK, sprintf('%.0f%% of sentences may use passive voice (keep under 10%%).', $pctPassive));

        // Flesch reading ease
        $flesch = self::flesch($text, $words, $sentences);
        if ($flesch >= 60) {
            $checks[] = self::r(self::GOOD, sprintf('Flesch reading ease is %.0f — easy to read.', $flesch));
        } elseif ($flesch >= 40) {
            $checks[] = self::r(self::OK, sprintf('Flesch reading ease is %.0f — fairly difficult.', $flesch));
        } else {
            $checks[] = self::r(self::BAD, sprintf('Flesch reading ease is %.0f — very difficult.', $flesch));
        }

        return ['checks' => $checks, 'score' => self::score($checks)];
    }

    protected static function flesch(string $text, array $words, array $sentences): float
    {
        $wc = max(count($words), 1);
        $sc = max(count($sentences), 1);
        $syll = 0;
        foreach ($words as $w) { $syll += self::syllables($w); }
        return 206.835 - 1.015 * ($wc / $sc) - 84.6 * ($syll / $wc);
    }

    protected static function syllables(string $word): int
    {
        $word = strtolower(preg_replace('/[^a-z]/i', '', $word));
        if ($word === '') { return 0; }
        $word = preg_replace('/(?:[^laeiouy]es|ed|[^laeiouy]e)$/', '', $word);
        $word = preg_replace('/^y/', '', $word);
        preg_match_all('/[aeiouy]{1,2}/', $word, $m);
        return max(1, count($m[0]));
    }

    protected static function score(array $checks): int
    {
        if (! $checks) { return 0; }
        $map = [self::GOOD => 100, self::OK => 55, self::BAD => 15];
        $sum = 0;
        foreach ($checks as $c) { $sum += $map[$c['status']] ?? 0; }
        return (int) round($sum / count($checks));
    }

    public static function label(int $score): string
    {
        return $score >= 70 ? 'Good' : ($score >= 40 ? 'OK' : 'Needs improvement');
    }

    public static function color(int $score): string
    {
        return $score >= 70 ? '#4caf50' : ($score >= 40 ? '#ee7c1b' : '#dc3232');
    }

    protected static function r(string $status, string $text): array
    {
        return ['status' => $status, 'text' => $text];
    }
}
