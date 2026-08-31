{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0"><channel>
<title>NextGen - Tin tức công nghệ</title><link>{{ url('/tin-tuc') }}</link><description>Tin tức, đánh giá và tư vấn laptop mới nhất.</description><language>vi-VN</language>
@foreach($items as $item)<item><title>{{ $item->tieude }}</title><link>{{ url('/tin-tuc/'.$item->slug) }}</link><guid>{{ url('/tin-tuc/'.$item->slug) }}</guid><description>{{ $item->seo_description ?: $item->tomtat }}</description><pubDate>{{ optional($item->dang_luc)->toRssString() }}</pubDate><author>{{ $item->tacgia }}</author></item>@endforeach
</channel></rss>
