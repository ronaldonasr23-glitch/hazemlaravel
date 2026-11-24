<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use Illuminate\Console\Command;

/**
 * Two jobs, both idempotent:
 *
 * 1. FIX the existing redirects whose from_path was stored percent-ENCODED
 *    (e.g. /%d8%b9%d9%86-...). The HandleRedirects middleware looks the path up
 *    DECODED (rawurldecode), so encoded rows could never match and never fired.
 *    We decode them in place.
 *
 * 2. BUILD the redirects Google still 404s on (Search Console "Not found 404"):
 *    - /archives/{id} and /ar/archives/{id}  → the post/page with that wp_id
 *    - the OLD clinic-services / surgical-services / خدمات-العيادة / 3mliat / …
 *      URL structure → the matching CURRENT page (or its nearest parent section).
 */
class FixAndBuild404Redirects extends Command
{
    protected $signature = 'wp:redirects-404 {--dry : only print what would change, write nothing}';

    protected $description = 'Decode broken Arabic redirects and add 301s for the Search Console 404 URLs';

    private bool $dry = false;

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry');

        $fixed = $this->decodeExistingRows();
        $arch  = $this->buildArchiveRedirects();
        $map   = $this->buildExplicitMap();

        $this->newLine();
        $this->info("Decoded broken rows: {$fixed}");
        $this->info("archives/{id} redirects: {$arch}");
        $this->info("explicit old→new redirects: {$map}");
        if ($this->dry) {
            $this->warn('DRY RUN — nothing was written. Re-run without --dry to apply.');
        }

        return self::SUCCESS;
    }

    /** 1. Decode any from_path that was stored percent-encoded so it can match. */
    private function decodeExistingRows(): int
    {
        $n = 0;
        foreach (Redirect::all() as $r) {
            if (! str_contains($r->from_path, '%')) {
                continue;
            }
            $decoded = '/' . trim(rawurldecode($r->from_path), '/');
            if ($decoded === $r->from_path) {
                continue;
            }
            $this->line("  decode: {$r->from_path}  →  {$decoded}");
            if ($this->dry) {
                $n++;
                continue;
            }
            // A decoded row may already exist (unique from_path) — drop the dupe.
            Redirect::where('from_path', $decoded)->where('id', '!=', $r->id)->delete();
            $r->from_path = $decoded;
            $r->save();
            $n++;
        }

        return $n;
    }

    /** 2a. /archives/{wp_id} (both languages) → the real current path of that content. */
    private function buildArchiveRedirects(): int
    {
        $targets = [];
        foreach (Post::whereNotNull('wp_id')->get(['wp_id', 'path', 'slug']) as $p) {
            $targets[$p->wp_id] = '/' . ltrim($p->path ?: $p->slug, '/');
        }
        foreach (Page::whereNotNull('wp_id')->get(['wp_id', 'path']) as $p) {
            $targets[$p->wp_id] ??= '/' . ltrim($p->path, '/');
        }

        $n = 0;
        foreach ($targets as $id => $to) {
            if ($to === '/' || $to === '/') {
                continue;
            }
            foreach (["/archives/{$id}", "/ar/archives/{$id}"] as $from) {
                $n += $this->put($from, $to);
            }
        }

        return $n;
    }

    /** 2b. Explicit old-structure → current-structure map. */
    private function buildExplicitMap(): int
    {
        // English / root-level old URLs → current path (no leading slash here).
        $en = [
            'clinic-services' => 'services',
            'clinic-services/treatment-of-excessive-humpback' => 'services/spine-deformity-correction/kyphosis-treatment',
            'clinic-services/treatment-of-spinal-tumors' => 'services/complex-spine-surgeries/spinal-tumours',
            'clinic-services/treatment-of-ankylosing-spondylitis' => 'services/spinal-discs-disorders/spine-disorders',
            'clinic-services/treatment-of-herniated-disc' => 'services/spinal-discs-disorders',
            'clinic-services/neck-pain-treatment' => 'services/spinal-discs-disorders/back-neck-pain-management',
            'clinic-services/lower-back-pain-treatment' => 'services/spinal-discs-disorders/back-neck-pain-management',
            'surgical-services' => 'services',
            'surgical-services/dynamic-scoliosis-correction' => 'services/spine-deformity-correction/dynamic-correction-vbt',
            'surgical-services/cervical-vertebrectomy-and-fusion' => 'services/complex-spine-surgeries',
            'surgical-services/thoracic-and-lumbar-spinal-fixation' => 'services/complex-spine-surgeries',
            'surgical-services/thoracic-and-lumbar-vertebrectomy' => 'services/complex-spine-surgeries/vertebral-fractures-reconstruction',
            'surgical-services/radiofrequency-ablation-rfa-therapy' => 'services/spinal-discs-disorders/back-neck-pain-management',
            'about-us' => 'about',
            'contact-us2' => 'contact-us',
            'exercises' => '',
            'index.php/blogs' => 'blogs',
        ];

        // Arabic old URLs. Keys WITHOUT the ar/ prefix; we register BOTH "/key"
        // and "/ar/key" → the same Arabic target, since Google holds both forms.
        $arBase = 'ar/الخدمات-والعمليات';
        $ar = [
            'خدمات-العيادة' => $arBase,
            'خدمات-العيادة/service1-1' => $arBase,
            'خدمات-العيادة/علاج-الجنف' => "$arBase/تقويم-تشوهات-العمود-الفقري/تصحيح-اعوجاج-العمود-الفقري-الجنف",
            'خدمات-العيادة/علاج-الحدب-الزائد' => "$arBase/تقويم-تشوهات-العمود-الفقري/علاج-التحدب-الكيوفوز",
            'خدمات-العيادة/علاج-آلام-الرقبة' => "$arBase/اضطرابات-غضاريف-العمود-الفقري/علاج-آلام-الظهر-والرقبة",
            'خدمات-العيادة/علاج-الإنزلاق-الغضروفي' => "$arBase/اضطرابات-غضاريف-العمود-الفقري",
            'خدمات-العيادة/علاج-الإنزلاق-الفقري' => "$arBase/اضطرابات-غضاريف-العمود-الفقري",
            'خدمات-العيادة/علاج-الإنحلال-الفقري' => "$arBase/اضطرابات-غضاريف-العمود-الفقري",
            'خدمات-العيادة/علاج-أورام-العمود-الفقري' => "$arBase/جراحات-العمود-الفقري-المعقدة/أورام-العمود-الفقري",
            '3mliat' => $arBase,
            '3mliat/جراحة-العمود-الفقري-بمساعدة-المجهر' => "$arBase/جراحات-العمود-الفقري-طفيفة-التوغل/جراحات-العمود-الفقري-الميكروسكوبية",
            '3mliat/3mod-fkry-mnzar' => "$arBase/جراحات-العمود-الفقري-طفيفة-التوغل/جراحات-العمود-الفقري-بالمنظار",
            '3mliat/العلاج-بالتردد-الحراري-rfa-لمفاصل-الفقر' => "$arBase/اضطرابات-غضاريف-العمود-الفقري/علاج-آلام-الظهر-والرقبة",
            '3mliat/استئصال-الغضروف-بالليزر' => "$arBase/جراحات-العمود-الفقري-طفيفة-التوغل",
            '3mliat/دمج-الفقرات-العنقية-الخلفي-pcf' => "$arBase/جراحات-العمود-الفقري-المعقدة",
            '3mliat/دمج-الفقرات-القطنية-الأمامي-alif' => "$arBase/جراحات-العمود-الفقري-المعقدة",
            '3mliat/استئصال-الفقرة-العنقية-ودمجها' => "$arBase/جراحات-العمود-الفقري-المعقدة",
            '3mliat/دمج-الفقرات-الجانبي-المتطرف-xlif' => "$arBase/جراحات-العمود-الفقري-المعقدة",
            '3mliat/استئصال-الفقرات-الظهرية-والقطنية' => "$arBase/جراحات-العمود-الفقري-المعقدة/علاج-كسور-الفقرات-وإعادة-البناء",
            '3mliat/التثبيت-الظهري-الصدرية-والقطني' => "$arBase/جراحات-العمود-الفقري-المعقدة",
            '3mliat/دمج-الفقرات-العنقية-الأمامية' => "$arBase/جراحات-العمود-الفقري-المعقدة",
            '3mliat/دمج-الفقرات-القطنية-بين-الأجسام-عبر-ال' => "$arBase/جراحات-العمود-الفقري-المعقدة",
            'من-نحن' => 'ar/عن-الدكتور',
            'تواصل-معنا' => 'ar/اتصل-بنا',
            'الحجز' => 'ar/حجز-موعد-عبر-الإنترنت',
            'medical-faq' => 'ar/الأسئلة-الشائعة',
            'المدونة' => 'ar/blogs',
        ];

        $n = 0;

        foreach ($en as $from => $to) {
            $n += $this->put('/' . $from, '/' . ltrim($to, '/') ?: '/');
            $n += $this->put('/en/' . $from, '/' . ltrim($to, '/') ?: '/');
        }

        foreach ($ar as $from => $to) {
            $to = '/' . ltrim($to, '/');
            $n += $this->put('/' . $from, $to);
            $n += $this->put('/ar/' . $from, $to);
        }

        // Arabic FAQ-topic taxonomy pages → the Arabic FAQ page.
        foreach ([
            'جراحات-التدخل-المحدود-والميكروسكوب-و',
            'التخطيط-الجراحي-الدقيق-والرعاية-الدو',
            'أمراض-الغضاريف،-عرق-النسا،-والفقرات-ا',
            'جراحات-العمود-الفقري-المعقدة-والإصلا',
            'ضيق-القناة-الشوكية-والانزلاق-الفقاري',
            'تشوهات-واعوجاج-العمود-الفقري-وجنف-الأ',
            'أورام-العمود-الفقري-وكسور-الفقرات-وإع',
            'علاج-وإدارة-آلام-الظهر-والرقبة',
        ] as $slug) {
            $n += $this->put('/ar/faq-topic/' . $slug, '/ar/الأسئلة-الشائعة');
        }

        return $n;
    }

    /** Upsert one redirect (decoded from_path). Returns 1 if valid, else 0. */
    private function put(string $from, string $to): int
    {
        $from = '/' . trim($from, '/');
        $to = '/' . trim($to, '/');
        if ($from === '/' || $to === '/' || $from === $to) {
            return 0;
        }

        $this->line("  {$from}  →  {$to}");
        if (! $this->dry) {
            Redirect::updateOrCreate(
                ['from_path' => $from],
                ['to_path' => $to, 'status_code' => 301, 'is_active' => true, 'source' => '404-fix']
            );
        }

        return 1;
    }
}
