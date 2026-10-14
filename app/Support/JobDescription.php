<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class JobDescription
{
    public const MAX_LENGTH = 100000;

    public function toHtml(?string $description): string
    {
        $description ??= '';

        if (! preg_match('/<\/?[a-z][^>]*>/i', $description)) {
            return '<p>'.nl2br(e($description)).'</p>';
        }

        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowMediaSchemes(['https'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->forceAttribute('img', 'loading', 'lazy')
            ->forceAttribute('img', 'referrerpolicy', 'no-referrer')
            ->withMaxInputLength(self::MAX_LENGTH * 4);

        foreach (['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'strong', 'b', 'em', 'i', 'u', 's', 'del', 'sub', 'sup', 'mark', 'blockquote', 'pre', 'code', 'ul', 'ol', 'li', 'br', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td'] as $tag) {
            $config = $config->allowElement($tag, ['style']);
        }

        $config = $config->allowElement('a', ['href', 'title'])
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'data-align'])
            ->allowAttribute('start', ['ol'])
            ->allowAttribute('colspan', ['th', 'td'])
            ->allowAttribute('rowspan', ['th', 'td'])
            ->blockElement('div');

        $html = (new HtmlSanitizer($config))->sanitize($description);
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrorHandling = libxml_use_internal_errors(true);

        try {
            $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorHandling);
        }

        foreach ($document->getElementsByTagName('*') as $element) {
            $this->sanitizePresentation($element);
        }

        $result = '';
        foreach ($document->getElementsByTagName('div')->item(0)?->childNodes ?? [] as $child) {
            $result .= $document->saveHTML($child);
        }

        return $result;
    }

    public function hasText(string $description): bool
    {
        $text = html_entity_decode(strip_tags($this->toHtml($description)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/[\s\p{Z}\x{200B}-\x{200D}\x{FEFF}]+/u', '', $text) !== '';
    }

    private function sanitizePresentation(DOMElement $element): void
    {
        $styles = [];

        foreach (explode(';', $element->getAttribute('style')) as $declaration) {
            [$property, $value] = array_pad(explode(':', $declaration, 2), 2, '');
            $property = strtolower(trim($property));
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $allowed = match ($property) {
                'text-align' => in_array($value, ['left', 'center', 'right', 'justify'], true),
                'color', 'background-color' => (bool) preg_match('/^(#[a-f0-9]{3,8}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\))$/i', $value),
                'font-size' => in_array($value, ['12px', '14px', '16px', '18px', '20px', '24px', '30px', '36px'], true),
                'font-family' => in_array($value, ['Arial', 'Georgia', 'Times New Roman', 'Verdana', 'Courier New', 'sans-serif', 'serif', 'monospace'], true),
                'line-height' => in_array($value, ['1', '1.25', '1.5', '1.75', '2'], true),
                default => false,
            };

            if ($allowed) {
                $styles[$property] = $property.': '.$value;
            }
        }

        $element->removeAttribute('style');
        if ($styles !== []) {
            $element->setAttribute('style', implode('; ', $styles));
        }

        if ($element->tagName === 'img') {
            if (! in_array($element->getAttribute('width'), ['25%', '50%', '75%', '100%'], true)) {
                $element->removeAttribute('width');
            }
            if (! in_array($element->getAttribute('data-align'), ['left', 'center', 'right'], true)) {
                $element->removeAttribute('data-align');
            }
        }

        foreach (['colspan', 'rowspan', 'start'] as $attribute) {
            if ($element->hasAttribute($attribute) && ! preg_match('/^[1-9]\d{0,2}$/', $element->getAttribute($attribute))) {
                $element->removeAttribute($attribute);
            }
        }
    }
}
