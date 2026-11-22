{{-- Self-contained layout CSS for the dynamic blog (the captured hello-elementor
     theme ships no grid framework, so we don't rely on Bootstrap col-* classes). --}}
<style>
    .cms-wrap { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
    .cms-article .article-body { display: flex; flex-wrap: wrap; gap: 44px; align-items: flex-start; }
    .cms-article .article-main { flex: 1 1 0; min-width: 0; }
    .cms-article .article-aside { flex: 0 0 330px; max-width: 330px; }
    .cms-article .article-aside .widget { position: relative; }

    .cms-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
    .cms-related { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }

    .cms-pagination { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-top: 44px; }
    .cms-pagination a, .cms-pagination span {
        display: inline-flex; align-items: center; justify-content: center; min-width: 42px; height: 42px;
        padding: 0 12px; border-radius: 8px; border: 1px solid #e3e7ee; color: #0c2340; text-decoration: none; font-size: 14px; background: #fff;
    }
    .cms-pagination .current span, .cms-pagination span[aria-current] { background: #0c2340; color: #fff; border-color: #0c2340; }
    .cms-pagination .disabled span { opacity: .45; }

    /* The article body inherits the theme's base font but we normalise spacing. */
    .cms-article .entry-content h2, .cms-article .entry-content h3 { color: #0c2340; line-height: 1.3; margin: 34px 0 14px; }
    .cms-article .entry-content p { margin: 0 0 18px; }
    .cms-article .entry-content img { max-width: 100%; height: auto; border-radius: 8px; }
    .cms-article .entry-content ul, .cms-article .entry-content ol { margin: 0 0 18px; padding-inline-start: 22px; }

    @media (max-width: 991px) {
        .cms-article .article-aside { flex-basis: 100%; max-width: 100%; }
        .cms-grid { grid-template-columns: repeat(2, 1fr); }
        .cms-related { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .cms-grid, .cms-related { grid-template-columns: 1fr; }
    }
</style>
