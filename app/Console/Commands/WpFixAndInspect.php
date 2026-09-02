<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class WpFixAndInspect extends Command
{
    protected $signature = 'wp:fix-inspect';
    protected $description = 'Inspect media, excerpts, tags, seo_metas for diagnosed issues';

    public function handle(): int
    {
        $this->info('=== 1. CHECK MEDIA ROW & HTTP STATUS ===');
        $att = DB::connection('wordpress')->table('wp_posts')->where('post_type', 'attachment')->whereNotNull('guid')->where('guid', '!=', '')->first();
        if ($att) {
            $this->line("WP Attachment ID: {$att->ID}");
            $this->line("WP Attachment GUID: {$att->guid}");
            try {
                $res = Http::timeout(5)->get($att->guid);
                $this->line("HTTP Fetch Status for GUID: {$res->status()} (bytes: " . strlen($res->body()) . ")");
            } catch (\Throwable $e) {
                $this->line("HTTP Fetch Error: {$e->getMessage()}");
            }
        }

        $localMedia = DB::table('media')->first();
        $this->line("Local Media Record: " . json_encode($localMedia, JSON_PRETTY_PRINT));
        $this->line("Local file exists on disk ('" . ($localMedia->path ?? '') . "'): " . (isset($localMedia->path) && Storage::disk('public')->exists($localMedia->path) ? 'YES' : 'NO'));

        $this->info('=== 2. CHECK POST #11130 (Mathematics Teacher) ===');
        $post = DB::table('posts')->where('id', 11130)->first();
        $this->line("Post: " . json_encode($post, JSON_PRETTY_PRINT));

        $translations = DB::table('post_translations')->where('post_id', 11130)->get();
        $this->line("Post Translations: " . json_encode($translations, JSON_PRETTY_PRINT));

        $postTags = DB::table('post_tag')->where('post_id', 11130)->get();
        $this->line("Post Tags count in post_tag: {$postTags->count()}");
        foreach ($postTags as $pt) {
            $t = DB::table('tags')->where('id', $pt->tag_id)->first();
            $this->line(" - Tag ID {$pt->tag_id}: slug=" . ($t->slug ?? 'N/A'));
        }

        $seoMeta = DB::table('seo_metas')->where('seoable_id', 11130)->where('seoable_type', 'App\\Models\\Post')->first();
        $this->line("SEO Meta: " . json_encode($seoMeta, JSON_PRETTY_PRINT));

        $this->info('=== 3. CHECK WP ORIGINAL POST #11130 ===');
        $wpPost = DB::connection('wordpress')->table('wp_posts')->where('ID', 11130)->first();
        if ($wpPost) {
            $this->line("WP Post Title: {$wpPost->post_title}");
            $this->line("WP Post Excerpt: '{$wpPost->post_excerpt}'");
        }

        $wpPostTags = DB::connection('wordpress')->table('wp_term_relationships as tr')
            ->join('wp_term_taxonomy as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
            ->join('wp_terms as t', 'tt.term_id', '=', 't.term_id')
            ->where('tr.object_id', 11130)
            ->where('tt.taxonomy', 'post_tag')
            ->select('t.term_id', 't.name', 't.slug')
            ->get();
        $this->line("WP Original Post Tags count: {$wpPostTags->count()}");
        foreach ($wpPostTags as $wpt) {
            $this->line(" - WP Tag: ID={$wpt->term_id}, name={$wpt->name}, slug={$wpt->slug}");
        }

        $this->info('=== 4. CHECK WP OPTIONS ===');
        $opts = DB::connection('wordpress')->table('wp_options')->whereIn('option_name', ['siteurl', 'home', 'upload_path', 'upload_url_path'])->get();
        foreach ($opts as $o) {
            $this->line(" - Option {$o->option_name}: {$o->option_value}");
        }

        return 0;
    }
}
