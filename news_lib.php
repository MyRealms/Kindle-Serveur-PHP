<?php
declare(strict_types=1);

function extract_first_image_url(SimpleXMLElement $item): string {
    // enclosure url="..." type="image/..."
    if (isset($item->enclosure)) {
        foreach ($item->enclosure as $enc) {
            $attrs = $enc->attributes();
            if (!$attrs) continue;
            $url = trim((string)($attrs['url'] ?? ''));
            $type = strtolower(trim((string)($attrs['type'] ?? '')));
            if ($url !== '' && ($type === '' || strpos($type, 'image/') === 0)) {
                if (strpos($url, '//') === 0) $url = 'https:' . $url;
                if (filter_var($url, FILTER_VALIDATE_URL)) return $url;
            }
        }
    }

    // media:content / media:thumbnail
    $media = $item->children('http://search.yahoo.com/mrss/');
    if ($media) {
        if (isset($media->content)) {
            foreach ($media->content as $c) {
                $attrs = $c->attributes();
                if (!$attrs) continue;
                $url = trim((string)($attrs['url'] ?? ''));
                if ($url !== '') {
                    if (strpos($url, '//') === 0) $url = 'https:' . $url;
                    if (filter_var($url, FILTER_VALIDATE_URL)) return $url;
                }
            }
        }
        if (isset($media->thumbnail)) {
            foreach ($media->thumbnail as $t) {
                $attrs = $t->attributes();
                if (!$attrs) continue;
                $url = trim((string)($attrs['url'] ?? ''));
                if ($url !== '') {
                    if (strpos($url, '//') === 0) $url = 'https:' . $url;
                    if (filter_var($url, FILTER_VALIDATE_URL)) return $url;
                }
            }
        }
    }

    // content:encoded / description: <img src="...">
    $content = $item->children('http://purl.org/rss/1.0/modules/content/');
    $html = '';
    if ($content && isset($content->encoded)) $html .= (string)$content->encoded;
    $html .= "\n" . (string)($item->description ?? '');

    if ($html !== '' && preg_match('/<img[^>]+src\\s*=\\s*["\\\']([^"\\\']+)["\\\']/i', $html, $m)) {
        $url = html_entity_decode(trim((string)$m[1]), ENT_QUOTES, 'UTF-8');
        if (strpos($url, '//') === 0) $url = 'https:' . $url;
        if (filter_var($url, FILTER_VALIDATE_URL)) return $url;
    }

    return '';
}

function fetch_news(string $url, int $limit = 8): array {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "User-Agent: KindleServeur/1.0\r\nAccept: application/rss+xml, application/xml;q=0.9, */*;q=0.8\r\n",
        ],
        'https' => [
            'timeout' => 8,
        ],
    ]);

    $xml = @file_get_contents($url, false, $ctx);
    if ($xml === false) return [];

    $feed = @simplexml_load_string($xml);
    if ($feed === false || !isset($feed->channel->item)) return [];

    $items = [];
    foreach ($feed->channel->item as $item) {
        $title = trim((string)$item->title);
        $desc  = trim(strip_tags((string)$item->description));
        $link  = trim((string)$item->link);
        $date  = trim((string)$item->pubDate);
        $image = extract_first_image_url($item);
        $meta  = '';

        if ($date !== '') {
            $ts = strtotime($date);
            $meta = $ts ? date('d.m.Y H:i', $ts) : $date;
        }

        if ($title !== '') {
            $items[] = ['title' => $title, 'desc' => $desc, 'meta' => $meta, 'link' => $link, 'image' => $image];
        }

        if (count($items) >= $limit) break;
    }

    return $items;
}
