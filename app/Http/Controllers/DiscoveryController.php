<?php

namespace App\Http\Controllers;

use App\Models\SeoPage;
use App\Services\SeoContent;
use Illuminate\Http\Response;

class DiscoveryController extends Controller
{
    public function sitemap(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        if (SeoContent::globallyIndexable()) {
            $settings = SeoPage::all()->keyBy('path');
            $inventory = SeoContent::inventory();
            foreach ($inventory as $path => $item) {
                $data = $settings->get($path)?->data ?? [];
                if (! ($data['indexable'] ?? true) || (! empty($data['canonical_path']) && $data['canonical_path'] !== $path && $inventory->has($data['canonical_path']))) {
                    continue;
                }
                $xml .= '<url><loc>'.htmlspecialchars(SeoContent::absolute($path), ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc></url>';
            }
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $text = SeoContent::globallyIndexable() ? "User-agent: *\nDisallow: /admin\nDisallow: /integrations\nDisallow: /search\nDisallow: /login\nSitemap: ".SeoContent::absolute('/sitemap.xml')."\n" : "User-agent: *\nDisallow: /\n";

        return response($text, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
