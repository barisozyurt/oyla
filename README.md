<p align="center">
  <img src="assets/img/logo.svg" alt="Oyla" width="180">
</p>

<p align="center"><strong>Türk dernekleri için kriptografik doğrulamalı dijital seçim yönetim sistemi.</strong></p>

<p align="center">
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/Lisans-MIT-1f7a4c.svg" alt="MIT"></a>
  <img src="https://img.shields.io/badge/PHP-8.3+-blue.svg" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/MariaDB-10.6+-orange.svg" alt="MariaDB">
  <img src="https://img.shields.io/badge/Testler-73%20geçiyor-1f7a4c.svg" alt="Tests">
  <img src="https://img.shields.io/badge/Durum-v0.2-brass.svg" alt="v0.2">
</p>

---

## Nedir?

Oyla, 5253 sayılı Dernekler Kanunu ile Dernekler Yönetmeliği'ne uygun şekilde tasarlanmış, Türkiye'deki derneklerin genel kurul seçimlerini dijital ortamda güvenli biçimde yönetmesini sağlayan açık kaynak bir yazılımdır.

Kağıt bazlı seçimlerin uzun sürmesi, sayım hataları ve itiraz riskleri göz önünde bulundurularak geliştirilmiştir. Sistem; fiziksel kimlik doğrulamayı, kriptografik oy güvencesini ve anlık sonuç yayınını tek bir platformda birleştirir.

---

## Hangi Sorunları Çözer?

**Kağıt oy pusulası karmaşası** — 200 üyeli bir dernekte sayım saatlerce sürer. Oyla ile tüm oylar dijital ortamda kullanılır, **sayım anında biter**.

**Sayım hataları ve itirazlar** — Her oy kriptografik hash ile kayıt altındadır; **manipülasyon teknik olarak imkânsızdır**.

**"Kim kime oy verdi?" tedirginliği** — Kimlik bilgisi ile oy bilgisi ayrı tablolarda tutulur ve birbirleriyle **hiçbir şekilde eşleştirilemez**.

**Hazirun takibi** — Her adım kayıt altında: 1. imza (kimlik ibrazı) ve 2. imza (oy kullanma teyidi) ayrı ayrı zaman damgasıyla tutulur.

**Tutanak hazırlama derdi** — Seçim kapandığında resmi tutanak **PDF olarak otomatik üretilir**.

---

## Ekranlar

### Giriş & Genel

| Giriş Ekranı | Ana Sayfa (Aktif Seçim) |
|:---:|:---:|
| ![Giriş](assets/screenshots/03-giris-ekrani.png) | ![Ana Sayfa](assets/screenshots/50-anasayfa-secim-acik.png) |

### Admin Paneli

| Yönetim Paneli | Aktivite Logu |
|:---:|:---:|
| ![Admin Dashboard](assets/screenshots/10-admin-dashboard.png) | ![Aktivite Log](assets/screenshots/11-admin-aktivite-log.png) |

| Kullanıcı Listesi | Seçim Yönetimi |
|:---:|:---:|
| ![Kullanıcılar](assets/screenshots/12-admin-kullanici-listesi.png) | ![Seçimler](assets/screenshots/14-admin-secim-listesi.png) |

| Sistem Durumu | Log Bütünlük Doğrulama |
|:---:|:---:|
| ![Sistem](assets/screenshots/15-admin-sistem-durumu.png) | ![Log Doğrulama](assets/screenshots/16-admin-log-dogrulama.png) |

### Üye & Oy Pusulası Yönetimi

| Üye Listesi | Üye Ekle |
|:---:|:---:|
| ![Üye Listesi](assets/screenshots/20-yonetim-uye-listesi.png) | ![Üye Ekle](assets/screenshots/21-yonetim-uye-ekle.png) |

| CSV İçe Aktarma | Oy Pusulası Yönetimi |
|:---:|:---:|
| ![CSV Import](assets/screenshots/22-yonetim-uye-import.png) | ![Oy Pusulası](assets/screenshots/23-yonetim-oy-pusulaları.png) |

### Divan Paneli

| Divan — Hazır | Divan — Seçim Açık |
|:---:|:---:|
| ![Divan Hazır](assets/screenshots/32-divan-panel-hazir.png) | ![Divan Açık](assets/screenshots/33-divan-secim-acik.png) |

### Görevli Masası

| Görevli Paneli | Görevli — Seçim Aktif |
|:---:|:---:|
| ![Görevli](assets/screenshots/40-gorevli-panel.png) | ![Görevli Aktif](assets/screenshots/43-gorevli-secim-acik.png) |

### Sonuç Ekranları

| Canlı Sonuçlar | Perde Modu (Projeksiyon) | Katılım Raporu |
|:---:|:---:|:---:|
| ![Sonuçlar](assets/screenshots/51-sonuc-canli.png) | ![Perde](assets/screenshots/55-sonuc-perdesi.png) | ![Katılım](assets/screenshots/56-katilim-raporu.png) |

### Mobil

| Mobil Ana Sayfa | Mobil Giriş |
|:---:|:---:|
| ![Mobil Ana](assets/screenshots/60-mobil-anasayfa.png) | ![Mobil Giriş](assets/screenshots/61-mobil-giris.png) |

---

## Seçim Günü Nasıl İşler?

### 1. Hazırlık (Genel Kurul Öncesi)

Yönetici **Yönetim Paneli**'ne giriş yapar, üye listesini sisteme yükler. Her üyenin adı, TC kimlik numarası ve telefon bilgisi kaydedilir. Seçim kurulları (YK, Denetleme, Disiplin vb.) ve adaylar tanımlanır.

### 2. Divan Kurulunun Oluşması

**Divan Paneli**'nden divan başkanı, üyeleri ve kâtip girilir. Tüm kurullar hazır olduğunda "Seçimi Başlat" butonuna basılır.

### 3. Kayıt Masasında 5 Adımlı Akış

```
Üye gelir → Kimlik ibraz → 1. İmza → Token SMS'le gönderilir → Üye oylar → 2. İmza
```

Görevli hiçbir zaman kimin kime oy verdiğini göremez. Sadece oy kullanılıp kullanılmadığını takip eder.

### 4. Oy Kullanma

Üye telefonundaki bağlantıyı açar. Her kurul için aday listesi görüntülenir, kontenjan kadar seçim yapılır. "Oyumu Gönder" ile oy kaydedilir, oy geri alınamaz. SMS ile makbuz kodu iletilir.

### 5. Canlı Sonuçlar

Salon ekranına yansıtılan sonuç ekranı oylar geldikçe güncellenir. Seçim kapanınca "Resmi Sonuçlar" başlığı belirir.

### 6. Tutanak

Divan başkanı PDF tutanak oluşturur: divan kurulu, katılım istatistikleri, kurul bazlı sonuçlar, güvenlik özeti ve imza alanları. Şeffaflık için tüm commitment hash'leri CSV olarak indirilebilir.

---

## Özellikler

### Seçim Yönetimi
- Çoklu kurul (YK, Denetleme, Disiplin vb.) — ayrı kota ve yedek tanımı
- Divan kurulu yönetimi, seçim başlatma/kapatma, hazirun takibi
- PDF tutanak otomatik üretimi (TCPDF)
- Seçim geçmişi ve arşiv

### Güvenlik & Kriptografi
- HMAC-SHA256 token doğrulama — plaintext asla veritabanında tutulmaz
- HKDF+HMAC v1 commitment hash şeması
- HMAC hash zinciri ile tamper-evident aktivite logu
- DB-destekli rate limiter — üstel geri çekilme ile IP+endpoint bazlı
- CSRF double-submit koruması
- Atomic token burn — oy kaydı + token iptali tek transaction

### Anonimlik Garantisi
- `votes` tablosunda `member_id` sütunu yoktur
- `tokens` ve `votes` tabloları hiçbir zaman JOIN yapılmaz
- Kimlik bilgisi ile oy bilgisi teknik olarak eşleştirilemez

### Üye & Aday Yönetimi
- Tek tek veya CSV ile toplu üye ekleme
- Fotoğraf yükleme (UUID yeniden adlandırma, MIME doğrulama)
- TC kimlik checksum doğrulaması

### Görevli Masası
- 5 adımlı check-in: Kimlik → 1. İmza → Token → Oy Bekleme → 2. İmza
- QR kod + SMS ile oy bağlantısı
- Gerçek zamanlı oy durum takibi

### Sonuç Ekranı
- Canlı bar chart (5 sn. polling)
- Perde/projeksiyon modu — tam ekran, karanlık tema, otomatik kurul rotasyonu
- Katılım raporu ve oranları

### Admin Paneli
- Kullanıcı yönetimi (CRUD, rol bazlı)
- HMAC hash zinciri bütünlük doğrulama
- Sistem durumu izleme (DB, SMS, disk)
- Commitment hash CSV export
- Yapılandırılabilir veri anonim hale getirme (KVKK)

### Altyapı
- Parola politikası: 12+ karakter, büyük/küçük harf, rakam, tekrar engeli, kara liste
- Structured JSON logging, hassas alan maskeleme
- Global exception handler
- Dosya bazlı iş kuyruğu
- `/health` endpoint (uptime probe)
- 73 test (Unit + Integration) — CI/CD GitHub Actions

---

## Güvenlik Mimarisi

```
Token Üretimi   →  HMAC-SHA256(üye_id + zaman + TOKEN_SECRET)
                   Plaintext token sadece SMS/QR'da; veritabanında yalnızca hash
Oy Kaydı        →  HKDF+HMAC v1: commitment = HMAC(HKDF(token+secret), oy+tuz)
Anonimlik       →  member_id ASLA votes tablosuna yazılmaz; JOIN mümkün değil
Çift Oy Engeli  →  Atomic token burn — PDO transaction içinde: oy yaz + token sil
Doğrulama       →  Üye makbuz kodu ile bağımsız olarak teyit edebilir
Şeffaflık       →  Seçim kapanınca commitment hash listesi CSV olarak indirilebilir
Audit Log       →  HMAC prev_hash zinciri — tek satır değişse zincir bozulur
```

---

## Teknoloji

| Katman | Teknoloji |
|--------|-----------|
| Backend | PHP 8.3+ — saf MVC, framework yok |
| Veritabanı | MariaDB 10.6+ |
| Frontend | Bootstrap 5.3 + Vanilla JS |
| Tasarım | Source Serif 4 + IBM Plex Sans, özel design system |
| PDF | TCPDF 6.6 |
| SMS | Netgsm API (mock modu mevcut) |
| Sunucu | Docker — Nginx 1.25 + PHP-FPM 8.3 + MariaDB 10.6 |
| Test | PHPUnit 10.5 — 73 test, 336 assertion |
| Statik Analiz | PHPStan seviye 5 |
| CI | GitHub Actions |

---

## Kurulum

### Geliştirme

```bash
git clone https://github.com/barisozyurt/oyla.git
cd oyla
cp .env.example .env
bin/install               # Secret üretir, admin parolası sorar, demo veri yükler
docker compose up -d
docker compose exec php composer install
docker compose exec php bin/migrate
```

`bin/install` her kurulumda:
1. `APP_SECRET` ve `TOKEN_SECRET` için 64 karakterlik kriptografik rastgele değer üretir
2. Veritabanı parolaları için güçlü rastgele değer önerir
3. Admin parolasını **etkileşimli** sorar — min. 12 karakter, sözlük kontrolü
4. `SEED_DEMO_DATA=true` ise demo kullanıcıların **parolaları rastgele üretilip ekrana yazılır**

### Production

```bash
cp .env.example .env              # APP_ENV=production, APP_DEBUG=false
bin/install --no-demo
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
docker compose exec php composer install --no-dev --optimize-autoloader
docker compose exec php bin/migrate
```

---

## Klasör Yapısı

```
oyla/
├── app/
│   ├── Controllers/   AuthController, AdminController, AdminUsers/Elections/SystemController,
│   │                  DivanController, GorevliController, MemberController, BallotController,
│   │                  ElectionController, VoteController, ResultController, ReceiptController
│   ├── Models/        Election, User, Member, Ballot, Candidate, Token, Vote, Receipt, Divan
│   ├── Views/         layouts/ + her ekran için template + hata sayfaları (403/404/500/503)
│   ├── Core/          Router, Database, Controller, Model, View, App, Config,
│   │                  Logger, ErrorHandler, RateLimiter, Validator, PasswordPolicy, Queue
│   └── Services/      CryptoService, TokenService, SmsService, ActivityLogService
├── bin/               install, migrate, backup, kvkk-anonymize, audit-monitor, queue-worker
├── database/
│   ├── migrations/    016 SQL migration dosyası
│   └── seeds/         Demo veri (seçim, üyeler, kurullar, adaylar)
├── docker/            Nginx vhost + PHP Dockerfile
├── public/
│   ├── assets/
│   │   ├── css/       design-system.css + app.css
│   │   ├── js/        nav.js + oylama.js
│   │   ├── img/       logo.svg, logo-dark.svg, logo-icon.svg
│   │   └── vendor/    bootstrap-icons/ (self-hosted)
│   └── uploads/       Üye fotoğrafları
├── assets/screenshots/       Tüm ekranların tam sayfa görüntüleri (35 dosya)
└── tests/
    ├── Unit/          RateLimiter, CryptoService, Validator, PasswordPolicy, Router
    └── Integration/   SecurityTest, VotingFlowTest, AnonymityTest
```

---

## Hukuki Uyumluluk

- **5253 sayılı Dernekler Kanunu** — Genel kurul usulü, organ seçimi, bildirim yükümlülüğü
- **TMK Md. 73, 80** — Genel kurul zorunluluğu
- **Dernekler Yönetmeliği Md. 14–15** — Toplantı ve oylama usulü
- **DY Ek Madde-2 (2020)** — Elektronik ortamda işlem

> Üye fiziksel olarak kayıt masasında kimliğini ibraz eder ve imzasını atar. Token bu kişiye özel üretilir. "Şahsen oy kullanma" şartı bu mekanizma ile karşılanır.

---

## Katkı

Katkıda bulunmak isteyenler [CONTRIBUTING.md](CONTRIBUTING.md) dosyasına göz atabilir.

---

## Lisans

MIT License — Detaylar için [LICENSE](LICENSE) dosyasına bakınız.

---

<p align="center">
  Geliştirici: <strong>Barış Özyurt</strong> — <a href="https://mirket.io">mirket.io</a>
</p>
