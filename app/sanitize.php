<?php
declare(strict_types=1);

/**
 * Очистка HTML из редактора по белому списку тегов и атрибутов.
 * Всё лишнее удаляется, неизвестные теги «разворачиваются» (остаётся текст).
 */
function sanitize_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $allowed = [
        'p' => [], 'br' => [], 'hr' => [], 'div' => [], 'span' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [],
        'h2' => [], 'h3' => [], 'h4' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [], 'figure' => [], 'figcaption' => [],
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'width', 'height'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
    ];
    $drop = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select',
             'textarea', 'link', 'meta', 'base', 'svg', 'math', 'template', 'noscript', 'head', 'title'];

    $doc = new DOMDocument('1.0', 'UTF-8');
    $prev = libxml_use_internal_errors(true);
    $encoded = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
    $doc->loadHTML('<!DOCTYPE html><html><body><div id="__root">' . $encoded . '</div></body></html>', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = $doc->getElementById('__root');
    if (!$root) {
        return '';
    }

    $walk = function (DOMNode $node) use (&$walk, $allowed, $drop, $doc) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->nodeName);
            if (in_array($tag, $drop, true)) {
                $node->removeChild($child);
                continue;
            }
            $walk($child);
            if (!isset($allowed[$tag])) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                if (!in_array($name, $allowed[$tag], true)) {
                    $child->removeAttribute($attr->nodeName);
                } elseif (in_array($name, ['href', 'src'], true) && !safe_url($attr->nodeValue ?? '', $name === 'src')) {
                    $child->removeAttribute($attr->nodeName);
                }
            }
            if ($tag === 'a') {
                if ($child->getAttribute('target') !== '' && $child->getAttribute('target') !== '_blank') {
                    $child->removeAttribute('target');
                }
                if ($child->getAttribute('target') === '_blank') {
                    $child->setAttribute('rel', 'noopener');
                }
            }
            if ($tag === 'img' && !$child->hasAttribute('src')) {
                $node->removeChild($child);
            }
        }
    };
    $walk($root);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim($out);
}

function safe_url(string $url, bool $imageOnly = false): bool
{
    $clean = preg_replace('~[\x00-\x20]+~', '', $url);
    if (preg_match('~^([a-z][a-z0-9+.\-]*):~i', (string)$clean, $m)) {
        $scheme = strtolower($m[1]);
        return $imageOnly ? in_array($scheme, ['http', 'https'], true)
                          : in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
    }
    return true; // относительный путь / якорь
}
