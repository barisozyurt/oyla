# Katkıda Bulunma Rehberi

Oyla'ya katkıda bulunmak istediğin için teşekkürler. Aşağıdaki adımları takip ederek projeye destek olabilirsin.

## Başlamadan Önce

1. Repo'yu fork'la ve kendi branch'inde çalış
2. `.env.example` dosyasını `.env` olarak kopyala ve ayarlarını yap
3. `docker-compose up -d` ile geliştirme ortamını kur
4. `docker-compose exec php composer install` ile bağımlılıkları yükle

## Geliştirme Kuralları

### Kod Standartları

- **PSR-12** kodlama standardı
- PHP 8.2+ type hint'leri tüm public method'larda zorunlu
- SQL sorguları her zaman **prepared statement** ile (PDO)
- HTML çıktısında her zaman `htmlspecialchars()` veya `e()` helper'ı
- Her POST formunda CSRF token

### Veritabanı

- Migration dosyaları `database/migrations/` altında numaralı `.sql` formatında
- Tablo adlarında prefix kullanılmaz
- `votes` tablosuna **asla** `member_id` eklenmez — bu projenin temel anonimlik garantisidir

### Frontend

- Bootstrap 5.3 + Vanilla JS (jQuery yok)
- Mobil öncelikli tasarım
- Türkçe arayüz

## Pull Request Süreci

1. Açıklayıcı bir branch adı kullan (`feat/gorevli-search`, `fix/token-expire` vb.)
2. Değişikliklerini küçük, anlaşılır commit'lere böl
3. PR açıklamasında ne yaptığını ve neden yaptığını belirt
4. Mevcut testlerin geçtiğinden emin ol: `docker-compose exec php vendor/bin/phpunit`
5. Yeni özellik ekliyorsan test de ekle

## Hata Bildirimi

GitHub Issues üzerinden bildirebilirsin. Mümkünse şunları ekle:

- Hatanın tekrar edilebilir adımları
- Beklenen ve gerçekleşen davranış
- Tarayıcı/ortam bilgisi

## Güvenlik Açıkları

Güvenlik açıklarını **public issue olarak açma**. Bunun yerine doğrudan mirket@mirket.io adresine mail at.

## Lisans

Katkıların MIT lisansı altında yayınlanır.
