<p align="center">
  <img src="assets/img/logo.svg" alt="Oyla" width="200">
</p>

<h1 align="center">Oyla</h1>

<p align="center"><strong>Türk dernekleri için güvenli, şeffaf ve kriptografik doğrulamalı dijital seçim yönetim sistemi.</strong></p>

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-8.x-blue.svg)](https://www.php.net/)
[![Status](https://img.shields.io/badge/Status-Geliştirme_Aşamasında-orange.svg)]()

---

## Nedir?

Oyla, 5253 sayılı Dernekler Kanunu ile Dernekler Yönetmeliği'ne uygun şekilde tasarlanmış, Türkiye'deki derneklerin genel kurul seçimlerini dijital ortamda güvenli biçimde yönetmesini sağlayan açık kaynak bir yazılımdır.

Kağıt bazlı seçimlerin uzun sürmesi, sayım hataları ve itiraz riskleri göz önünde bulundurularak geliştirilmiştir. Sistem; fiziksel kimlik doğrulamayı, kriptografik oy güvencesini ve anlık sonuç yayınını tek bir platformda birleştirir.

---

## Özellikler

- **Divan yönetimi** — Divan başkanı ve kurulu girişi, hazirun takibi, seçimi başlatma/kapatma
- **Üye yönetimi** — Üye ve aday listesi, fotoğraf yükleme, GSM/e-posta kaydı
- **Görevli masası** — Kimlik doğrulama, fiziksel imza kaydı, QR/SMS token üretimi
- **Üye oylama ekranı** — Mobil uyumlu, çoklu kurul desteği (YK, Denetleme, Disiplin vb.), oy makbuzu
- **Canlı sonuç ekranı** — Gerçek zamanlı güncelleme, perde/projeksiyon modu
- **Admin paneli** — Genel ayarlar, sistem logu, tüm ekranları izleme
- **Test modu** — Seçim öncesi sistem doğrulama, isteğe bağlı tutanağa ekleme
- **PDF tutanak** — Seçim sonuçları, divan imzaları, güvenlik özeti

---

## Güvenlik Mimarisi
```
Token Üretimi     →   UUID v4 + HMAC-SHA256(üye_id + zaman + gizli_anahtar)
Oy Kaydı          →   Commit hash: SHA256(oy + gizli_tuz + token)
Anonimlik         →   Kimlik tablosu ile oy tablosu ayrı — çapraz sorgu imkânsız
Çift oy engeli    →   Token burn (tek kullanımlık, 2 saat geçerli)
Doğrulama         →   Üye makbuz koduyla oyunun sayıldığını teyit edebilir
Şeffaflık         →   Seçim kapanınca tüm commit hash listesi kamuya açıklanır
```

---

## Ekranlar

| # | Ekran | Kullanıcı |
|---|---|---|
| 1 | Divan paneli | Divan başkanı |
| 2 | Yönetim paneli | Dernek yöneticisi |
| 3 | Görevli masası | Kayıt görevlisi |
| 4 | Sonuç / Perde | Salon ekranı |
| 5 | Oylama ekranı | Oy kullanan üye |
| 6 | Admin paneli | Sistem yöneticisi |

---

## Hukuki Uyumluluk

- 5253 sayılı Dernekler Kanunu
- 4721 sayılı Türk Medeni Kanunu (TMK Md. 73, 80)
- Dernekler Yönetmeliği Md. 14-15 (toplantı ve oylama usulü)
- Elektronik ortam: Yönetmelik Ek Madde-2 (2020) — 2FA ile kimlik doğrulama
- Gizli oy + açık sayım ilkesi: Commit-hash yöntemiyle karşılanmaktadır

> Üye fiziksel olarak kayıt masasında kimliğini ibraz eder ve imzasını atar.
> Token bu kişiye özel üretilir. Oy şahsen kullanılmış sayılır.

---

## Teknoloji
```
Backend:    PHP 8.x (MVC, saf PHP)
Veritabanı: MariaDB
Frontend:   Bootstrap 5 + Vanilla JS
PDF:        TCPDF
SMS:        Netgsm / İleti Hub
QR Kod:     endroid/qr-code
Sunucu:     Docker + Nginx + Let's Encrypt
```

---

## Kurulum

> Detaylı kurulum kılavuzu hazırlanıyor.
```bash
git clone https://github.com/barisozyurt/oyla.git
cd oyla
cp .env.example .env
# .env dosyasını düzenle
docker-compose up -d
```

---

## Klasör Yapısı
```
oyla/
├── app/
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── config/
├── public/
├── database/
│   └── migrations/
├── docker/
├── tests/
├── .env.example
├── docker-compose.yml
└── README.md
```

---

## Yol Haritası

- [x] Sistem mimarisi ve güvenlik tasarımı
- [x] UI/UX mockup (6 ekran)
- [ ] Veritabanı şeması
- [ ] PHP MVC iskelet
- [ ] Kimlik doğrulama modülü
- [ ] Oylama motoru
- [ ] PDF tutanak üretimi
- [ ] Test modu
- [ ] Gerçek SMS entegrasyonu
- [ ] İlk stabil sürüm (v1.0)

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
