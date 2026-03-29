<p align="center">
  <img src="assets/img/logo.svg" alt="Oyla" width="200">
</p>

<h1 align="center">Oyla</h1>

<p align="center"><strong>Türk dernekleri için güvenli, şeffaf ve kriptografik doğrulamalı dijital seçim yönetim sistemi.</strong></p>

<p align="center">
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License: MIT"></a>
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.2+-blue.svg" alt="PHP"></a>
  <img src="https://img.shields.io/badge/Status-v0.1_Alpha-orange.svg" alt="Status">
</p>

---

## Nedir?

Oyla, 5253 sayılı Dernekler Kanunu ile Dernekler Yönetmeliği'ne uygun şekilde tasarlanmış, Türkiye'deki derneklerin genel kurul seçimlerini dijital ortamda güvenli biçimde yönetmesini sağlayan açık kaynak bir yazılımdır.

Kağıt bazlı seçimlerin uzun sürmesi, sayım hataları ve itiraz riskleri göz önünde bulundurularak geliştirilmiştir. Sistem; fiziksel kimlik doğrulamayı, kriptografik oy güvencesini ve anlık sonuç yayınını tek bir platformda birleştirir.

---

## Ekranlar

| # | Ekran | Yol | Kullanıcı | Durum |
|---|---|---|---|---|
| 1 | Divan Paneli | `/divan` | Divan başkanı | Tamamlandı |
| 2 | Yönetim Paneli | `/yonetim` | Dernek yöneticisi | Tamamlandı |
| 3 | Görevli Masası | `/gorevli` | Kayıt görevlisi | Tamamlandı |
| 4 | Sonuç / Perde | `/sonuc` | Salon ekranı (herkese açık) | Tamamlandı |
| 5 | Oylama Ekranı | `/oy/{token}` | Oy kullanan üye | Tamamlandı |
| 6 | Admin Paneli | `/admin` | Sistem yöneticisi | Tamamlandı |

---

## Özellikler

**Seçim Yönetimi**
- Divan kurulu tanımlama, seçim başlatma/kapatma
- Hazirun takibi — anlık katılım oranı ve ilerleme çubuğu
- Çoklu kurul desteği (YK, Denetleme, Disiplin vb.) ayrı kota ve yedek tanımıyla

**Üye ve Aday Yönetimi**
- Tek tek veya CSV ile toplu üye ekleme
- Fotoğraf yükleme (UUID ile yeniden adlandırma, MIME doğrulama)
- Aday listesi yönetimi — kurula bağlama, sıralama

**Kayıt Masası (Görevli)**
- 5 adımlı check-in akışı: Kimlik doğrula → 1. İmza → Token üret → Oy bekle → 2. İmza
- QR kod + SMS ile oy bağlantısı gönderimi
- Gerçek zamanlı oy durumu takibi (3 saniye polling)

**Oylama**
- Mobil öncelikli arayüz (360px'den itibaren uyumlu)
- Kurul bazlı aday seçimi, kota zorlama
- Commitment hash ile oy bütünlüğü
- Atomik token burn — oy kaydı ve token iptali tek transaction'da
- SMS ile makbuz kodu gönderimi

**Sonuç Ekranı**
- Canlı bar chart (5 saniye polling)
- Kazananlar yeşil, yedekler açık yeşil, diğerleri gri
- Perde/projeksiyon modu — salon ekranı için tam ekran, karanlık tema, otomatik kurul rotasyonu
- Seçim kapanınca "Resmi Sonuçlar" başlığı

**Admin**
- Kullanıcı yönetimi (görevli/divan hesapları)
- Aktivite logu — tüm işlemler kayıt altında
- Sistem durumu izleme (DB, SMS, disk alanı)
- Hash listesi CSV export — şeffaflık için
- Seçim override (acil durumlar)

**Test Modu**
- 8 sistem kontrolü (DB, SMS, token, hash, çift oy, yetki, sonuç, PDF)
- Sanal seçim simülasyonu — yapılandırılabilir üye sayısı
- Test verileri izole, tek tıkla temizlik

**PDF Tutanak**
- 7 bölümlü resmi seçim tutanağı (TCPDF)
- Divan imza alanları, katılım istatistikleri, kurul bazlı sonuçlar
- Güvenlik özeti, test kaydı (isteğe bağlı)
- Seçim kapanmamışsa "TASLAK" filigranı
- Türkçe karakter desteği (DejaVu Sans)

---

## Güvenlik Mimarisi

```
Token Üretimi     →   UUID v4 + HMAC-SHA256(üye_id + zaman + gizli_anahtar)
Oy Kaydı          →   Commitment hash: SHA256(oy + rastgele_tuz + token)
Anonimlik         →   Kimlik tablosu ile oy tablosu ayrı — çapraz sorgu imkânsız
Çift oy engeli    →   Token burn (tek kullanımlık, 2 saat geçerli)
Doğrulama         →   Üye makbuz koduyla oyunun sayıldığını teyit edebilir
Şeffaflık         →   Seçim kapanınca tüm commitment hash listesi CSV olarak indirilebilir
```

**Kritik kural:** `votes` tablosunda `member_id` sütunu yoktur. `tokens` ve `votes` tabloları hiçbir zaman JOIN yapılmaz. Bu iki kural, oy anonimliğinin temel garantisidir.

---

## Teknoloji

```
Backend:      PHP 8.2+ (saf MVC, framework yok)
Veritabanı:   MariaDB 10.6+
Frontend:     Bootstrap 5.3 + Vanilla JS
Tipografi:    Source Sans 3 + JetBrains Mono
PDF:          TCPDF 6.6
SMS:          Netgsm API (mock modu mevcut)
QR Kod:       endroid/qr-code 5.0
Token:        ramsey/uuid 4.7
Sunucu:       Docker (Nginx + PHP-FPM + MariaDB)
Test:         PHPUnit 10.5
```

---

## Kurulum

```bash
git clone https://github.com/barisozyurt/oyla.git
cd oyla
cp .env.example .env        # Düzenle: DB_PASS, TOKEN_SECRET, APP_SECRET
docker-compose up -d
docker-compose exec php composer install
```

Tarayıcıda `http://localhost` açın. Demo giriş bilgileri:

| Rol | Kullanıcı | Şifre |
|-----|-----------|-------|
| Admin | `admin` | `password` |
| Divan | `divan` | `password` |
| Görevli | `gorevli1` | `password` |

> Demo şifreleri yalnızca geliştirme içindir. Production'da mutlaka değiştirin.

---

## Klasör Yapısı

```
oyla/
├── app/
│   ├── Controllers/    10 controller (Auth, Admin, Divan, Gorevli, Vote, Result, ...)
│   ├── Models/         10 model (Election, Member, Ballot, Vote, Token, ...)
│   ├── Views/          20+ template (6 ekran + layoutlar + hata sayfaları)
│   ├── Core/           MVC çekirdeği (Router, Database, Controller, Model, View)
│   └── Services/       6 servis (Crypto, Token, SMS, QR, PDF, ActivityLog)
├── config/             app.php, database.php, sms.php
├── database/
│   ├── migrations/     10 SQL migration dosyası
│   └── seeds/          Demo veri
├── docker/             Nginx config + PHP Dockerfile
├── public/             index.php + assets (CSS, JS, img) + uploads
└── tests/
    ├── Unit/           Router, RateLimiter, CryptoService, SmsService testleri
    └── Integration/    Anonimlik, güvenlik, oylama akışı testleri
```

---

## Hukuki Uyumluluk

- **5253 sayılı Dernekler Kanunu** — Genel kurul usulü, bildirim yükümlülüğü
- **TMK Md. 73, 80** — Genel kurul zorunluluğu, organ seçimi
- **Dernekler Yönetmeliği Md. 14-15** — Toplantı ve oylama usulü
- **DY Ek Madde-2 (2020)** — Elektronik ortamda işlem ve 2FA

> Üye fiziksel olarak kayıt masasında kimliğini ibraz eder ve imzasını atar. Token bu kişiye özel üretilir. "Şahsen oy kullanma" şartı bu mekanizmayla karşılanır.

---

## Yol Haritası

- [x] Sistem mimarisi ve güvenlik tasarımı
- [x] Docker altyapısı (Nginx + PHP-FPM + MariaDB)
- [x] PHP MVC çekirdeği (Router, Database, Controller, Model, View)
- [x] Veritabanı şeması (10 tablo, migration'lar)
- [x] Kimlik doğrulama (rol bazlı, rate limiting, CSRF)
- [x] Çekirdek servisler (Crypto, Token, SMS, QR, PDF)
- [x] 6 ekranın tamamı (Divan, Yönetim, Görevli, Oylama, Sonuç, Admin)
- [x] Test modu (8 sistem kontrolü, sanal seçim simülasyonu)
- [x] PDF tutanak üretimi
- [x] UI/UX tasarımı (Source Sans 3, Oyla yeşili, mobil uyumlu)
- [ ] Gerçek ortam testi ve hata düzeltmeleri
- [ ] Netgsm canlı SMS entegrasyonu
- [ ] Güvenlik denetimi
- [ ] v1.0 kararlı sürüm

---

## Katkı

Katkıda bulunmak isteyenler [CONTRIBUTING.md](CONTRIBUTING.md) dosyasına göz atabilir.

---

## Lisans

MIT License — Detaylar için [LICENSE](LICENSE) dosyasına bakınız.

---

## İletişim

Geliştirici: **Barış Özyurt** — mirket@mirket.io
Proje: [github.com/barisozyurt/oyla](https://github.com/barisozyurt/oyla)
