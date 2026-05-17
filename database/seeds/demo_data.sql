-- =============================================================================
-- Oyla — Demo verisi (sadece development).
-- =============================================================================
-- ÖNEMLİ: Bu dosya artık KULLANICI ŞIFRESI İÇERMEZ.
-- Kullanıcı parolaları `bin/install` tarafından her kuruluma özel rastgele
-- üretilir ve `.install-demo-users.json`'a yazılır; ardından `bin/migrate`
-- bu hash'leri users tablosuna yükler.
--
-- Bu dosya sadece örnek bir seçim, üyeler, kurullar ve adaylar tanımlar.
-- =============================================================================

INSERT INTO elections (id, title, description, status, test_mode, created_at)
VALUES (
    1,
    'Demo Dernek Genel Kurul Seçimi',
    '2025 yılı olağan genel kurul organ seçimleri. Yönetim Kurulu, Denetleme Kurulu ve Disiplin Kurulu seçimleri yapılacaktır.',
    'draft',
    0,
    NOW()
)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- -----------------------------------------------------------------------------
-- Üyeler (10 örnek Türk üye — fake TC'ler, gerçek hayatla ilgisizdir)
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO members (id, election_id, sicil_no, tc_kimlik, name, phone, email, role, status)
VALUES
    (1,  1, 'S-0001', '12345678901', 'Ahmet Yılmaz',      '5321234567', 'ahmet.yilmaz@ornek.com',      'yk_adayi',          'waiting'),
    (2,  1, 'S-0002', '23456789012', 'Fatma Kaya',        '5334567890', 'fatma.kaya@ornek.com',        'yk_adayi',          'waiting'),
    (3,  1, 'S-0003', '34567890123', 'Mehmet Demir',      '5359876543', 'mehmet.demir@ornek.com',      'yk_adayi',          'waiting'),
    (4,  1, 'S-0004', '45678901234', 'Ayşe Çelik',        '5361122334', 'ayse.celik@ornek.com',        'denetleme_adayi',   'waiting'),
    (5,  1, 'S-0005', '56789012345', 'Mustafa Şahin',     '5375544332', 'mustafa.sahin@ornek.com',     'denetleme_adayi',   'waiting'),
    (6,  1, 'S-0006', '67890123456', 'Zeynep Arslan',     '5389988776', 'zeynep.arslan@ornek.com',     'disiplin_adayi',    'waiting'),
    (7,  1, 'S-0007', '78901234567', 'İbrahim Koç',       '5396677889', 'ibrahim.koc@ornek.com',       'disiplin_adayi',    'waiting'),
    (8,  1, 'S-0008', '89012345678', 'Hatice Doğan',      '5423344556', 'hatice.dogan@ornek.com',      'yk_adayi',          'waiting'),
    (9,  1, 'S-0009', '90123456789', 'Ömer Yıldız',       '5444455667', 'omer.yildiz@ornek.com',       'uye',               'waiting'),
    (10, 1, 'S-0010', '10234567890', 'Elif Özdemir',      '5455566778', 'elif.ozdemir@ornek.com',      'uye',               'waiting');

-- -----------------------------------------------------------------------------
-- Seçim kurulları (Oy pusulaları)
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO ballots (id, election_id, title, description, quota, yedek_quota, sort_order)
VALUES
    (1, 1, 'Yönetim Kurulu',    'Dernek yönetim kurulu asıl ve yedek üye seçimi', 7, 3, 1),
    (2, 1, 'Denetleme Kurulu',  'Denetleme kurulu asıl ve yedek üye seçimi',      3, 2, 2),
    (3, 1, 'Disiplin Kurulu',   'Disiplin kurulu asıl ve yedek üye seçimi',       3, 2, 3);

-- -----------------------------------------------------------------------------
-- Adaylar
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO candidates (id, ballot_id, member_id, name, title, candidate_no, sort_order)
VALUES
    (1,  1, 1,  'Ahmet Yılmaz',  'Mühendis',          'YK-01', 1),
    (2,  1, 2,  'Fatma Kaya',    'Öğretmen',          'YK-02', 2),
    (3,  1, 3,  'Mehmet Demir',  'Avukat',            'YK-03', 3),
    (4,  1, 8,  'Hatice Doğan',  'Doktor',            'YK-04', 4),
    (5,  1, NULL, 'Serkan Polat', 'Muhasebeci',        'YK-05', 5),
    (6,  2, 4,  'Ayşe Çelik',       'Mali Müşavir',  'DEN-01', 1),
    (7,  2, 5,  'Mustafa Şahin',    'Mühendis',      'DEN-02', 2),
    (8,  2, NULL, 'Berna Aksoy',    'Ekonomist',     'DEN-03', 3),
    (9,  2, NULL, 'Tarık Güven',    'Avukat',        'DEN-04', 4),
    (10, 3, 6,  'Zeynep Arslan',    'Hukukçu',       'DIS-01', 1),
    (11, 3, 7,  'İbrahim Koç',      'Emekli Hakim',  'DIS-02', 2),
    (12, 3, NULL, 'Cemil Erdoğan',  'Avukat',        'DIS-03', 3);
