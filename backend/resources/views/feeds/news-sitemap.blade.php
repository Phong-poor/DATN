{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
@foreach($items as $item)<url><loc>{{ url('/tin-tuc/'.$item->slug) }}</loc><lastmod>{{ optional($item->updated_at)->toAtomString() }}</lastmod><news:news><news:publication><news:name>VinaTech</news:name><news:language>vi</news:language></news:publication><news:publication_date>{{ optional($item->dang_luc)->toAtomString() }}</news:publication_date><news:title>{{ $item->tieude }}</news:title></news:news></url>@endforeach
</urlset>
