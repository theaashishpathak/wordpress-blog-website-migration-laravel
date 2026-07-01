<?php

declare(strict_types=1);

namespace App\Services\Content;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Single entry-point for cleaning rich-text HTML coming from the
 * TipTap editor or the AI providers before it lands in the database.
 *
 * Configured to be PERMISSIVE for newsroom needs:
 *   - keeps p, br, h2-h6, ul/ol/li, blockquote, pre/code
 *   - keeps strong/em/u/s, a (with rel/target), img (with alt/title)
 *   - keeps figure/figcaption, hr, table family
 *   - keeps iframe ONLY for YouTube embeds (allowlisted host)
 *   - strips on* event handlers, javascript: URLs, <script>, <style>
 *
 * The result is safe to render with {!! $html !!} on the public site.
 */
final class HtmlSanitizer
{
    private ?HTMLPurifier $purifier = null;

    public function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        // Cheap pre-pass — collapse repeated empty paragraphs that
        // editors love to emit, normalise NBSP and zero-width chars.
        $html = str_replace(["\u{00A0}", "\u{200B}"], [' ', ''], $html);
        $html = (string) preg_replace('#(<p>\s*</p>\s*){2,}#i', '<p></p>', $html);

        return $this->purifier()->purify($html);
    }

    /**
     * Plain-text excerpt — handy for meta descriptions and search
     * indexing. Strips ALL tags and collapses whitespace.
     */
    public function toPlainText(?string $html, int $maxLength = 0): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $text = (string) preg_replace('/\s+/u', ' ', strip_tags($html));
        $text = trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
            $text = rtrim(mb_substr($text, 0, $maxLength - 1)).'…';
        }

        return $text;
    }

    private function purifier(): HTMLPurifier
    {
        if ($this->purifier !== null) {
            return $this->purifier;
        }

        $config = HTMLPurifier_Config::createDefault();

        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Nofollow', false);
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
        $config->set('AutoFormat.RemoveEmpty', false);    // keep <p></p> = paragraph break
        $config->set('AutoFormat.AutoParagraph', false);  // editor already paragraphs
        $config->set('CSS.AllowTricky', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);

        // Cache files live in framework storage so they survive deploys.
        $cacheDir = storage_path('framework/cache/htmlpurifier');
        if (! is_dir($cacheDir) && ! @mkdir($cacheDir, 0775, true) && ! is_dir($cacheDir)) {
            $config->set('Cache.DefinitionImpl', null);
        } else {
            $config->set('Cache.SerializerPath', $cacheDir);
        }

        // Allow iframes for YouTube embeds. We constrain to youtube hosts
        // so the editor's YouTube extension keeps working.
        $config->set('HTML.SafeIframe', true);
        $config->set(
            'URI.SafeIframeRegexp',
            '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%'
        );

        // Custom HTML definition — add modern tags HTMLPurifier doesn't
        // know about by default (figure/figcaption, semantic roles).
        // DefinitionID + DefinitionRev are required when the cache is
        // enabled; bump the rev whenever this definition changes so the
        // cached definition is regenerated.
        $config->set('HTML.DefinitionID', 'newspilot.content.v1');
        $config->set('HTML.DefinitionRev', 1);
        if ($def = $config->maybeGetRawHTMLDefinition()) {
            $def->addElement('figure', 'Block', 'Flow', 'Common');
            $def->addElement('figcaption', 'Block', 'Flow', 'Common');
            $def->addAttribute('a', 'rel', 'Text');
            $def->addAttribute('img', 'loading', 'Enum#lazy,eager');
            $def->addAttribute('img', 'decoding', 'Enum#async,sync,auto');
            $def->addElement('iframe', 'Inline', 'Empty', 'Common', [
                'src' => 'URI',
                'width' => 'Length',
                'height' => 'Length',
                'frameborder' => 'Length',
                'allowfullscreen' => 'Bool',
                'allow' => 'Text',
                'title' => 'Text',
            ]);
        }

        return $this->purifier = new HTMLPurifier($config);
    }
}
