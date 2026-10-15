<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Seeder bank soal CBT: 3 mata pelajaran x 150 soal = 450 soal.
 *
 * Cara kerja:
 *  - Soal "hafalan/konsep" ditulis manual (bank data) dengan pembahasan.
 *  - Soal hitungan (Matematika & Fisika) dibangkitkan oleh generator
 *    berparameter, jawaban benar dihitung otomatis, pengecoh dibuat dari
 *    kesalahan umum siswa. Hasilnya selalu konsisten dan reproducible
 *    (mt_srand dengan seed tetap).
 *  - Posisi kunci jawaban (A/B/C/D) disebar merata lalu diacak.
 *
 * Jalankan:  php artisan db:seed --class=QuestionBankSeeder
 */
class QuestionBankSeeder extends Seeder
{
    private const PER_SUBJECT = 150;

    public function run(): void
    {
        mt_srand(6); // seed tetap (dipilih agar tidak ada soal kembar) -> hasil selalu sama

        $subjects = [
            [
                'name' => 'Bahasa Indonesia',
                'description' => 'Teks eksplanasi, ide pokok, EYD/PUEBI, makna kata, kalimat efektif, majas, sinonim/antonim, dan tata bahasa.',
                'builder' => 'bahasaIndonesia',
            ],
            [
                'name' => 'Matematika',
                'description' => 'Aritmatika, aljabar dasar, persentase, geometri, pecahan, statistika dasar, barisan, dan logika matematika.',
                'builder' => 'matematika',
            ],
            [
                'name' => 'Ilmu Pengetahuan Alam (IPA)',
                'description' => 'Biologi dan ekosistem, fisika dasar dan energi, kimia sederhana, tata surya, serta struktur tubuh makhluk hidup.',
                'builder' => 'ipa',
            ],
        ];

        Model::unguard();
        DB::beginTransaction();

        try {
            $banks = [];
            foreach ($subjects as $s) {
                $banks[$s['name']] = $this->{$s['builder']}();
            }
            foreach ($subjects as $s) {
                $this->seedSubject($s['name'], $s['description'], $banks[$s['name']]);
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            Model::reguard();
        }
    }

    /* =====================================================================
     |  PENYIMPANAN
     * ===================================================================*/

    private function seedSubject(string $name, string $description, array $bank): void
    {
        $subject = Subject::firstOrCreate(
            ['name' => $name],
            ['description' => $description, 'is_active' => true]
        );

        if (Question::where('subject_id', $subject->id)->exists()) {
            $this->command?->warn("Mapel '{$name}' sudah memiliki soal, dilewati (hindari duplikasi).");
            return;
        }

        if (count($bank) !== self::PER_SUBJECT) {
            $this->command?->warn("Mapel '{$name}' menghasilkan ".count($bank).' soal (target '.self::PER_SUBJECT.').');
        }

        // Sebaran posisi kunci jawaban merata (A/B/C/D), lalu diacak.
        $positions = [];
        foreach (array_keys($bank) as $i) {
            $positions[] = $i % 4;
        }
        shuffle($positions);
        shuffle($bank);

        foreach ($bank as $i => $item) {
            $options = $item['wrong'];
            shuffle($options);
            array_splice($options, $positions[$i], 0, [$item['correct']]);

            $question = Question::create([
                'subject_id' => $subject->id,
                'payload' => $item['payload'],
                'score' => 1,
                'description' => $item['description'],
                'is_active' => true,
            ]);

            foreach ($options as $idx => $text) {
                Answer::create([
                    'question_id' => $question->id,
                    'text' => $text,
                    'is_correct' => $idx === $positions[$i],
                    'is_active' => true,
                ]);
            }
        }

        $this->command?->info("Mapel '{$name}': ".count($bank).' soal berhasil dibuat.');
    }

    /* =====================================================================
     |  HELPER UMUM
     * ===================================================================*/

    /** Membuat satu soal. $wrong boleh lebih dari 3; akan disaring unik & dipotong 3. */
    private function q(string $text, string $correct, array $wrong, string $exp, ?string $passage = null): array
    {
        $wrong = array_values(array_unique(array_diff($wrong, [$correct])));
        $wrong = array_slice($wrong, 0, 3);

        if (count($wrong) !== 3) {
            throw new RuntimeException("Pilihan jawaban tidak lengkap untuk soal: {$text}");
        }

        $payload = ($passage !== null ? '<blockquote>'.e($passage).'</blockquote>' : '').'<p>'.e($text).'</p>';

        return ['payload' => $payload, 'correct' => $correct, 'wrong' => $wrong, 'description' => $exp];
    }

    /** Soal dari pasangan [kunci, jawaban, (pembahasan)]; pengecoh diambil dari jawaban lain. */
    private function pairQuestions(array $pairs, string $template, string $expTemplate): array
    {
        $out = [];
        $answers = array_unique(array_column($pairs, 1));

        foreach ($pairs as $p) {
            [$k, $v] = $p;
            $pool = array_values(array_diff($answers, [$v]));
            shuffle($pool);
            $exp = $p[2] ?? str_replace(['{k}', '{v}'], [$k, $v], $expTemplate);
            $out[] = $this->q(str_replace('{k}', $k, $template), $v, array_slice($pool, 0, 3), $exp);
        }

        return $out;
    }

    private function r(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }

    private function pick(array $a): mixed
    {
        return $a[mt_rand(0, count($a) - 1)];
    }

    private function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }
        return abs($a);
    }

    private function lcm(int $a, int $b): int
    {
        return intdiv($a * $b, $this->gcd($a, $b));
    }

    /** Format angka gaya Indonesia (1.000 dan 2,5). */
    private function num(int|float $n): string
    {
        $n = round($n, 2);
        if ($n == floor($n)) {
            return number_format($n, 0, ',', '.');
        }
        return rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
    }

    private function rp(int|float $n): string
    {
        return 'Rp'.number_format((int) round($n), 0, ',', '.');
    }

    private function sup(int $n): string
    {
        return strtr((string) $n, ['0' => '⁰', '1' => '¹', '2' => '²', '3' => '³', '4' => '⁴', '5' => '⁵', '6' => '⁶', '7' => '⁷', '8' => '⁸', '9' => '⁹']);
    }

    /**
     * Menyusun pilihan numerik: kandidat pengecoh (kesalahan umum) diprioritaskan,
     * sisanya diisi kelipatan $step di sekitar jawaban benar.
     */
    private function numOpts(int|float $correct, array $cands, int|float $step, callable $fmt, bool $positive = true): array
    {
        $seen = [$fmt($correct) => true];
        $wrong = [];

        $add = function ($v) use (&$wrong, &$seen, $fmt, $positive) {
            if ($positive && $v <= 0) {
                return;
            }
            $s = $fmt($v);
            if (isset($seen[$s])) {
                return;
            }
            $seen[$s] = true;
            $wrong[] = $s;
        };

        foreach ($cands as $c) {
            $add($c);
        }
        for ($k = 1; count($wrong) < 3 && $k < 60; $k++) {
            $add($correct + $k * $step);
            $add($correct - $k * $step);
        }

        return [$fmt($correct), array_slice($wrong, 0, 3)];
    }

    /** Soal numerik: jawaban benar & pengecoh otomatis diformat. */
    private function mq(string $text, int|float $correct, array $cands, int|float $step, string $exp, ?string $unit = null, bool $positive = true, ?callable $fmt = null): array
    {
        $fmt ??= fn ($v) => $this->num($v).($unit ? ($unit === '°' ? '' : ' ').$unit : '');
        [$c, $w] = $this->numOpts($correct, $cands, $step, $fmt, $positive);

        return $this->q($text, $c, $w, $exp);
    }

    private function frac(int $n, int $d): string
    {
        $g = $this->gcd($n, $d);
        $n = intdiv($n, $g);
        $d = intdiv($d, $g);

        if ($d === 1) {
            return (string) $n;
        }
        if ($n > $d) {
            return intdiv($n, $d).' '.($n % $d).'/'.$d;
        }
        return "$n/$d";
    }

    private function lin(int $a, int $b): string
    {
        return $a.'x'.($b >= 0 ? ' + '.$b : ' - '.abs($b));
    }

    private function coef(int $c, string $v): string
    {
        $a = abs($c);
        return ($a === 1 ? '' : $a).$v;
    }

    private function poly2(int $cx, int $cy): string
    {
        $parts = [];
        if ($cx !== 0) {
            $parts[] = [$cx, 'x'];
        }
        if ($cy !== 0) {
            $parts[] = [$cy, 'y'];
        }
        if (! $parts) {
            return '0';
        }
        $s = '';
        foreach ($parts as $i => [$c, $v]) {
            $s .= $i === 0 ? ($c < 0 ? '-' : '') : ($c < 0 ? ' - ' : ' + ');
            $s .= $this->coef($c, $v);
        }
        return $s;
    }

    /* =====================================================================
     |  1. BAHASA INDONESIA (150 soal)
     * ===================================================================*/

    private function bahasaIndonesia(): array
    {
        return [
            ...$this->biSinonim(),       // 15
            ...$this->biAntonim(),       // 15
            ...$this->biMajas(),         // 15
            ...$this->biKataBaku(),      // 15
            ...$this->biEjaan(),         // 15
            ...$this->biMaknaUngkapan(), // 15
            ...$this->biKalimatEfektif(),// 10
            ...$this->biIdePokok(),      // 15
            ...$this->biEksplanasi(),    // 15
            ...$this->biTataBahasa(),    // 15
            ...$this->biFaktaOpini(),    // 5
        ];
    }

    private function biSinonim(): array
    {
        return $this->pairQuestions([
            ['abadi', 'kekal'], ['cendekiawan', 'intelektual'], ['gembira', 'riang'],
            ['lazim', 'biasa'], ['mufakat', 'sepakat'], ['pelik', 'rumit'],
            ['tangkas', 'gesit'], ['hakikat', 'inti'], ['karib', 'akrab'],
            ['musnah', 'lenyap'], ['elok', 'indah'], ['kikir', 'pelit'],
            ['santun', 'sopan'], ['tabah', 'teguh'], ['bohong', 'dusta'],
        ], 'Sinonim kata "{k}" adalah ...', 'Kata "{k}" bersinonim (sepadan maknanya) dengan "{v}".');
    }

    private function biAntonim(): array
    {
        return $this->pairQuestions([
            ['hemat', 'boros'], ['optimis', 'pesimis'], ['kuno', 'modern'],
            ['dermawan', 'kikir'], ['abstrak', 'konkret'], ['vertikal', 'horizontal'],
            ['ekspor', 'impor'], ['mayoritas', 'minoritas'], ['pasif', 'aktif'],
            ['sementara', 'permanen'], ['rajin', 'malas'], ['hilir', 'hulu'],
            ['cekatan', 'lamban'], ['awal', 'akhir'], ['tenang', 'gelisah'],
        ], 'Antonim kata "{k}" adalah ...', 'Lawan kata (antonim) "{k}" adalah "{v}".');
    }

    private function biMajas(): array
    {
        return $this->pairQuestions([
            ['Angin berbisik di telinga malam itu.', 'Personifikasi', 'Angin (benda mati) digambarkan dapat berbisik seperti manusia, sehingga termasuk majas personifikasi.'],
            ['Aku sudah menunggu seribu tahun di tempat ini.', 'Hiperbola', 'Ungkapan "seribu tahun" melebih-lebihkan kenyataan, sehingga termasuk majas hiperbola.'],
            ['Perpustakaan adalah jantung sekolah kami.', 'Metafora', 'Perpustakaan dibandingkan langsung dengan jantung tanpa kata pembanding, itulah metafora.'],
            ['Wajahnya secerah bulan purnama.', 'Simile (perumpamaan)', 'Perbandingan dinyatakan eksplisit dengan kata pembanding "se-", itulah simile.'],
            ['Terimalah bingkisan kecil dari kami ini.', 'Litotes', 'Pembicara merendahkan diri dengan menyebut pemberiannya "kecil", itulah litotes.'],
            ['Rajin sekali kamu, jam sembilan baru bangun!', 'Ironi', 'Pujian "rajin" dipakai untuk menyindir kebalikannya (malas), itulah ironi.'],
            ['Sudah tiga hari ini ia tidak tampak batang hidungnya.', 'Sinekdoke pars pro toto', 'Sebagian (batang hidung) dipakai untuk menyebut keseluruhan (orangnya), itulah pars pro toto.'],
            ['Beliau telah berpulang ke rahmatullah.', 'Eufemisme', 'Kata "berpulang" menggantikan kata "meninggal" agar lebih halus, itulah eufemisme.'],
            ['Aku merasa sendirian di tengah keramaian kota.', 'Paradoks', 'Dua hal tampak bertentangan (sendiri-ramai) tetapi mengandung kebenaran, itulah paradoks.'],
            ['Kita harus belajar, belajar, dan terus belajar demi masa depan.', 'Repetisi', 'Kata "belajar" diulang untuk penegasan, itulah repetisi.'],
            ['Indonesia berhasil merebut medali emas di ajang SEA Games.', 'Sinekdoke totum pro parte', 'Keseluruhan (Indonesia) dipakai untuk menyebut sebagian (atlet), itulah totum pro parte.'],
            ['Tangisannya membanjiri seluruh ruangan.', 'Hiperbola', 'Tangisan tidak mungkin membanjiri ruangan; ungkapan dilebih-lebihkan, itulah hiperbola.'],
            ['Tua muda, kaya miskin, semua berkumpul di balai desa.', 'Antitesis', 'Dua kata berlawanan (tua-muda, kaya-miskin) disandingkan, itulah antitesis.'],
            ['Ombak berlari mengejar bibir pantai.', 'Personifikasi', 'Ombak diberi sifat manusia (berlari, mengejar), itulah personifikasi.'],
            ['Kakak pergi ke sekolah naik Honda.', 'Metonimia', 'Nama merek (Honda) dipakai untuk menyebut barangnya (sepeda motor), itulah metonimia.'],
        ], 'Majas yang digunakan pada kalimat "{k}" adalah ...', '');
    }

    private function biKataBaku(): array
    {
        $words = [
            ['analisis', 'analisa', 'analysis', 'analisys', 'Guru meminta siswa melakukan ... terhadap data hasil percobaan.'],
            ['apotek', 'apotik', 'apothek', 'apoteck', 'Ibu membeli obat batuk di ... dekat rumah.'],
            ['risiko', 'resiko', 'riziko', 'risico', 'Setiap pekerjaan memiliki ... yang harus dipertimbangkan.'],
            ['kualitas', 'kwalitas', 'kuwalitas', 'qualitas', 'Pabrik itu selalu menjaga ... produknya.'],
            ['nasihat', 'nasehat', 'nasihad', 'nasihaat', 'Kakek memberikan ... agar kami rajin belajar.'],
            ['objek', 'obyek', 'objeck', 'obyeg', 'Candi Prambanan menjadi ... wisata yang ramai dikunjungi.'],
            ['standar', 'standard', 'setandar', 'setandard', 'Nilai kelulusan harus memenuhi ... yang telah ditetapkan sekolah.'],
            ['teknik', 'tehnik', 'tekhnik', 'tehnig', 'Pelatih mengajarkan ... dasar bermain bola voli.'],
            ['jadwal', 'jadual', 'jadwall', 'jawdal', 'Siswa wajib memeriksa ... pelajaran sebelum berangkat sekolah.'],
            ['izin', 'ijin', 'ijzin', 'ihzin', 'Ia meminta ... kepada wali kelas untuk pulang lebih awal.'],
            ['silakan', 'silahkan', 'silakhan', 'selakan', 'Bapak ... duduk di kursi yang telah disediakan.'],
            ['sistem', 'sistim', 'sestem', 'sisitem', 'Sekolah menerapkan ... ujian berbasis komputer.'],
            ['praktik', 'praktek', 'praktic', 'praktikk', 'Siswa melakukan ... di laboratorium setiap hari Kamis.'],
            ['frasa', 'frase', 'prasa', 'phrasa', '"Rumah besar" merupakan contoh ... nominal.'],
            ['konkret', 'kongkrit', 'konkrit', 'kongkret', 'Guru menunjukkan contoh ... agar siswa mudah memahami materi.'],
        ];

        $out = [];
        foreach ($words as [$baku, $a, $b, $c, $sentence]) {
            $out[] = $this->q(
                "Kata baku yang tepat untuk melengkapi kalimat \"{$sentence}\" adalah ...",
                $baku,
                [$a, $b, $c],
                "Bentuk baku yang benar adalah \"{$baku}\"; bentuk \"{$a}\" termasuk tidak baku."
            );
        }

        return $out;
    }

    private function biEjaan(): array
    {
        return [
            $this->q('Penulisan huruf kapital yang tepat terdapat pada kalimat ...',
                'Kami berlibur ke Danau Toba pada bulan Agustus.',
                ['Kami berlibur ke danau Toba pada bulan agustus.', 'kami berlibur ke Danau Toba pada bulan Agustus.', 'Kami berlibur Ke Danau Toba pada Bulan Agustus.'],
                'Huruf kapital dipakai pada awal kalimat serta nama geografi (Danau Toba) dan nama bulan (Agustus).'),
            $this->q('Penulisan kalimat langsung yang benar adalah ...',
                'Ibu bertanya, "Kapan kamu pulang?"',
                ['Ibu bertanya "Kapan kamu pulang?"', 'Ibu bertanya, "kapan kamu pulang?"', 'Ibu bertanya, "Kapan kamu pulang?."'],
                'Sebelum kutipan langsung diberi koma, huruf pertama kutipan kapital, dan tanda tanya diletakkan di dalam tanda petik.'),
            $this->q('Penggunaan tanda koma yang tepat terdapat pada kalimat ...',
                'Kami membeli apel, jeruk, mangga, dan anggur.',
                ['Kami membeli apel jeruk mangga, dan anggur.', 'Kami membeli, apel, jeruk, mangga dan anggur.', 'Kami, membeli apel jeruk, mangga, dan, anggur.'],
                'Koma dipakai untuk memisahkan unsur dalam perincian, dan sebelum kata "dan" pada unsur terakhir.'),
            $this->q('Penggunaan tanda koma pada kalimat majemuk bertingkat yang benar adalah ...',
                'Karena hujan deras, upacara dibatalkan.',
                ['Karena hujan deras upacara dibatalkan.', 'Upacara dibatalkan, karena hujan deras.', 'Karena, hujan deras upacara dibatalkan.'],
                'Anak kalimat yang mendahului induk kalimat dipisah koma; koma tidak dipakai bila anak kalimat berada di belakang.'),
            $this->q('Penggunaan tanda titik dua yang tepat terdapat pada kalimat ...',
                'Ibu membeli bahan makanan berikut: beras, gula, dan minyak.',
                ['Ibu membeli: beras, gula, dan minyak.', 'Ibu membeli beras: gula, dan minyak.', 'Ibu: membeli beras, gula, dan minyak.'],
                'Titik dua dipakai setelah kalimat lengkap yang diikuti rincian; tidak boleh memutus predikat dan objek.'),
            $this->q('Penulisan singkatan yang sesuai kaidah adalah ...',
                'dll.',
                ['dll', 'd.l.l.', 'DLL'],
                'Singkatan "dan lain-lain" ditulis "dll." dengan satu titik di akhir.'),
            $this->q('Penulisan bilangan pada awal kalimat yang benar adalah ...',
                'Tiga puluh siswa mengikuti lomba itu.',
                ['30 siswa mengikuti lomba itu.', 'Tiga Puluh siswa mengikuti lomba itu.', '3 puluh siswa mengikuti lomba itu.'],
                'Bilangan di awal kalimat ditulis dengan huruf, bukan angka.'),
            $this->q('Penulisan gabungan kata yang tepat adalah ...',
                'Ia bertanggung jawab atas tugas itu.',
                ['Ia bertanggungjawab atas tugas itu.', 'Ia bertanggung-jawab atas tugas itu.', 'Ia ber-tanggung jawab atas tugas itu.'],
                'Bentuk baku yang benar adalah "bertanggung jawab" (ditulis terpisah).'),
            $this->q('Tanda baca akhir kalimat yang tepat terdapat pada ...',
                'Alangkah indahnya pemandangan di Danau Toba!',
                ['Alangkah indahnya pemandangan di Danau Toba?', 'Alangkah indahnya pemandangan di Danau Toba,', 'Alangkah indahnya pemandangan di Danau Toba;'],
                'Kalimat seru yang menyatakan kekaguman diakhiri tanda seru (!).'),
            $this->q('Penulisan kata ulang yang benar adalah ...',
                'anak-anak',
                ['anak2', 'anak anak', 'anak - anak'],
                'Kata ulang ditulis lengkap dengan tanda hubung tanpa spasi, bukan angka 2.'),
            $this->q('Penulisan awalan se- yang diikuti nama diri yang benar adalah ...',
                'se-Sumatra Utara',
                ['seSumatra Utara', 'se Sumatra Utara', 'Se-sumatra utara'],
                'Awalan yang diikuti huruf kapital (nama diri) dihubungkan dengan tanda hubung: se-Sumatra Utara.'),
            $this->q('Huruf miring dalam tulisan digunakan untuk menuliskan ...',
                'kata asing dan nama ilmiah',
                ['nama orang dan nama bulan', 'awal kalimat dan nama tempat', 'judul bab dalam daftar isi'],
                'Huruf miring dipakai untuk kata/ungkapan asing, nama ilmiah, dan penegasan.'),
            $this->q('Penulisan kutipan langsung yang sesuai PUEBI adalah ...',
                'Ayah berkata, "Belajarlah dengan sungguh-sungguh."',
                ['Ayah berkata, "Belajarlah dengan sungguh-sungguh".', 'Ayah berkata "Belajarlah dengan sungguh-sungguh."', 'Ayah berkata, Belajarlah dengan sungguh-sungguh.'],
                'Tanda titik penutup kutipan diletakkan di dalam tanda petik, dan sebelum kutipan diberi koma.'),
            $this->q('Penulisan huruf kapital pada kata sapaan yang tepat adalah ...',
                'Kapan Bapak berangkat ke kantor?',
                ['kapan Bapak berangkat ke kantor?', 'Kapan bapak berangkat ke Kantor?', 'Kapan Bapak Berangkat ke kantor?'],
                'Kata kekerabatan yang dipakai sebagai sapaan diawali huruf kapital; kata biasa seperti "kantor" tidak.'),
            $this->q('Penulisan unsur terikat antar- yang tepat adalah ...',
                'Bus antarkota berhenti di terminal.',
                ['Bus antar kota berhenti di terminal.', 'Bus antar-kota berhenti di terminal.', 'Bus Antarkota berhenti di terminal.'],
                'Unsur terikat "antar-" ditulis serangkai dengan kata dasarnya: antarkota.'),
        ];
    }

    private function biMaknaUngkapan(): array
    {
        return $this->pairQuestions([
            ['panjang tangan', 'suka mencuri'], ['bertangan besi', 'memerintah dengan keras'],
            ['buah bibir', 'bahan pembicaraan orang'], ['meja hijau', 'pengadilan'],
            ['kambing hitam', 'orang yang dipersalahkan'], ['banting tulang', 'bekerja keras'],
            ['naik daun', 'menjadi terkenal'], ['gulung tikar', 'bangkrut'],
            ['buah tangan', 'oleh-oleh'],
            ['Air tenang menghanyutkan', 'orang yang tampak tenang justru menyimpan kemampuan besar atau bahaya'],
            ['Sedia payung sebelum hujan', 'bersiap-siap sebelum terjadi sesuatu'],
            ['Bagai air di daun talas', 'tidak berpendirian tetap'],
            ['Ringan sama dijinjing, berat sama dipikul', 'bekerja sama dalam suka dan duka'],
            ['Tong kosong nyaring bunyinya', 'orang yang kurang ilmu biasanya banyak bicara'],
            ['Sekali merengkuh dayung, dua tiga pulau terlampaui', 'sekali bekerja, beberapa maksud tercapai'],
        ], 'Makna ungkapan/peribahasa "{k}" adalah ...', 'Ungkapan/peribahasa "{k}" bermakna {v}.');
    }

    private function biKalimatEfektif(): array
    {
        $stems = [
            'Kalimat efektif yang tidak memakai bentuk jamak ganda adalah ...',
            'Kalimat yang bebas dari pleonasme (pemborosan kata) adalah ...',
            'Kalimat yang tidak memakai dua kata penghubung bermakna sama adalah ...',
            'Kalimat efektif yang memiliki subjek jelas adalah ...',
            'Kalimat efektif dengan struktur lengkap dan tidak kehilangan subjek terdapat pada ...',
            'Kalimat efektif yang memiliki kesejajaran bentuk kata adalah ...',
            'Kalimat yang hemat kata dan tepat penggunaan predikatnya adalah ...',
            'Kalimat yang menyatakan jumlah orang secara efektif adalah ...',
            'Kalimat efektif yang hubungan sebab-akibatnya logis adalah ...',
            'Kalimat efektif yang susunan unsurnya benar dan tidak berbelit adalah ...',
        ];

        return [
            $this->q($stems[0], 'Para siswa mengikuti upacara bendera dengan tertib.',
                ['Para siswa-siswa mengikuti upacara bendera dengan tertib.', 'Para siswa semuanya mengikuti upacara bendera dengan tertib.', 'Para siswa-siswi mengikuti upacara bendera dengan tertib sekali.'],
                'Kata "para" sudah bermakna jamak, sehingga tidak perlu diulang (siswa-siswa) atau ditambah "semuanya".'),
            $this->q($stems[1], 'Pemerintah akan menaikkan harga BBM bulan depan.',
                ['Pemerintah akan menaikkan harga BBM ke atas bulan depan.', 'Pemerintah akan menaikkan harga BBM yang naik bulan depan.', 'Pemerintah akan menaikkan kenaikan harga BBM bulan depan.'],
                '"Menaikkan" sudah berarti ke atas; tambahan "ke atas" atau "kenaikan" membuat kalimat boros (pleonasme).'),
            $this->q($stems[2], 'Ia belajar keras agar lulus ujian.',
                ['Ia belajar keras agar supaya lulus ujian.', 'Ia belajar keras, agar supaya dia lulus ujian.', 'Ia belajar keras supaya agar dapat lulus ujian.'],
                '"Agar" dan "supaya" bermakna sama, cukup dipakai salah satu.'),
            $this->q($stems[3], 'Siswa harus mematuhi tata tertib sekolah.',
                ['Bagi siswa harus mematuhi tata tertib sekolah.', 'Di sekolah mengharuskan siswa mematuhi tata tertib.', 'Siswa wajib harus mematuhi tata tertib sekolah.'],
                'Kalimat efektif memiliki subjek jelas; kata depan di depan subjek menghilangkan subjek, dan "wajib harus" berlebihan.'),
            $this->q($stems[4], 'Dalam rapat itu, panitia membahas jadwal lomba.',
                ['Dalam rapat itu membahas jadwal lomba.', 'Dalam rapat itu dibahas oleh panitia tentang jadwal lomba.', 'Rapat itu membahas oleh panitia jadwal lomba.'],
                '"Dalam rapat itu" hanyalah keterangan; subjek (panitia) harus dimunculkan agar kalimat lengkap.'),
            $this->q($stems[5], 'Kegiatan pramuka bertujuan melatih kedisiplinan, kemandirian, dan kepemimpinan.',
                ['Kegiatan pramuka bertujuan melatih kedisiplinan, mandiri, dan kepemimpinan.', 'Kegiatan pramuka bertujuan melatih disiplin, kemandirian, dan memimpin.', 'Kegiatan pramuka bertujuan melatih kedisiplinan, bermandiri, dan pemimpin.'],
                'Kalimat efektif memiliki kesejajaran (paralelisme): ketiga unsur perincian berbentuk kata benda berimbuhan ke-an.'),
            $this->q($stems[6], 'Ibu sedang memasak di dapur.',
                ['Ibu adalah sedang memasak di dapur.', 'Ibu itu adalah memasak di dapur.', 'Ibu sedang di dapur sedang memasak.'],
                'Kata "adalah" tidak dipakai di depan kata kerja, dan pengulangan "sedang" membuat kalimat boros.'),
            $this->q($stems[7], 'Banyak warga mengikuti kerja bakti pagi itu.',
                ['Banyak sekali warga-warga mengikuti kerja bakti pagi itu.', 'Banyak para warga mengikuti kerja bakti pagi itu.', 'Banyak warga yang mengikuti kerja bakti pada pagi hari itu pada waktu pagi.'],
                '"Banyak" sudah menyatakan jumlah jamak, sehingga tidak perlu "para" atau pengulangan warga-warga.'),
            $this->q($stems[8], 'Hujan deras mengakibatkan banjir di beberapa wilayah.',
                ['Hujan deras menyebabkan akibat banjir di beberapa wilayah.', 'Hujan deras mengakibatkan sehingga banjir di beberapa wilayah.', 'Akibat hujan deras mengakibatkan banjir di beberapa wilayah.'],
                'Penggunaan dua kata penghubung sebab-akibat sekaligus tidak logis; kalimat efektif cukup memakai satu predikat.'),
            $this->q($stems[9], 'Panitia menyediakan hadiah bagi para pemenang lomba.',
                ['Panitia menyediakan hadiah-hadiah bagi para pemenang-pemenang lomba.', 'Panitia menyediakan hadiah bagi pemenang-pemenang para lomba.', 'Hadiah menyediakan panitia bagi para pemenang lomba.'],
                'Struktur S-P-O-Ket jelas dan tidak ada pengulangan jamak yang berlebihan.'),
        ];
    }

    private function biIdePokok(): array
    {
        $topics = [
            ['Membaca buku secara rutin memberikan banyak manfaat bagi pelajar.', 'Wawasan siswa menjadi lebih luas karena mendapat informasi baru.', 'Selain itu, kemampuan berpikir kritis dan kosakata siswa ikut terlatih.', 'Manfaat membaca buku secara rutin bagi pelajar', 'Cara memilih buku bacaan yang menarik', 'Pentingnya perpustakaan di sekolah', 'Kosakata siswa yang masih terbatas'],
            ['Sampah plastik menjadi ancaman serius bagi lingkungan.', 'Plastik membutuhkan ratusan tahun untuk terurai di dalam tanah.', 'Sampah yang terbawa ke laut membahayakan nyawa penyu dan ikan.', 'Bahaya sampah plastik bagi lingkungan', 'Cara mendaur ulang botol plastik', 'Penyu sebagai hewan laut yang dilindungi', 'Proses terbentuknya tanah di alam'],
            ['Olahraga teratur sangat penting untuk menjaga kesehatan tubuh.', 'Jantung dan paru-paru bekerja lebih baik pada orang yang rajin berolahraga.', 'Berolahraga juga membantu tubuh mengendalikan berat badan.', 'Pentingnya olahraga teratur bagi kesehatan', 'Jenis olahraga yang paling populer', 'Penyebab kegemukan pada anak-anak', 'Fungsi jantung dan paru-paru'],
            ['Gotong royong merupakan budaya khas masyarakat Indonesia.', 'Warga desa bahu-membahu membersihkan selokan setiap akhir pekan.', 'Ketika ada tetangga yang membangun rumah, warga lain datang membantu tanpa diminta.', 'Gotong royong sebagai budaya masyarakat Indonesia', 'Cara membangun rumah yang kokoh', 'Kebersihan selokan di desa', 'Hubungan antartetangga di kota besar'],
            ['Penggunaan gawai yang berlebihan berdampak buruk bagi remaja.', 'Waktu belajar mereka tersita untuk bermain gim dan media sosial.', 'Mata mudah lelah dan jam tidur menjadi berantakan.', 'Dampak buruk penggunaan gawai berlebihan pada remaja', 'Manfaat gawai untuk belajar daring', 'Perkembangan teknologi gawai', 'Cara menghemat baterai gawai'],
            ['Hutan bakau berperan penting dalam melindungi wilayah pesisir.', 'Akar-akarnya yang kuat menahan hantaman ombak dan mencegah abrasi.', 'Kawasan ini juga menjadi tempat berkembang biak berbagai jenis ikan dan udang.', 'Peran penting hutan bakau bagi wilayah pesisir', 'Cara menanam bibit bakau', 'Penyebab terjadinya ombak besar', 'Jenis ikan yang hidup di laut dalam'],
            ['Disiplin waktu menjadi kunci keberhasilan seorang pelajar.', 'Siswa yang mengatur jadwal belajar dengan baik jarang terburu-buru saat ujian.', 'Tugas sekolah pun dapat diselesaikan tepat waktu tanpa begadang.', 'Disiplin waktu sebagai kunci keberhasilan pelajar', 'Cara membuat jadwal piket kelas', 'Penyebab siswa begadang', 'Kebiasaan buruk saat ujian'],
            ['Salah satu warisan budaya dunia yang megah adalah Candi Borobudur.', 'Bangunan batu di Magelang itu dihiasi ribuan panel relief.', 'Setiap tahun, wisatawan dari berbagai negara berdatangan untuk menyaksikannya.', 'Kemegahan Candi Borobudur sebagai warisan budaya dunia', 'Sejarah penjajahan di Pulau Jawa', 'Cara memelihara candi dari pelapukan', 'Harga tiket masuk tempat wisata'],
            ['Tidur yang cukup sangat dibutuhkan oleh pelajar.', 'Otak memerlukan waktu istirahat untuk menyimpan pelajaran yang telah dipelajari.', 'Siswa yang kurang tidur sering sulit berkonsentrasi di kelas.', 'Pentingnya tidur yang cukup bagi pelajar', 'Kesulitan siswa berkonsentrasi di kelas', 'Cara kerja otak manusia', 'Jadwal pelajaran di sekolah'],
            ['Banjir di perkotaan terutama disebabkan oleh buruknya pengelolaan lingkungan.', 'Warga masih membuang sampah ke sungai sehingga aliran air tersumbat.', 'Lahan resapan air terus berkurang akibat pembangunan gedung.', 'Penyebab banjir di perkotaan', 'Dampak banjir bagi kesehatan warga', 'Cara membersihkan sungai', 'Pembangunan gedung bertingkat'],
            ['Lebah madu memiliki pembagian tugas yang teratur dalam koloninya.', 'Lebah pekerja bertugas mencari nektar dan merawat larva.', 'Ratu lebah hanya bertugas bertelur untuk menjaga keberlangsungan koloni.', 'Pembagian tugas yang teratur pada koloni lebah madu', 'Cara memanen madu dari sarang', 'Manfaat madu bagi kesehatan manusia', 'Kehidupan ratu lebah yang istimewa'],
            ['Kejujuran harus ditanamkan kepada anak sejak usia dini.', 'Orang tua dapat memberi contoh dengan selalu berkata benar di rumah.', 'Guru juga perlu menghargai siswa yang berani mengakui kesalahannya.', 'Penanaman kejujuran pada anak sejak dini', 'Peran guru di sekolah dasar', 'Hukuman bagi anak yang berbohong', 'Kewajiban orang tua membiayai sekolah'],
            ['Energi surya merupakan sumber energi alternatif yang ramah lingkungan.', 'Cahaya matahari tersedia melimpah di negara tropis sepanjang tahun.', 'Pemanfaatannya tidak menghasilkan asap atau limbah berbahaya.', 'Energi surya sebagai energi alternatif ramah lingkungan', 'Harga panel surya yang mahal', 'Cara kerja pembangkit listrik tenaga air', 'Cuaca di negara tropis'],
            ['Wisata kuliner menjadi daya tarik utama di banyak kota di Indonesia.', 'Pengunjung dapat menikmati beragam makanan khas daerah dengan harga terjangkau.', 'Banyak pedagang kecil pun memperoleh penghasilan dari ramainya wisatawan kuliner.', 'Wisata kuliner sebagai daya tarik utama kota-kota di Indonesia', 'Cara membuka usaha rumah makan', 'Harga makanan di pasar tradisional', 'Keuntungan besar para pedagang kecil'],
            ['Kebiasaan menabung sejak kecil bermanfaat bagi masa depan.', 'Anak belajar menahan keinginan membeli barang yang tidak diperlukan.', 'Uang yang terkumpul dapat dipakai saat ada kebutuhan mendesak.', 'Manfaat menabung sejak kecil', 'Cara memilih celengan yang bagus', 'Kebutuhan mendesak dalam keluarga', 'Bunga yang diberikan oleh bank'],
        ];

        $out = [];
        foreach ($topics as $i => [$main, $s1, $s2, $idea, $w1, $w2, $w3]) {
            $inductive = $i % 2 === 1;
            $passage = $inductive
                ? "$s1 $s2 Dengan demikian, ".lcfirst($main)
                : "$main $s1 $s2";
            $exp = $inductive
                ? "Kalimat utama berada di akhir paragraf (paragraf induktif) dan didukung kalimat-kalimat sebelumnya. Ide pokoknya: {$idea}."
                : "Kalimat utama berada di awal paragraf (paragraf deduktif), kalimat berikutnya hanya menjelaskan. Ide pokoknya: {$idea}.";
            $out[] = $this->q('Ide pokok paragraf tersebut adalah ...', $idea, [$w1, $w2, $w3], $exp, $passage);
        }

        return $out;
    }

    private function biEksplanasi(): array
    {
        return [
            $this->q('Tujuan utama teks eksplanasi adalah ...',
                'menjelaskan proses terjadinya suatu fenomena alam atau sosial',
                ['menceritakan pengalaman pribadi penulis', 'membujuk pembaca untuk melakukan sesuatu', 'menggambarkan keadaan suatu objek secara rinci'],
                'Teks eksplanasi menjelaskan proses dan hubungan sebab-akibat suatu fenomena.'),
            $this->q('Struktur teks eksplanasi yang benar secara berurutan adalah ...',
                'pernyataan umum, deretan penjelas, interpretasi',
                ['orientasi, komplikasi, resolusi', 'tesis, argumen, penegasan ulang', 'identifikasi, deskripsi bagian, simpulan'],
                'Struktur teks eksplanasi: pernyataan umum, deretan penjelas, dan (opsional) interpretasi.'),
            $this->q('Bagian teks eksplanasi yang memuat rincian proses sebab-akibat disebut ...',
                'deretan penjelas',
                ['pernyataan umum', 'interpretasi', 'orientasi'],
                'Deretan penjelas berisi urutan proses atau sebab-akibat secara rinci.'),
            $this->q('Bagian teks eksplanasi yang bersifat opsional (boleh tidak ada) adalah ...',
                'interpretasi',
                ['pernyataan umum', 'deretan penjelas', 'tesis'],
                'Interpretasi berisi pandangan penutup penulis dan bersifat opsional.'),
            $this->q('Kata hubung yang lazim dipakai untuk menyatakan sebab-akibat dalam teks eksplanasi adalah ...',
                'karena, sebab, akibatnya',
                ['namun, tetapi, sedangkan', 'andaikan, seandainya, jikalau', 'agar, supaya, demi'],
                'Konjungsi kausalitas seperti karena, sebab, dan akibatnya menandai hubungan sebab-akibat.'),
            $this->q('Topik yang paling tepat dikembangkan menjadi teks eksplanasi adalah ...',
                'proses terjadinya gerhana matahari',
                ['liburan seru di Pulau Samosir', 'pentingnya memakai helm saat berkendara', 'riwayat hidup Ki Hajar Dewantara'],
                'Gerhana matahari adalah fenomena alam yang prosesnya dapat dijelaskan secara berurutan.'),
            $this->q('Hubungan antarkalimat pada kutipan tersebut menunjukkan hubungan ...',
                'sebab-akibat',
                ['perbandingan', 'pertentangan', 'urutan waktu'],
                'Kata "akibatnya" menandai bahwa kalimat kedua merupakan akibat dari kalimat pertama.',
                'Banjir terjadi ketika curah hujan tinggi tidak diimbangi daya tampung sungai. Akibatnya, air meluap ke permukiman warga.'),
            $this->q('Kalimat tersebut merupakan bagian ... dari teks eksplanasi.',
                'pernyataan umum',
                ['deretan penjelas', 'interpretasi', 'kesimpulan'],
                'Kalimat itu memperkenalkan topik secara umum (definisi gempa bumi), itulah pernyataan umum.',
                'Gempa bumi adalah getaran yang terjadi pada kulit bumi.'),
            $this->q('Ciri kebahasaan teks eksplanasi yang benar adalah ...',
                'menggunakan istilah ilmiah dan kata hubung sebab-akibat',
                ['menggunakan kata sapaan dan kalimat seru', 'banyak memakai kalimat perintah dan ajakan', 'menggunakan dialog antartokoh'],
                'Teks eksplanasi bersifat faktual-ilmiah sehingga banyak memuat istilah teknis dan konjungsi kausalitas.'),
            $this->q('Perbedaan teks eksplanasi dengan teks deskripsi terletak pada ...',
                'eksplanasi menjelaskan proses sebab-akibat, deskripsi melukiskan ciri objek',
                ['eksplanasi menyajikan tokoh dan latar, deskripsi menyajikan fakta ilmiah', 'eksplanasi berisi ajakan, deskripsi berisi cerita khayalan', 'eksplanasi bertujuan menghibur, deskripsi bertujuan mengkritik'],
                'Eksplanasi berfokus pada proses/sebab-akibat, sedangkan deskripsi melukiskan ciri-ciri objek.'),
            $this->q('Kalimat tersebut menunjukkan bahwa teks eksplanasi memiliki ciri ...',
                'memaparkan proses secara berurutan',
                ['memuat pendapat pribadi penulis', 'mengisahkan tokoh utama', 'mengajak pembaca bertindak'],
                'Kalimat itu memaparkan tahapan (penguapan, pembentukan awan, turunnya hujan) secara berurutan.',
                'Hujan terjadi melalui proses penguapan air laut, pembentukan awan, hingga turunnya butir-butir air ke permukaan bumi.'),
            $this->q('Kalimat yang menyatakan hubungan sebab-akibat adalah ...',
                'Tanah longsor terjadi karena hutan di lereng gunung ditebangi.',
                ['Tanah longsor adalah bergeraknya tanah dari tempat tinggi ke rendah.', 'Kami mengunjungi lokasi tanah longsor kemarin sore.', 'Mari kita jaga kelestarian hutan di lereng gunung.'],
                'Kata "karena" menghubungkan penyebab (hutan ditebangi) dengan akibat (tanah longsor).'),
            $this->q('Istilah ilmiah yang lazim muncul dalam teks eksplanasi tentang fotosintesis adalah ...',
                'klorofil',
                ['pemandangan', 'sahabat', 'liburan'],
                'Klorofil adalah zat hijau daun, istilah teknis dalam proses fotosintesis.'),
            $this->q('Langkah yang tepat dalam menulis teks eksplanasi adalah ...',
                'menentukan topik, mengumpulkan data ilmiah, menyusun kerangka, lalu mengembangkannya',
                ['menentukan tokoh, membuat konflik, lalu menulis akhir cerita', 'menulis pendapat pribadi lalu mencari pendukung', 'menyalin cerita dari buku tanpa menyusun kerangka'],
                'Penulisan eksplanasi memerlukan data faktual dan kerangka yang runtut.'),
            $this->q('Pernyataan umum yang tepat untuk teks eksplanasi berjudul "Proses Terbentuknya Pelangi" adalah ...',
                'Pelangi adalah busur cahaya berwarna yang tampak di langit setelah hujan.',
                ['Pelangi terjadi karena cahaya matahari dibiaskan oleh butir air.', 'Marilah kita menikmati keindahan pelangi bersama keluarga.', 'Pada suatu sore, Dina melihat pelangi di atas bukit.'],
                'Pernyataan umum memperkenalkan fenomena secara garis besar; penjelasan pembiasan termasuk deretan penjelas.'),
        ];
    }

    private function biTataBahasa(): array
    {
        return [
            $this->q('Kata berimbuhan yang penulisannya tepat dari kata dasar "sapu" dengan awalan me- adalah ...',
                'menyapu', ['mensapu', 'memsapu', 'mesapu'],
                'Awalan me- bertemu huruf s luluh menjadi meny-: menyapu.'),
            $this->q('Kata berimbuhan yang benar dari kata dasar "karang" dengan awalan me- adalah ...',
                'mengarang', ['mengkarang', 'menkarang', 'memkarang'],
                'Awalan me- bertemu huruf k luluh menjadi meng-: mengarang.'),
            $this->q('Kata depan "di" ditulis dengan benar pada kalimat ...',
                'Adik duduk di kursi kayu itu.',
                ['Buku itu di bawa oleh Rina.', 'Pencuri itu di tangkap warga.', 'Surat itu di kirim kemarin.'],
                '"di kursi" menunjukkan tempat (kata depan, terpisah); pada pilihan lain "di-" adalah awalan sehingga harus serangkai.'),
            $this->q('Kalimat dengan penulisan kata depan "ke" yang tepat adalah ...',
                'Ibu pergi ke pasar membeli sayuran.',
                ['Ayah kerumah sakit menjenguk paman.', 'Kakak berangkat kesekolah pagi-pagi.', 'Kami berlibur kepantai bersama keluarga.'],
                'Kata depan "ke" yang menunjukkan arah/tempat ditulis terpisah dari kata yang mengikutinya.'),
            $this->q('Kata yang termasuk kata kerja (verba) adalah ...',
                'menari', ['sepatu', 'cantik', 'tiga'],
                '"Menari" menyatakan perbuatan; sepatu (nomina), cantik (adjektiva), tiga (numeralia).'),
            $this->q('Konjungsi yang tepat untuk melengkapi kalimat "Ia tidak masuk sekolah ... sedang sakit." adalah ...',
                'karena', ['tetapi', 'agar', 'meskipun'],
                'Bagian kedua menyatakan sebab, sehingga konjungsi yang tepat adalah "karena".'),
            $this->q('Kalimat yang mengandung konjungsi pertentangan adalah ...',
                'Dia pintar, tetapi sering lupa membawa buku.',
                ['Dia sakit sehingga tidak masuk sekolah.', 'Dia belajar agar lulus ujian.', 'Dia membaca buku ketika hujan turun.'],
                'Kata "tetapi" menyatakan pertentangan antara dua hal.'),
            $this->q('Kalimat majemuk setara terdapat pada ...',
                'Ayah membaca koran, dan ibu memasak di dapur.',
                ['Ayah membaca koran ketika ibu memasak di dapur.', 'Karena hujan deras, pertandingan ditunda.', 'Adik menangis karena mainannya rusak.'],
                'Dua klausa sederajat dihubungkan "dan" (konjungsi koordinatif); pilihan lain memakai konjungsi subordinatif.'),
            $this->q('Kalimat majemuk bertingkat terdapat pada ...',
                'Jika hujan reda, kami akan berangkat ke sekolah.',
                ['Kakak menyanyi dan adik menari di panggung.', 'Ibu pergi ke pasar, lalu pulang membawa sayur.', 'Dia pintar, tetapi malas.'],
                '"Jika hujan reda" adalah anak kalimat yang bergantung pada induk kalimat.'),
            $this->q('Kata ulang yang bermakna "saling" adalah ...',
                'tolong-menolong', ['buku-buku', 'berlari-lari', 'kuda-kudaan'],
                '"Tolong-menolong" berarti saling menolong.'),
            $this->q('Kata ulang yang menyatakan makna jamak (banyak) adalah ...',
                'rumah-rumah', ['mobil-mobilan', 'bolak-balik', 'berpura-pura'],
                '"Rumah-rumah" bermakna banyak rumah.'),
            $this->q('Kalimat aktif transitif terdapat pada ...',
                'Petani menanam padi di sawah.',
                ['Padi ditanam petani di sawah.', 'Petani itu sedang tidur.', 'Sawah itu sangat luas.'],
                'Kalimat transitif memiliki objek (padi) setelah predikat aktif (menanam).'),
            $this->q('Bentuk pasif dari kalimat "Ibu memasak nasi goreng." adalah ...',
                'Nasi goreng dimasak oleh ibu.',
                ['Nasi goreng memasak ibu.', 'Ibu dimasak nasi goreng.', 'Ibu memasakkan nasi goreng.'],
                'Objek menjadi subjek dan predikat berawalan di-: "Nasi goreng dimasak oleh ibu."'),
            $this->q('Imbuhan yang membentuk kata benda pada kata "pembangunan" adalah ...',
                'pe-an', ['me-kan', 'ber-an', 'me-i'],
                'Kata "pembangunan" dibentuk dari kata dasar "bangun" dengan konfiks pe-an.'),
            $this->q('Makna imbuhan ber- pada kata "bersepeda" adalah ...',
                'menggunakan sepeda', ['membuat sepeda', 'menjual sepeda', 'mencari sepeda'],
                'Awalan ber- pada kata benda alat bermakna "memakai/menggunakan".'),
        ];
    }

    private function biFaktaOpini(): array
    {
        return [
            $this->q('Kalimat yang berisi fakta adalah ...',
                'Gunung Sinabung terletak di Kabupaten Karo, Sumatra Utara.',
                ['Gunung Sinabung adalah gunung terindah di dunia.', 'Mendaki gunung pasti lebih menyenangkan daripada berenang.', 'Sebaiknya semua orang mendaki gunung setiap bulan.'],
                'Fakta dapat dibuktikan kebenarannya, misalnya letak geografis gunung.'),
            $this->q('Kalimat yang berisi opini adalah ...',
                'Menurut saya, film itu sangat membosankan.',
                ['Film itu berdurasi 120 menit.', 'Film itu dirilis pada tahun 2019.', 'Film itu disutradarai oleh sutradara asal Indonesia.'],
                'Opini adalah pendapat pribadi yang tidak dapat dibuktikan secara objektif.'),
            $this->q('Kata-kata yang menandai kalimat opini adalah ...',
                'sebaiknya, menurut saya, seharusnya',
                ['pada tahun, sebanyak, tercatat', 'sejak, ketika, selama', 'kemarin, tadi, esok'],
                'Kata seperti sebaiknya, menurut saya, dan seharusnya menandakan pendapat/penilaian.'),
            $this->q('Simpulan yang tepat dari paragraf tersebut adalah ...',
                'Belajar dengan tekun dapat meningkatkan hasil ujian.',
                ['Rina adalah siswa yang paling pandai di kelas.', 'Guru harus lebih sering memberi latihan soal.', 'Ujian sebaiknya dilaksanakan pada malam hari.'],
                'Kebiasaan belajar tekun Rina berakibat pada meningkatnya nilai, sehingga simpulan umumnya demikian.',
                'Rina belajar setiap malam, mengerjakan latihan soal, dan bertanya kepada guru jika kesulitan. Hasilnya, nilai ujiannya meningkat.'),
            $this->q('Kalimat fakta yang memuat data terukur adalah ...',
                'Tinggi Monas mencapai 132 meter.',
                ['Monas adalah bangunan yang paling megah.', 'Monas pasti dikunjungi semua wisatawan.', 'Menara Monas seharusnya dicat ulang.'],
                'Data tinggi 132 meter dapat diukur dan dibuktikan, sehingga merupakan fakta.'),
        ];
    }

    /* =====================================================================
     |  2. MATEMATIKA (150 soal)
     * ===================================================================*/

    private function matematika(): array
    {
        return [
            ...$this->mathMixed(),      // 15
            ...$this->mathKpkFpb(),     // 10
            ...$this->mathPowers(),     // 10
            ...$this->mathFractions(),  // 15
            ...$this->mathPercent(),    // 15
            ...$this->mathAlgebra(),    // 20
            ...$this->mathGeometry(),   // 25
            ...$this->mathStats(),      // 15
            ...$this->mathSequences(),  // 10
            ...$this->mathRatio(),      // 5
            ...$this->mathLogic(),      // 10
        ];
    }

    private function mathMixed(): array
    {
        $o = [];
        for ($i = 0; $i < 15; $i++) {
            switch ($i % 3) {
                case 0:
                    [$a, $b, $c, $d] = [$this->r(10, 60), $this->r(3, 12), $this->r(3, 9), $this->r(5, 30)];
                    $v = $a + $b * $c - $d;
                    $o[] = $this->mq("Hasil dari $a + $b × $c - $d adalah ...", $v,
                        [($a + $b) * $c - $d, $a + $b * ($c - $d), $v + 10, $v - 10], 2,
                        "Kerjakan perkalian lebih dulu: $b × $c = ".($b * $c).", lalu $a + ".($b * $c)." - $d = $v.");
                    break;
                case 1:
                    $b = $this->r(2, 9);
                    $a = $b * $this->r(3, 12);
                    [$c, $d] = [$this->r(2, 9), $this->r(2, 9)];
                    $v = intdiv($a, $b) + $c * $d;
                    $o[] = $this->mq("Hasil dari $a ÷ $b + $c × $d adalah ...", $v,
                        [(intdiv($a, $b) + $c) * $d, intdiv($a, $b) * $c + $d, $v + 5, $v - 5], 3,
                        "Pembagian dan perkalian dikerjakan lebih dulu: $a ÷ $b = ".intdiv($a, $b)." dan $c × $d = ".($c * $d).", sehingga hasilnya $v.");
                    break;
                default:
                    [$a, $b, $c, $d, $e] = [$this->r(120, 250), $this->r(2, 9), $this->r(2, 9), $this->r(2, 6), $this->r(1, 20)];
                    $v = $a - ($b + $c) * $d + $e;
                    $o[] = $this->mq("Hasil dari $a - ($b + $c) × $d + $e adalah ...", $v,
                        [$a - $b + $c * $d + $e, ($a - ($b + $c)) * $d + $e, $v + 10, $v - 10], 4,
                        "Kerjakan tanda kurung dahulu: $b + $c = ".($b + $c).", lalu kali $d = ".(($b + $c) * $d).". Hasil: $a - ".(($b + $c) * $d)." + $e = $v.");
            }
        }
        return $o;
    }

    private function mathKpkFpb(): array
    {
        $o = [];
        for ($i = 0; $i < 10; $i++) {
            do {
                $m = $this->pick([2, 3, 4, 5, 7]);
                $n = $this->pick([2, 3, 4, 5, 7]);
            } while ($m === $n || $this->gcd($m, $n) !== 1);

            $g = $this->pick([2, 3, 4, 5, 6]);
            $a = $g * $m;
            $b = $g * $n;
            $fpb = $g;
            $kpk = $g * $m * $n;

            $isFpb = $i < 5 ? $i % 2 === 0 : $i >= 8;
            if ($i < 5) {
                $text = ($isFpb ? 'FPB' : 'KPK')." dari $a dan $b adalah ...";
            } elseif ($isFpb) {
                $text = "Pita sepanjang $a cm dan $b cm akan dipotong menjadi bagian-bagian sama panjang tanpa sisa, dengan potongan sepanjang mungkin. Panjang tiap potongan adalah ... cm";
            } else {
                $text = "Lampu merah menyala setiap $a detik dan lampu hijau setiap $b detik. Jika keduanya menyala bersamaan pada detik ke-0, keduanya akan menyala bersamaan lagi setelah ... detik";
            }

            if ($isFpb) {
                $o[] = $this->mq($text, $fpb, [$kpk, $g * 2, abs($a - $b), $g + 1], $g,
                    "Faktor persekutuan terbesar dari $a dan $b adalah $fpb (faktorisasi prima: $a = $fpb × $m, $b = $fpb × $n).");
            } else {
                $o[] = $this->mq($text, $kpk, [$a * $b, $g, $a + $b, $kpk * 2], $g,
                    "KPK dari $a dan $b adalah $fpb × $m × $n = $kpk.");
            }
        }
        return $o;
    }

    private function mathPowers(): array
    {
        $o = [];

        for ($i = 0; $i < 3; $i++) {
            [$a, $b] = [$this->r(3, 9), $this->r(2, 4)];
            $v = $a ** 2 + $b ** 3;
            $o[] = $this->mq("Nilai dari $a".$this->sup(2)." + $b".$this->sup(3)." adalah ...", $v,
                [$a * 2 + $b * 3, $a ** 2 * $b ** 3, $v + 6, $v - 6], 3,
                "$a".$this->sup(2)." = ".($a ** 2)." dan $b".$this->sup(3)." = ".($b ** 3).", sehingga jumlahnya $v.");
        }

        $squares = [49, 64, 81, 100, 121, 144, 169, 196, 225];
        for ($i = 0; $i < 2; $i++) {
            do {
                $x = $this->pick($squares);
                $y = $this->pick($squares);
            } while ($x === $y);
            $v = (int) (sqrt($x) + sqrt($y));
            $o[] = $this->mq("Nilai dari √$x + √$y adalah ...", $v,
                [(int) sqrt($x + $y), $x + $y, $v + 2, $v - 2], 2,
                "√$x = ".(int) sqrt($x)." dan √$y = ".(int) sqrt($y).", jadi hasilnya $v.");
        }

        for ($i = 0; $i < 2; $i++) {
            [$a, $b] = [$this->r(4, 7), $this->r(3, 5)];
            $c = $this->r(2, 4);
            $v = 2 ** ($a + $b - $c);
            $o[] = $this->mq("Hasil dari 2".$this->sup($a)." × 2".$this->sup($b)." ÷ 2".$this->sup($c)." adalah ...", $v,
                [2 ** ($a * $b - $c), 2 ** ($a + $b + $c), $v * 2, intdiv($v, 2)], 4,
                "Sifat pangkat: 2^$a × 2^$b ÷ 2^$c = 2^($a + $b - $c) = 2^".($a + $b - $c)." = $v.");
        }

        $cubes = [64, 125, 216, 343, 512, 729, 1000];
        for ($i = 0; $i < 2; $i++) {
            $cube = $this->pick($cubes);
            $sq = $this->pick($squares);
            $v = (int) round($cube ** (1 / 3)) + (int) sqrt($sq);
            $o[] = $this->mq("Nilai dari ∛$cube + √$sq adalah ...", $v,
                [(int) round($cube ** (1 / 3)) * (int) sqrt($sq), $v + 3, $v - 3, $v + 6], 2,
                "∛$cube = ".(int) round($cube ** (1 / 3))." dan √$sq = ".(int) sqrt($sq).", jumlahnya $v.");
        }

        [$a, $b] = [$this->r(6, 9), 3];
        $v = $a ** 2 - $b * 2 ** 3;
        $o[] = $this->mq("Nilai dari $a".$this->sup(2)." - $b × 2".$this->sup(3)." adalah ...", $v,
            [($a ** 2 - $b) * 8, $a * 2 - $b * 6, $v + 8, $v - 8], 4,
            "Pangkat dahulu: $a".$this->sup(2)." = ".($a ** 2)." dan 2".$this->sup(3)." = 8. Lalu ".($a ** 2)." - ".($b * 8)." = $v.");

        return $o;
    }

    private function mathFractions(): array
    {
        $o = [];
        $dens = [2, 3, 4, 5, 6, 8];

        $pairFraction = function () use ($dens) {
            do {
                $d1 = $this->pick($dens);
                $d2 = $this->pick($dens);
            } while ($d1 === $d2);
            return [$this->r(1, $d1 - 1), $d1, $this->r(1, $d2 - 1), $d2];
        };

        // Penjumlahan (5)
        for ($i = 0; $i < 5; $i++) {
            [$n1, $d1, $n2, $d2] = $pairFraction();
            $num = $n1 * $d2 + $n2 * $d1;
            $den = $d1 * $d2;
            $ans = $this->frac($num, $den);
            $o[] = $this->q("Hasil dari $n1/$d1 + $n2/$d2 adalah ...", $ans,
                [$this->frac($n1 + $n2, $d1 + $d2), $this->frac($n1 + $n2, max($d1, $d2)), $this->frac($num + 1, $den), $this->frac($num + 2, $den), $this->frac($num, $den + 1), $this->frac($num + $den, $den)],
                "Samakan penyebut ($den): ".($n1 * $d2)."/$den + ".($n2 * $d1)."/$den = $num/$den, bentuk paling sederhana: $ans.");
        }

        // Pengurangan (4)
        for ($i = 0; $i < 4; $i++) {
            do {
                [$n1, $d1, $n2, $d2] = $pairFraction();
                $num = $n1 * $d2 - $n2 * $d1;
            } while ($num <= 0);
            $den = $d1 * $d2;
            $ans = $this->frac($num, $den);
            $o[] = $this->q("Hasil dari $n1/$d1 - $n2/$d2 adalah ...", $ans,
                [$this->frac(abs($n1 - $n2) ?: 1, abs($d1 - $d2)), $this->frac($num + 1, $den), $this->frac($num + 2, $den), $this->frac($num, $den + 1), $this->frac($n1 * $d2 + $n2 * $d1, $den)],
                "Samakan penyebut ($den): ".($n1 * $d2)."/$den - ".($n2 * $d1)."/$den = $num/$den, bentuk paling sederhana: $ans.");
        }

        // Perkalian (3)
        for ($i = 0; $i < 3; $i++) {
            [$n1, $d1, $n2, $d2] = $pairFraction();
            $ans = $this->frac($n1 * $n2, $d1 * $d2);
            $o[] = $this->q("Hasil dari $n1/$d1 × $n2/$d2 adalah ...", $ans,
                [$this->frac($n1 * $n2, $d1 + $d2), $this->frac($n1 + $n2, $d1 * $d2), $this->frac($n1 * $d2, $n2 * $d1), $this->frac($n1 * $n2 + 1, $d1 * $d2), $this->frac($n1 * $n2 + 2, $d1 * $d2)],
                "Kalikan pembilang dengan pembilang dan penyebut dengan penyebut: ".($n1 * $n2)."/".($d1 * $d2).", bentuk paling sederhana: $ans.");
        }

        // Pembagian (3)
        for ($i = 0; $i < 3; $i++) {
            [$n1, $d1, $n2, $d2] = $pairFraction();
            $ans = $this->frac($n1 * $d2, $d1 * $n2);
            $o[] = $this->q("Hasil dari $n1/$d1 ÷ $n2/$d2 adalah ...", $ans,
                [$this->frac($n1 * $n2, $d1 * $d2), $this->frac($d1 * $n2, $n1 * $d2), $this->frac($n1 * $d2 + 1, $d1 * $n2), $this->frac($n1 * $d2 + 2, $d1 * $n2), $this->frac($n1 * $d2, $d1 * $n2 + 1)],
                "Pembagian pecahan sama dengan mengalikan dengan kebalikan: $n1/$d1 × $d2/$n2 = ".($n1 * $d2)."/".($d1 * $n2).", bentuk paling sederhana: $ans.");
        }

        return $o;
    }

    private function mathPercent(): array
    {
        $o = [];
        $fmtRp = fn ($v) => $this->rp($v);
        $fmtPct = fn ($v) => $this->num($v).'%';

        // x% dari N (5)
        for ($i = 0; $i < 5; $i++) {
            $x = $this->pick([10, 15, 20, 25, 30, 40, 60, 75]);
            $n = $this->r(2, 40) * 20;
            $v = $x * $n / 100;
            $o[] = $this->mq("$x% dari ".$this->num($n)." adalah ...", $v,
                [$x * $n / 10, $n - $v, $v + $x, $v * 2], max(1, intdiv($x, 5)),
                "$x% × ".$this->num($n)." = ($x ÷ 100) × ".$this->num($n)." = ".$this->num($v).".");
        }

        // Diskon (4)
        for ($i = 0; $i < 4; $i++) {
            $h = $this->r(2, 20) * 10000;
            $d = $this->pick([10, 20, 25, 30, 40, 50]);
            $disc = $h * $d / 100;
            $pay = $h - $disc;
            $o[] = $this->mq("Sebuah tas dijual seharga ".$this->rp($h)." dan mendapat diskon $d%. Harga yang harus dibayar adalah ...", $pay,
                [$disc, $h + $disc, $h - $d, $pay + 5000, $pay - 5000], 5000,
                "Diskon = $d% × ".$this->rp($h)." = ".$this->rp($disc).". Harga bayar = ".$this->rp($h)." - ".$this->rp($disc)." = ".$this->rp($pay).".",
                null, true, $fmtRp);
        }

        // Untung (3)
        for ($i = 0; $i < 3; $i++) {
            $b = $this->r(3, 20) * 10000;
            $p = $this->pick([10, 20, 25, 40, 50]);
            $j = $b * (100 + $p) / 100;
            $o[] = $this->mq("Pak Ali membeli sepeda bekas seharga ".$this->rp($b)." lalu menjualnya seharga ".$this->rp($j).". Persentase keuntungan Pak Ali adalah ...", $p,
                [$p + 5, $p + 10, round(100 * ($j - $b) / $j), $p - 5], 5,
                "Untung = ".$this->rp($j)." - ".$this->rp($b)." = ".$this->rp($j - $b).". Persentase untung = (".($j - $b)." ÷ $b) × 100% = $p%.",
                null, true, $fmtPct);
        }

        // Kenaikan gaji (3)
        for ($i = 0; $i < 3; $i++) {
            $g = $this->r(6, 16) * 500000;
            $p = $this->pick([5, 10, 15, 20, 25]);
            $inc = $g * $p / 100;
            $o[] = $this->mq("Gaji Bu Sari sebesar ".$this->rp($g)." per bulan dan naik $p%. Gaji Bu Sari setelah kenaikan adalah ...", $g + $inc,
                [$inc, $g + $p, $g - $inc, $g + $inc + 100000], 50000,
                "Kenaikan = $p% × ".$this->rp($g)." = ".$this->rp($inc).". Gaji baru = ".$this->rp($g)." + ".$this->rp($inc)." = ".$this->rp($g + $inc).".",
                null, true, $fmtRp);
        }

        return $o;
    }

    private function mathAlgebra(): array
    {
        $o = [];

        // Persamaan linear satu variabel (8)
        for ($i = 0; $i < 8; $i++) {
            $x = $this->pick([-6, -5, -4, -3, -2, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
            $a = $this->r(2, 9);
            do {
                $b = $this->r(-9, 12);
            } while ($b === 0);
            $c = $a * $x + $b;
            $o[] = $this->mq("Penyelesaian dari persamaan ".$this->lin($a, $b)." = $c adalah x = ...", $x,
                [-$x, $x + 1, $x - 1, ($c + $b) / $a, $c - $b], 2,
                "Pindahkan konstanta: {$a}x = $c ".($b >= 0 ? '- '.$b : '+ '.abs($b))." = ".($c - $b).", lalu x = ".($c - $b)." ÷ $a = $x.",
                null, false);
        }

        // Substitusi nilai (4)
        for ($i = 0; $i < 4; $i++) {
            [$a, $b] = [$this->r(2, 6), $this->r(-5, -1)];
            [$p, $q, $s] = [$this->r(2, 5), $this->r(2, 5), $this->r(1, 9)];
            $v = $p * $a - $q * $b + $s;
            $o[] = $this->mq("Jika a = $a dan b = $b, nilai dari {$p}a - {$q}b + $s adalah ...", $v,
                [$p * $a + $q * $b + $s, $p * $a - $q * $b - $s, $p * $a + $q * $b - $s, $v + 2], 2,
                "Substitusi: $p($a) - $q($b) + $s = ".($p * $a)." - (".($q * $b).") + $s = $v.",
                null, false);
        }

        // Menyederhanakan bentuk aljabar (4)
        for ($i = 0; $i < 4; $i++) {
            $a = $this->r(5, 9);
            $c = $this->r(1, 4);
            $b = $this->r(2, 8);
            $d = $this->r(2, 6);
            $text = "Bentuk sederhana dari ".$this->coef($a, 'x')." + ".$this->coef($b, 'y')." - ".$this->coef($c, 'x')." + ".$this->coef($d, 'y')." adalah ...";
            $ans = $this->poly2($a - $c, $b + $d);
            $o[] = $this->q($text, $ans,
                [$this->poly2($a + $c, $b + $d), $this->poly2($a - $c, $b - $d), ($a - $c + $b + $d).'xy', $this->poly2($c - $a, $b + $d), $this->poly2($a - $c + 1, $b + $d)],
                "Kelompokkan suku sejenis: ({$a} - {$c})x + ({$b} + {$d})y = $ans.");
        }

        // Pertidaksamaan linear (4)
        for ($i = 0; $i < 4; $i++) {
            $x0 = $this->pick([-4, -3, -2, 2, 3, 4, 5, 6, 8]);
            $a = $this->r(2, 6);
            do {
                $b = $this->r(-8, 8);
            } while ($b === 0);
            $c = $a * $x0 + $b;
            $gt = $i % 2 === 0;
            $sign = $gt ? '>' : '<';
            $flip = $gt ? '<' : '>';
            $o[] = $this->q("Penyelesaian dari pertidaksamaan ".$this->lin($a, $b)." $sign $c adalah ...",
                "x $sign $x0",
                ["x $flip $x0", "x $sign ".(-$x0), "x $flip ".(-$x0)],
                "Pindahkan konstanta: {$a}x $sign ".($c - $b).", lalu bagi dengan $a (positif, tanda tidak berubah): x $sign $x0.");
        }

        return $o;
    }

    private function mathGeometry(): array
    {
        $o = [];

        // Luas persegi panjang (2)
        for ($i = 0; $i < 2; $i++) {
            $p = $this->r(8, 25);
            $l = $this->r(4, $p - 1);
            $v = $p * $l;
            $o[] = $this->mq("Sebuah persegi panjang memiliki panjang $p cm dan lebar $l cm. Luas persegi panjang tersebut adalah ...", $v,
                [2 * ($p + $l), $p + $l, $v + $p, $v - $l], $l,
                "Luas = panjang × lebar = $p × $l = $v cm².", 'cm²');
        }

        // Keliling persegi panjang (1)
        $p = $this->r(10, 30);
        $l = $this->r(5, $p - 1);
        $v = 2 * ($p + $l);
        $o[] = $this->mq("Sebuah persegi panjang berukuran $p cm × $l cm. Keliling persegi panjang tersebut adalah ...", $v,
            [$p * $l, $p + $l, $v + 4, $v - 4], 2,
            "Keliling = 2 × (p + l) = 2 × ($p + $l) = $v cm.", 'cm');

        // Luas segitiga (3)
        for ($i = 0; $i < 3; $i++) {
            $a = 2 * $this->r(3, 10);
            $t = $this->r(4, 15);
            $v = $a * $t / 2;
            $o[] = $this->mq("Sebuah segitiga memiliki alas $a cm dan tinggi $t cm. Luas segitiga tersebut adalah ...", $v,
                [$a * $t, $a + $t, $v + $t, $v - $a], 2,
                "Luas = ½ × alas × tinggi = ½ × $a × $t = $v cm².", 'cm²');
        }

        // Luas trapesium (2)
        for ($i = 0; $i < 2; $i++) {
            $a = $this->r(4, 10);
            $b = $a + 2 * $this->r(1, 4);
            $t = $this->r(4, 12);
            $v = ($a + $b) * $t / 2;
            $o[] = $this->mq("Sebuah trapesium memiliki sisi sejajar $a cm dan $b cm serta tinggi $t cm. Luas trapesium tersebut adalah ...", $v,
                [($a + $b) * $t, ($a + $b) / 2 + $t, ($a + $b) * $t / 4, $a * $b * $t / 2], 2,
                "Luas = ½ × (a + b) × t = ½ × ($a + $b) × $t = $v cm².", 'cm²');
        }

        // Luas lingkaran (3)
        for ($i = 0; $i < 3; $i++) {
            if ($i !== 1) {
                $r = $this->pick([7, 14, 21]);
                $v = intdiv(22 * $r * $r, 7);
                $o[] = $this->mq("Sebuah lingkaran berjari-jari $r cm (π = 22/7). Luas lingkaran tersebut adalah ...", $v,
                    [intdiv(44 * $r, 7), intdiv(22 * $r, 7), $v * 2, intdiv(22 * 2 * $r * $r, 7) + $r], 2,
                    "Luas = π × r² = 22/7 × $r × $r = $v cm².", 'cm²');
            } else {
                $r = $this->pick([10, 20, 30]);
                $v = round(3.14 * $r * $r, 2);
                $o[] = $this->mq("Sebuah lingkaran berjari-jari $r cm (π = 3,14). Luas lingkaran tersebut adalah ...", $v,
                    [round(6.28 * $r, 2), round(3.14 * $r, 2), round(3.14 * $r * $r * 2, 2), round(3.14 * $r * $r, 2) + 10], 6,
                    "Luas = π × r² = 3,14 × $r × $r = ".$this->num($v)." cm².", 'cm²');
            }
        }

        // Keliling lingkaran (2)
        $r = $this->pick([7, 14, 21, 28]);
        $v = intdiv(44 * $r, 7);
        $o[] = $this->mq("Sebuah lingkaran berjari-jari $r cm (π = 22/7). Keliling lingkaran tersebut adalah ...", $v,
            [intdiv(22 * $r * $r, 7), intdiv(22 * $r, 7), $v * 2, $v + 4], 4,
            "Keliling = 2 × π × r = 2 × 22/7 × $r = $v cm.", 'cm');
        $r = $this->pick([10, 20, 30, 50]);
        $v = round(6.28 * $r, 2);
        $o[] = $this->mq("Sebuah roda berjari-jari $r cm (π = 3,14). Keliling roda tersebut adalah ...", $v,
            [round(3.14 * $r * $r, 2), round(3.14 * $r, 2), round(6.28 * $r * 2, 2), $v + 10], 10,
            "Keliling = 2 × π × r = 2 × 3,14 × $r = ".$this->num($v)." cm.", 'cm');

        // Volume balok (2)
        for ($i = 0; $i < 2; $i++) {
            [$p, $l, $t] = [$this->r(5, 12), $this->r(3, 8), $this->r(2, 7)];
            $v = $p * $l * $t;
            $o[] = $this->mq("Sebuah balok berukuran panjang $p cm, lebar $l cm, dan tinggi $t cm. Volume balok tersebut adalah ...", $v,
                [2 * ($p * $l + $p * $t + $l * $t), $p + $l + $t, $v + $p, $v - $t], 6,
                "Volume = p × l × t = $p × $l × $t = $v cm³.", 'cm³');
        }

        // Volume kubus (2)
        for ($i = 0; $i < 2; $i++) {
            $s = $this->r(3, 12);
            $v = $s ** 3;
            $o[] = $this->mq("Sebuah kubus memiliki panjang rusuk $s cm. Volume kubus tersebut adalah ...", $v,
                [6 * $s * $s, 3 * $s, $s * $s, $v + $s], $s,
                "Volume kubus = s³ = $s × $s × $s = $v cm³.", 'cm³');
        }

        // Luas permukaan kubus (2)
        for ($i = 0; $i < 2; $i++) {
            $s = $this->r(3, 12);
            $v = 6 * $s * $s;
            $o[] = $this->mq("Sebuah kubus memiliki panjang rusuk $s cm. Luas permukaan kubus tersebut adalah ...", $v,
                [$s ** 3, 4 * $s * $s, 6 * $s, $v + 12], 6,
                "Luas permukaan kubus = 6 × s² = 6 × $s × $s = $v cm².", 'cm²');
        }

        // Teorema Pythagoras (3)
        $triples = [[3, 4, 5], [5, 12, 13], [8, 15, 17], [6, 8, 10], [9, 12, 15], [7, 24, 25], [12, 16, 20], [15, 20, 25]];
        for ($i = 0; $i < 3; $i++) {
            [$a, $b, $c] = $this->pick($triples);
            if ($i < 2) {
                $o[] = $this->mq("Sebuah segitiga siku-siku memiliki sisi siku-siku $a cm dan $b cm. Panjang sisi miringnya adalah ...", $c,
                    [$a + $b, $c + 1, $c - 1, $c + 2], 1,
                    "c² = a² + b² = {$a}² + {$b}² = ".($a * $a + $b * $b)." sehingga c = $c cm.", 'cm');
            } else {
                $o[] = $this->mq("Sebuah segitiga siku-siku memiliki sisi miring $c cm dan salah satu sisi siku-siku $a cm. Panjang sisi siku-siku yang lain adalah ...", $b,
                    [$c - $a, $c + $a, $b + 1, $b - 1], 2,
                    "b² = c² - a² = {$c}² - {$a}² = ".($c * $c - $a * $a)." sehingga b = $b cm.", 'cm');
            }
        }

        // Sudut (3)
        [$a, $b] = [$this->r(30, 80), $this->r(30, 70)];
        $v = 180 - $a - $b;
        $o[] = $this->mq("Dua sudut sebuah segitiga masing-masing berukuran {$a}° dan {$b}°. Besar sudut ketiga adalah ...", $v,
            [360 - $a - $b, $a + $b, 90 - $a, $v + 10], 5,
            "Jumlah sudut dalam segitiga 180°, maka sudut ketiga = 180° - {$a}° - {$b}° = {$v}°.", '°');
        $x = $this->pick([30, 45, 50, 65, 70, 110, 125]);
        $v = 180 - $x;
        $o[] = $this->mq("Besar pelurus (suplemen) dari sudut {$x}° adalah ...", $v,
            [90 - $x, 360 - $x, $x, $v + 10], 5,
            "Dua sudut saling berpelurus jika jumlahnya 180°, sehingga pelurus {$x}° adalah {$v}°.", '°');
        $x = $this->pick([20, 25, 35, 40, 55, 65]);
        $v = 90 - $x;
        $o[] = $this->mq("Besar penyiku (komplemen) dari sudut {$x}° adalah ...", $v,
            [180 - $x, 360 - $x, $x, $v + 10], 5,
            "Dua sudut saling menyiku jika jumlahnya 90°, sehingga penyiku {$x}° adalah {$v}°.", '°');

        return $o;
    }

    private function mathStats(): array
    {
        $o = [];

        // Rata-rata (5)
        for ($i = 0; $i < 5; $i++) {
            $n = $this->r(5, 7);
            $m = $this->r(60, 90);
            do {
                $data = [];
                for ($k = 0; $k < $n - 1; $k++) {
                    $data[] = $m + $this->r(-8, 8);
                }
                $last = $m * $n - array_sum($data);
            } while ($last < 40 || $last > 100);
            $data[] = $last;
            shuffle($data);
            $o[] = $this->mq("Rata-rata (mean) dari data ".implode(', ', $data)." adalah ...", $m,
                [$m + 1, $m - 1, $m + 2, $m - 2], 1,
                "Jumlah data = ".array_sum($data).", banyak data = $n, sehingga rata-rata = ".array_sum($data)." ÷ $n = $m.");
        }

        // Median (4)
        for ($i = 0; $i < 4; $i++) {
            $n = $i % 2 === 0 ? 7 : 6;
            $data = [];
            while (count($data) < $n) {
                $data[] = $this->r(3, 20);
            }
            $sorted = $data;
            sort($sorted);
            $med = $n % 2 === 1 ? $sorted[intdiv($n, 2)] : ($sorted[$n / 2 - 1] + $sorted[$n / 2]) / 2;
            $o[] = $this->mq("Median dari data ".implode(', ', $data)." adalah ...", $med,
                [array_sum($data) / $n, $sorted[0], $sorted[$n - 1], $med + 1, $med - 1], 1,
                "Data terurut: ".implode(', ', $sorted).". ".($n % 2 === 1 ? "Nilai tengahnya adalah $med." : "Median = rata-rata dua nilai tengah = ".$this->num($med).".")
            );
        }

        // Modus (3)
        for ($i = 0; $i < 3; $i++) {
            $mode = $this->r(4, 10);
            $pool = array_values(array_diff(range(3, 12), [$mode]));
            shuffle($pool);
            $data = array_merge([$mode, $mode, $mode], array_slice($pool, 0, 4));
            shuffle($data);
            $o[] = $this->mq("Modus dari data ".implode(', ', $data)." adalah ...", $mode,
                [$pool[0], $pool[1], $pool[2], round(array_sum($data) / count($data))], 1,
                "Modus adalah nilai yang paling sering muncul, yaitu $mode (muncul 3 kali).");
        }

        // Nilai yang hilang (3)
        for ($i = 0; $i < 3; $i++) {
            $m = $this->r(70, 90);
            do {
                $four = [$this->r(60, 100), $this->r(60, 100), $this->r(60, 100), $this->r(60, 100)];
                $fifth = $m * 5 - array_sum($four);
            } while ($fifth < 40 || $fifth > 100);
            $o[] = $this->mq("Rata-rata lima nilai ulangan Dina adalah $m. Jika empat nilai pertamanya adalah ".implode(', ', $four).", nilai kelima adalah ...", $fifth,
                [$m, array_sum($four) / 4, $fifth + 5, $fifth - 5], 5,
                "Jumlah kelima nilai = $m × 5 = ".($m * 5).". Nilai kelima = ".($m * 5)." - ".array_sum($four)." = $fifth.");
        }

        return $o;
    }

    private function mathSequences(): array
    {
        $o = [];

        // Suku ke-n barisan aritmetika (4)
        for ($i = 0; $i < 4; $i++) {
            [$a, $b, $n] = [$this->r(2, 10), $this->r(2, 7), $this->r(8, 15)];
            $v = $a + ($n - 1) * $b;
            $seq = implode(', ', [$a, $a + $b, $a + 2 * $b, $a + 3 * $b]);
            $o[] = $this->mq("Suku ke-$n dari barisan $seq, ... adalah ...", $v,
                [$a + $n * $b, $a + ($n - 2) * $b, $n * $b, $v + $b], $b,
                "Un = a + (n - 1)b = $a + ($n - 1) × $b = $v.");
        }

        // Jumlah n suku pertama (3)
        for ($i = 0; $i < 3; $i++) {
            [$a, $b, $n] = [$this->r(2, 8), $this->r(2, 6), $this->r(6, 12)];
            $s = intdiv($n * (2 * $a + ($n - 1) * $b), 2);
            $o[] = $this->mq("Jumlah $n suku pertama dari barisan aritmetika dengan suku pertama $a dan beda $b adalah ...", $s,
                [$n * (2 * $a + ($n - 1) * $b), $s + $n, $s - $n, $n * ($a + ($n - 1) * $b)], $n,
                "Sn = n/2 × (2a + (n - 1)b) = $n/2 × (".(2 * $a)." + ".(($n - 1) * $b).") = $s.");
        }

        // Pola geometri (3)
        for ($i = 0; $i < 3; $i++) {
            $a = $this->r(1, 4);
            $ratio = $this->pick([2, 3]);
            $t = [$a, $a * $ratio, $a * $ratio ** 2, $a * $ratio ** 3];
            $v = $a * $ratio ** 4;
            $o[] = $this->mq("Suku selanjutnya dari barisan ".implode(', ', $t).", ... adalah ...", $v,
                [$t[3] + ($t[3] - $t[2]), $t[3] * $ratio + $ratio, $t[3] * ($ratio + 1), $v + $ratio], $ratio,
                "Barisan geometri dengan rasio $ratio, sehingga suku berikutnya = {$t[3]} × $ratio = $v.");
        }

        return $o;
    }

    private function mathRatio(): array
    {
        $o = [];

        // Skala peta (2)
        for ($i = 0; $i < 2; $i++) {
            $s = $this->pick([500000, 1000000, 2000000, 3000000]);
            $d = $this->r(2, 12);
            $km = $d * $s / 100000;
            $o[] = $this->mq("Pada peta berskala 1 : ".number_format($s, 0, ',', '.').", jarak dua kota adalah $d cm. Jarak sebenarnya kedua kota adalah ...", $km,
                [$km * 10, $km / 10, $km + $s / 100000, $km - $s / 100000], $s / 100000,
                "Jarak sebenarnya = $d × ".number_format($s, 0, ',', '.')." cm = ".number_format($d * $s, 0, ',', '.')." cm = ".$this->num($km)." km.", 'km');
        }

        // Perbandingan senilai (2)
        for ($i = 0; $i < 2; $i++) {
            $price = $this->pick([2000, 3000, 4000, 5000, 6000]);
            $k = $this->r(2, 5);
            $n = $k + $this->r(2, 6);
            $o[] = $this->mq("Harga $k buku tulis adalah ".$this->rp($price * $k).". Harga $n buku tulis yang sama adalah ...", $price * $n,
                [$price * ($n - $k), $price * $n + $price, $price * $n - $price, $price * $k + $n], $price,
                "Harga 1 buku = ".$this->rp($price).". Harga $n buku = $n × ".$this->rp($price)." = ".$this->rp($price * $n).".",
                null, true, fn ($v) => $this->rp($v));
        }

        // Perbandingan berbalik nilai (1)
        [$x, $y, $z] = $this->pick([[6, 12, 9], [8, 9, 12], [10, 6, 5], [12, 10, 8], [5, 24, 8]]);
        $v = $x * $y / $z;
        $o[] = $this->mq("Sebuah pekerjaan dapat diselesaikan oleh $x pekerja dalam $y hari. Jika pekerjaan tersebut dikerjakan oleh $z pekerja, waktu yang diperlukan adalah ...", $v,
            [$y * $z / $x, $y + ($z - $x), $y - ($z - $x), $v + 2], 1,
            "Jumlah pekerja dan lama kerja berbanding terbalik: $x × $y = $z × t, sehingga t = ".($x * $y)." ÷ $z = ".$this->num($v)." hari.", 'hari');

        return $o;
    }

    private function mathLogic(): array
    {
        return [
            $this->q('Negasi (ingkaran) dari pernyataan "Semua siswa mengikuti ujian." adalah ...',
                'Ada siswa yang tidak mengikuti ujian.',
                ['Semua siswa tidak mengikuti ujian.', 'Tidak ada siswa yang mengikuti ujian.', 'Beberapa siswa mengikuti ujian.'],
                'Negasi "semua" adalah "ada/beberapa yang tidak".'),
            $this->q('Negasi dari pernyataan "Beberapa hewan adalah mamalia." adalah ...',
                'Semua hewan bukan mamalia.',
                ['Beberapa hewan bukan mamalia.', 'Semua hewan adalah mamalia.', 'Tidak semua hewan adalah mamalia.'],
                'Negasi "beberapa/ada" adalah "semua ... tidak".'),
            $this->q('Konvers dari pernyataan "Jika hujan turun, maka jalanan basah." adalah ...',
                'Jika jalanan basah, maka hujan turun.',
                ['Jika hujan tidak turun, maka jalanan tidak basah.', 'Jika jalanan tidak basah, maka hujan tidak turun.', 'Hujan turun dan jalanan basah.'],
                'Konvers dari p → q adalah q → p.'),
            $this->q('Invers dari pernyataan "Jika ia rajin belajar, maka ia lulus ujian." adalah ...',
                'Jika ia tidak rajin belajar, maka ia tidak lulus ujian.',
                ['Jika ia lulus ujian, maka ia rajin belajar.', 'Jika ia tidak lulus ujian, maka ia tidak rajin belajar.', 'Ia rajin belajar dan ia tidak lulus ujian.'],
                'Invers dari p → q adalah ~p → ~q.'),
            $this->q('Kontraposisi dari pernyataan "Jika segitiga itu sama sisi, maka sudut-sudutnya sama besar." adalah ...',
                'Jika sudut-sudutnya tidak sama besar, maka segitiga itu tidak sama sisi.',
                ['Jika sudut-sudutnya sama besar, maka segitiga itu sama sisi.', 'Jika segitiga itu tidak sama sisi, maka sudut-sudutnya tidak sama besar.', 'Segitiga itu sama sisi dan sudutnya tidak sama besar.'],
                'Kontraposisi dari p → q adalah ~q → ~p, dan selalu ekuivalen dengan pernyataan awal.'),
            $this->q('Kesimpulan yang sah dari premis berikut adalah ...',
                'Dina dapat membeli sepeda.',
                ['Dina tidak dapat membeli sepeda.', 'Dina tidak rajin menabung.', 'Jika Dina membeli sepeda, maka ia rajin menabung.'],
                'Modus ponens: p → q dan p benar, maka q benar.',
                'Premis 1: Jika Dina rajin menabung, maka ia dapat membeli sepeda. Premis 2: Dina rajin menabung.'),
            $this->q('Kesimpulan yang sah dari premis berikut adalah ...',
                'Hari ini tidak hujan.',
                ['Hari ini hujan.', 'Upacara dibatalkan.', 'Hari ini hujan dan upacara dibatalkan.'],
                'Modus tollens: p → q dan ~q benar, maka ~p benar.',
                'Premis 1: Jika hari ini hujan, maka upacara dibatalkan. Premis 2: Upacara tidak dibatalkan.'),
            $this->q('Kesimpulan yang sah dari premis berikut adalah ...',
                'Jika Budi belajar, maka ia lulus ujian.',
                ['Jika Budi lulus ujian, maka ia belajar.', 'Jika Budi tidak belajar, maka ia tidak pandai.', 'Budi belajar dan tidak lulus ujian.'],
                'Silogisme: p → q dan q → r menghasilkan p → r.',
                'Premis 1: Jika Budi belajar, maka ia pandai. Premis 2: Jika Budi pandai, maka ia lulus ujian.'),
            $this->q('Implikasi p → q bernilai salah apabila ...',
                'p benar dan q salah',
                ['p salah dan q benar', 'p salah dan q salah', 'p benar dan q benar'],
                'Implikasi hanya bernilai salah bila anteseden benar tetapi konsekuen salah.'),
            $this->q('Konjungsi p ∧ q bernilai benar apabila ...',
                'p dan q keduanya benar',
                ['salah satu dari p atau q benar', 'p dan q keduanya salah', 'p benar dan q salah'],
                'Konjungsi (dan) hanya benar bila kedua pernyataan benar.'),
        ];
    }

    /* =====================================================================
     |  3. IPA (150 soal)
     * ===================================================================*/

    private function ipa(): array
    {
        return [
            ...$this->ipaOrgan(),         // 15
            ...$this->ipaUnsur(),         // 10
            ...$this->ipaSatuan(),        // 10
            ...$this->ipaHitungan(),      // 15
            ...$this->ipaEkosistem(),     // 30
            ...$this->ipaFisika(),        // 15
            ...$this->ipaKimia(),         // 15
            ...$this->ipaTataSurya(),     // 15
            ...$this->ipaStruktur(),      // 15
            ...$this->ipaPemakan(),       // 10
        ];
    }

    private function ipaOrgan(): array
    {
        return $this->pairQuestions([
            ['jantung', 'memompa darah ke seluruh tubuh'],
            ['paru-paru', 'tempat pertukaran oksigen dan karbon dioksida'],
            ['ginjal', 'menyaring darah dan membentuk urin'],
            ['hati', 'menghasilkan empedu dan menetralkan racun'],
            ['lambung', 'mencerna makanan secara mekanik dan kimiawi dengan asam lambung'],
            ['usus halus', 'tempat penyerapan sari-sari makanan'],
            ['usus besar', 'menyerap air dan membentuk feses'],
            ['otak', 'pusat pengatur dan pengendali kegiatan tubuh'],
            ['tulang rusuk', 'melindungi jantung dan paru-paru'],
            ['diafragma', 'membantu gerakan pernapasan dengan mengubah volume rongga dada'],
            ['pankreas', 'menghasilkan insulin dan enzim pencernaan'],
            ['kulit', 'melindungi tubuh, mengatur suhu, dan mengeluarkan keringat'],
            ['sel darah putih (leukosit)', 'melawan kuman penyakit'],
            ['sel darah merah (eritrosit)', 'mengangkut oksigen ke seluruh tubuh'],
            ['trakea', 'menyalurkan udara dari laring menuju bronkus'],
        ], 'Fungsi {k} pada tubuh manusia adalah ...', 'Fungsi {k} adalah {v}.');
    }

    private function ipaUnsur(): array
    {
        return $this->pairQuestions([
            ['natrium', 'Na', 'Lambang unsur natrium adalah Na (dari bahasa Latin natrium).'],
            ['kalium', 'K', 'Lambang unsur kalium adalah K (dari bahasa Latin kalium).'],
            ['besi', 'Fe', 'Lambang unsur besi adalah Fe (dari bahasa Latin ferrum).'],
            ['emas', 'Au', 'Lambang unsur emas adalah Au (dari bahasa Latin aurum).'],
            ['perak', 'Ag', 'Lambang unsur perak adalah Ag (dari bahasa Latin argentum).'],
            ['tembaga', 'Cu', 'Lambang unsur tembaga adalah Cu (dari bahasa Latin cuprum).'],
            ['raksa', 'Hg', 'Lambang unsur raksa adalah Hg (dari bahasa Latin hydrargyrum).'],
            ['timbal', 'Pb', 'Lambang unsur timbal adalah Pb (dari bahasa Latin plumbum).'],
            ['kalsium', 'Ca', 'Lambang unsur kalsium adalah Ca (dari bahasa Latin calcium).'],
            ['nitrogen', 'N', 'Lambang unsur nitrogen adalah N.'],
        ], 'Lambang kimia untuk unsur {k} adalah ...', '');
    }

    private function ipaSatuan(): array
    {
        return $this->pairQuestions([
            ['gaya', 'newton (N)'], ['usaha atau energi', 'joule (J)'], ['daya', 'watt (W)'],
            ['tekanan', 'pascal (Pa)'], ['frekuensi', 'hertz (Hz)'], ['kuat arus listrik', 'ampere (A)'],
            ['tegangan listrik', 'volt (V)'], ['hambatan listrik', 'ohm (Ω)'],
            ['massa', 'kilogram (kg)'], ['suhu', 'kelvin (K)'],
        ], 'Satuan SI untuk besaran {k} adalah ...', 'Satuan SI untuk {k} adalah {v}.');
    }

    private function ipaHitungan(): array
    {
        $o = [];

        // Kecepatan (3)
        for ($i = 0; $i < 3; $i++) {
            $v = $this->r(4, 18) * 5;
            $t = $this->pick([2, 3, 4, 5]);
            $s = $v * $t;
            if ($i < 2) {
                $o[] = $this->mq("Sebuah mobil menempuh jarak $s km dalam waktu $t jam. Kecepatan rata-rata mobil tersebut adalah ...", $v,
                    [$s + $t, $s * $t, $v + 5, $v - 5], 5,
                    "v = s ÷ t = $s ÷ $t = $v km/jam.", 'km/jam');
            } else {
                $o[] = $this->mq("Sebuah sepeda motor bergerak dengan kecepatan tetap $v m/s selama $t sekon. Jarak yang ditempuh adalah ...", $s,
                    [$v + $t, intdiv($v, $t), $s + $v, $s - $v], 5,
                    "s = v × t = $v × $t = $s m.", 'm');
            }
        }

        // Usaha (2)
        for ($i = 0; $i < 2; $i++) {
            $f = $this->r(2, 10) * 5;
            $s = $this->r(2, 10);
            $o[] = $this->mq("Gaya sebesar $f N memindahkan sebuah benda sejauh $s m searah gaya. Usaha yang dilakukan adalah ...", $f * $s,
                [$f + $s, $f / $s, $f * $s + $f, $f * $s - $s], 5,
                "W = F × s = $f × $s = ".($f * $s)." joule.", 'joule');
        }

        // Tekanan (2)
        for ($i = 0; $i < 2; $i++) {
            $p = $this->pick([50, 100, 150, 200, 250]);
            $a = $this->pick([2, 4, 5]);
            $f = $p * $a;
            $o[] = $this->mq("Gaya sebesar $f N bekerja tegak lurus pada bidang seluas $a m². Tekanan pada bidang tersebut adalah ...", $p,
                [$f * $a, $f - $a, $p + 50, $p - 50], 25,
                "P = F ÷ A = $f ÷ $a = $p Pa.", 'Pa');
        }

        // Hukum Ohm (3)
        for ($i = 0; $i < 3; $i++) {
            $cur = $this->r(1, 5);
            $res = $this->pick([2, 4, 6, 10, 12]);
            $volt = $cur * $res;
            if ($i < 2) {
                $o[] = $this->mq("Kuat arus $cur A mengalir melalui sebuah hambatan $res Ω. Beda potensial pada hambatan tersebut adalah ...", $volt,
                    [$cur + $res, $res / $cur, $volt + 2, $volt - 2], 2,
                    "V = I × R = $cur × $res = $volt volt.", 'V');
            } else {
                $o[] = $this->mq("Sebuah hambatan $res Ω dihubungkan dengan sumber tegangan $volt V. Kuat arus yang mengalir adalah ...", $cur,
                    [$volt * $res, $volt - $res, $cur + 1, $cur + 2], 1,
                    "I = V ÷ R = $volt ÷ $res = $cur A.", 'A');
            }
        }

        // Energi kinetik (2)
        for ($i = 0; $i < 2; $i++) {
            $m = $this->pick([2, 4, 6, 8, 10]);
            $v = $this->pick([2, 3, 4, 5, 6, 10]);
            $ek = $m * $v * $v / 2;
            $o[] = $this->mq("Sebuah benda bermassa $m kg bergerak dengan kecepatan $v m/s. Energi kinetik benda tersebut adalah ...", $ek,
                [$m * $v * $v, $m * $v, $m * $v / 2, $ek + $m], 2,
                "Ek = ½ × m × v² = ½ × $m × {$v}² = ".$this->num($ek)." joule.", 'joule');
        }

        // Energi potensial (2)
        for ($i = 0; $i < 2; $i++) {
            $m = $this->r(1, 10);
            $h = $this->r(2, 20);
            $ep = $m * 10 * $h;
            $o[] = $this->mq("Sebuah benda bermassa $m kg berada pada ketinggian $h m dari tanah (g = 10 m/s²). Energi potensial benda tersebut adalah ...", $ep,
                [$m * $h, $m * 10, $ep / 2, $ep + $h], 10,
                "Ep = m × g × h = $m × 10 × $h = $ep joule.", 'joule');
        }

        // Massa jenis (1)
        $rho = $this->pick([2, 4, 5, 8]);
        $vol = $this->pick([10, 20, 25, 50]);
        $m = $rho * $vol;
        $o[] = $this->mq("Sebuah benda bermassa $m gram memiliki volume $vol cm³. Massa jenis benda tersebut adalah ...", $rho,
            [$m * $vol, $vol / $m, $rho + 1, $rho + 2], 1,
            "ρ = m ÷ V = $m ÷ $vol = $rho g/cm³.", 'g/cm³');

        return $o;
    }

    private function ipaEkosistem(): array
    {
        return [
            $this->q('Organisme yang mampu membuat makanan sendiri disebut ...', 'produsen', ['konsumen', 'dekomposer', 'predator'], 'Produsen (tumbuhan hijau) membuat makanan sendiri lewat fotosintesis.'),
            $this->q('Pada rantai makanan rumput → belalang → katak → ular, belalang berperan sebagai ...', 'konsumen tingkat I', ['produsen', 'konsumen tingkat II', 'dekomposer'], 'Belalang memakan produsen (rumput), sehingga menjadi konsumen tingkat I.'),
            $this->q('Pengurai (dekomposer) berperan dalam ekosistem dengan cara ...', 'menguraikan sisa makhluk hidup menjadi zat hara', ['membuat makanan melalui fotosintesis', 'memakan produsen', 'memangsa konsumen tingkat tinggi'], 'Dekomposer mengembalikan zat hara ke tanah dengan menguraikan bangkai dan sisa organisme.'),
            $this->q('Hubungan antara lebah dan bunga yang sama-sama menguntungkan disebut ...', 'mutualisme', ['parasitisme', 'komensalisme', 'predasi'], 'Lebah memperoleh nektar dan bunga terbantu penyerbukan: keduanya diuntungkan (mutualisme).'),
            $this->q('Benalu yang menempel pada pohon mangga merupakan contoh simbiosis ...', 'parasitisme', ['mutualisme', 'komensalisme', 'netralisme'], 'Benalu mengambil nutrisi dari pohon inang sehingga merugikan inang (parasitisme).'),
            $this->q('Anggrek yang menempel pada batang pohon tanpa merugikan pohon tersebut merupakan simbiosis ...', 'komensalisme', ['parasitisme', 'mutualisme', 'predasi'], 'Anggrek untung, pohon tidak dirugikan maupun diuntungkan (komensalisme).'),
            $this->q('Kumpulan individu sejenis yang menempati wilayah dan waktu tertentu disebut ...', 'populasi', ['komunitas', 'ekosistem', 'biosfer'], 'Populasi adalah kumpulan individu sejenis di suatu tempat dan waktu.'),
            $this->q('Interaksi antara komunitas dengan lingkungan abiotiknya membentuk ...', 'ekosistem', ['populasi', 'spesies', 'individu'], 'Ekosistem = komunitas (biotik) + lingkungan fisik (abiotik).'),
            $this->q('Komponen abiotik pada ekosistem sawah adalah ...', 'air, tanah, dan cahaya matahari', ['padi, belalang, dan katak', 'bakteri, jamur, dan cacing', 'tikus, ular, dan burung elang'], 'Abiotik adalah komponen tak hidup seperti air, tanah, dan cahaya.'),
            $this->q('Pada piramida makanan, jumlah energi paling besar terdapat pada tingkat ...', 'produsen', ['konsumen I', 'konsumen II', 'konsumen puncak'], 'Energi berkurang di setiap tingkat trofik; produsen memiliki energi terbesar.'),
            $this->q('Jika populasi elang di sawah berkurang drastis, populasi tikus kemungkinan akan ...', 'meningkat', ['menurun', 'tetap', 'punah seketika'], 'Pemangsa berkurang sehingga jumlah mangsa (tikus) cenderung meningkat.'),
            $this->q('Contoh adaptasi morfologi adalah ...', 'paruh burung pelatuk yang runcing dan kuat untuk melubangi kayu', ['cumi-cumi menyemprotkan tinta saat bahaya', 'ginjal unta menghasilkan urin sangat pekat', 'burung bermigrasi saat musim dingin'], 'Adaptasi morfologi berkaitan dengan bentuk tubuh, misalnya paruh pelatuk.'),
            $this->q('Burung yang bermigrasi ke daerah hangat ketika musim dingin merupakan contoh adaptasi ...', 'tingkah laku', ['morfologi', 'fisiologi', 'anatomi'], 'Migrasi adalah penyesuaian melalui perilaku.'),
            $this->q('Pencemaran udara terutama disebabkan oleh ...', 'asap kendaraan bermotor dan pabrik', ['limbah rumah tangga yang dibuang ke sungai', 'penggunaan pupuk kandang', 'penanaman pohon di pekarangan'], 'Emisi kendaraan dan pabrik menghasilkan gas dan partikel pencemar udara.'),
            $this->q('Penyebab utama pemanasan global adalah meningkatnya kadar ... di atmosfer.', 'karbon dioksida', ['oksigen', 'nitrogen', 'helium'], 'Gas rumah kaca seperti CO₂ memerangkap panas sehingga suhu bumi meningkat.'),
            $this->q('Upaya yang tepat untuk mengurangi sampah plastik adalah ...', 'menerapkan prinsip reduce, reuse, recycle', ['membakar sampah di halaman', 'membuang sampah ke sungai', 'menimbun semua sampah di dekat sumur'], '3R mengurangi, memakai ulang, dan mendaur ulang sampah.'),
            $this->q('Hasil fotosintesis adalah ...', 'glukosa dan oksigen', ['karbon dioksida dan air', 'protein dan nitrogen', 'glukosa dan karbon dioksida'], 'Fotosintesis: CO₂ + H₂O + cahaya → glukosa + O₂.'),
            $this->q('Bahan baku fotosintesis adalah ...', 'karbon dioksida dan air', ['oksigen dan glukosa', 'oksigen dan air', 'nitrogen dan glukosa'], 'Tumbuhan menyerap CO₂ dari udara dan air dari tanah.'),
            $this->q('Organel sel tempat berlangsungnya fotosintesis adalah ...', 'kloroplas', ['mitokondria', 'ribosom', 'nukleus'], 'Kloroplas mengandung klorofil yang menangkap cahaya matahari.'),
            $this->q('Organisme yang berperan sebagai dekomposer adalah ...', 'jamur dan bakteri pengurai', ['singa dan harimau', 'ular dan elang', 'kelinci dan kambing'], 'Jamur dan bakteri menguraikan sisa organisme.'),
            $this->q('Perkembangbiakan vegetatif alami pada tumbuhan terjadi dengan ...', 'tunas pada pisang', ['okulasi', 'stek batang', 'cangkok'], 'Okulasi, stek, dan cangkok adalah vegetatif buatan; tunas pisang terjadi secara alami.'),
            $this->q('Contoh perkembangbiakan vegetatif buatan adalah ...', 'cangkok', ['tunas pada pisang', 'umbi lapis pada bawang merah', 'geragih pada stroberi'], 'Cangkok dilakukan manusia untuk memperbanyak tanaman.'),
            $this->q('Hewan yang mengalami metamorfosis sempurna adalah ...', 'kupu-kupu', ['belalang', 'kecoa', 'jangkrik'], 'Kupu-kupu melalui tahap telur, larva, pupa, dan dewasa.'),
            $this->q('Urutan metamorfosis sempurna yang benar adalah ...', 'telur → larva → pupa → dewasa', ['telur → nimfa → dewasa', 'telur → pupa → larva → dewasa', 'larva → telur → pupa → dewasa'], 'Metamorfosis sempurna memiliki tahap pupa setelah larva.'),
            $this->q('Hewan ovipar adalah hewan yang berkembang biak dengan cara ... Contohnya adalah ...', 'bertelur; ayam', ['bertelur; kucing', 'melahirkan; sapi', 'melahirkan; paus'], 'Ovipar bertelur, misalnya ayam; kucing, sapi, dan paus adalah vivipar.'),
            $this->q('Hewan vivipar adalah hewan yang ...', 'melahirkan anak', ['bertelur dan telur menetas di luar tubuh induk', 'bertelur dan telur menetas di dalam tubuh induk', 'berkembang biak dengan membelah diri'], 'Vivipar melahirkan anak yang berkembang di dalam tubuh induk.'),
            $this->q('Ciri utama hewan vertebrata adalah ...', 'memiliki tulang belakang', ['tidak memiliki tulang belakang', 'hanya hidup di air', 'selalu bertelur'], 'Vertebrata adalah hewan bertulang belakang.'),
            $this->q('Kelompok hewan yang seluruhnya termasuk mamalia adalah ...', 'paus, kelelawar, dan kucing', ['hiu, penyu, dan ayam', 'katak, ular, dan buaya', 'kupu-kupu, lebah, dan semut'], 'Mamalia menyusui anaknya; paus dan kelelawar tergolong mamalia.'),
            $this->q('Pewarisan sifat dari induk kepada keturunannya dibawa oleh ...', 'gen pada kromosom', ['mitokondria', 'ribosom', 'sel darah'], 'Gen terletak pada kromosom dan menurunkan sifat.'),
            $this->q('Pada persilangan Bb × Bb (B dominan), peluang keturunan bergenotipe bb adalah ...', '25%', ['0%', '50%', '75%'], 'Persilangan Bb × Bb menghasilkan BB : Bb : bb = 1 : 2 : 1, sehingga bb = 25%.'),
        ];
    }

    private function ipaFisika(): array
    {
        return [
            $this->q('Energi yang dimiliki benda karena geraknya disebut energi ...', 'kinetik', ['potensial', 'kimia', 'kalor'], 'Energi kinetik bergantung pada massa dan kecepatan benda.'),
            $this->q('Perubahan energi pada kipas angin yang menyala adalah ...', 'listrik menjadi gerak', ['gerak menjadi listrik', 'kimia menjadi bunyi', 'cahaya menjadi kalor'], 'Motor kipas mengubah energi listrik menjadi energi gerak.'),
            $this->q('Pada lampu pijar, energi listrik sebagian besar diubah menjadi ...', 'cahaya dan kalor', ['bunyi dan gerak', 'kimia dan gerak', 'magnet dan bunyi'], 'Lampu pijar memancarkan cahaya sekaligus memanas.'),
            $this->q('Hukum kekekalan energi menyatakan bahwa energi ...', 'tidak dapat diciptakan dan dimusnahkan, hanya berubah bentuk', ['dapat diciptakan dari ketiadaan', 'selalu hilang sebagai kalor', 'hanya dapat berubah menjadi energi listrik'], 'Total energi dalam sistem tertutup selalu tetap.'),
            $this->q('Sumber energi terbarukan ditunjukkan oleh pasangan ...', 'angin dan air', ['batu bara dan minyak bumi', 'gas alam dan batu bara', 'minyak bumi dan uranium'], 'Angin dan air tidak habis dan dapat dimanfaatkan terus-menerus.'),
            $this->q('Cermin cekung bersifat ...', 'mengumpulkan cahaya (konvergen)', ['menyebarkan cahaya (divergen)', 'menyerap cahaya', 'tidak memantulkan cahaya'], 'Cermin cekung mengumpulkan berkas cahaya sejajar ke satu titik fokus.'),
            $this->q('Bunyi tidak dapat merambat melalui ...', 'ruang hampa', ['udara', 'air', 'logam'], 'Bunyi memerlukan medium zat perantara.'),
            $this->q('Gelombang bunyi dengan frekuensi di atas 20.000 Hz disebut ...', 'ultrasonik', ['infrasonik', 'audiosonik', 'subsonik'], 'Ultrasonik berfrekuensi lebih dari 20.000 Hz.'),
            $this->q('Bunyi pantul yang terdengar setelah bunyi asli selesai disebut ...', 'gema', ['dengung', 'resonansi', 'frekuensi'], 'Gema terjadi bila bunyi pantul tiba setelah bunyi asli berakhir.'),
            $this->q('Pembiasan cahaya terjadi ketika cahaya ...', 'merambat dari satu medium ke medium lain yang berbeda kerapatannya', ['mengenai permukaan cermin', 'diserap permukaan hitam', 'melewati ruang hampa'], 'Perubahan kecepatan di batas dua medium membelokkan arah cahaya.'),
            $this->q('Pensil tampak patah saat dimasukkan sebagian ke gelas berisi air. Peristiwa ini disebabkan oleh ...', 'pembiasan cahaya', ['pemantulan cahaya', 'penyerapan cahaya', 'penguraian cahaya'], 'Cahaya dibiaskan di batas air dan udara.'),
            $this->q('Warna-warna pelangi terbentuk karena ...', 'penguraian cahaya putih oleh butir air hujan', ['pemantulan cahaya oleh awan', 'penyerapan cahaya oleh bumi', 'pembiasan bunyi di udara'], 'Butir air membiaskan dan menguraikan cahaya putih menjadi spektrum warna.'),
            $this->q('Penggaris plastik yang digosok kain wol dapat menarik potongan kertas kecil karena ...', 'penggaris memperoleh muatan listrik statis', ['penggaris menjadi magnet permanen', 'gaya gravitasi penggaris bertambah', 'penggaris menyerap kertas'], 'Gesekan memindahkan elektron sehingga benda bermuatan statis.'),
            $this->q('Kutub magnet yang senama akan ...', 'tolak-menolak', ['tarik-menarik', 'tidak berinteraksi', 'saling menghilangkan sifat magnet'], 'Kutub senama tolak-menolak, kutub berlainan tarik-menarik.'),
            $this->q('Perpindahan kalor tanpa zat perantara terjadi secara ...', 'radiasi', ['konduksi', 'konveksi', 'induksi'], 'Radiasi (misalnya dari Matahari) tidak memerlukan medium.'),
        ];
    }

    private function ipaKimia(): array
    {
        return [
            $this->q('Perubahan wujud dari cair menjadi gas disebut ...', 'menguap', ['mencair', 'menyublim', 'membeku'], 'Zat cair berubah menjadi gas saat menguap.'),
            $this->q('Perubahan wujud padat langsung menjadi gas disebut ...', 'menyublim', ['menguap', 'mengembun', 'melebur'], 'Contohnya kapur barus yang mengecil tanpa mencair.'),
            $this->q('Contoh perubahan kimia adalah ...', 'kayu terbakar menjadi arang dan abu', ['es mencair', 'air menguap', 'gula larut dalam air'], 'Pembakaran menghasilkan zat baru, sehingga merupakan perubahan kimia.'),
            $this->q('Ciri terjadinya perubahan kimia adalah ...', 'terbentuk zat baru', ['bentuk benda berubah saja', 'ukuran benda mengecil', 'wujud benda kembali seperti semula'], 'Perubahan kimia selalu menghasilkan zat baru dengan sifat berbeda.'),
            $this->q('Campuran heterogen (tidak merata) ditunjukkan oleh ...', 'air dan pasir', ['air dan gula', 'udara bersih', 'teh manis'], 'Pasir tidak larut sehingga tampak terpisah dari air.'),
            $this->q('Teknik yang tepat untuk memisahkan campuran pasir dan air adalah ...', 'penyaringan (filtrasi)', ['destilasi', 'kromatografi', 'sublimasi'], 'Pasir tidak larut dan tertahan oleh kertas saring.'),
            $this->q('Campuran air dan garam dapat dipisahkan dengan cara ...', 'penguapan', ['penyaringan', 'menggunakan magnet', 'dekantasi'], 'Air menguap, garam tertinggal.'),
            $this->q('Larutan dengan pH 3 bersifat ...', 'asam', ['basa', 'netral', 'tidak dapat ditentukan'], 'pH di bawah 7 menunjukkan sifat asam.'),
            $this->q('Zat yang memiliki pH di atas 7 adalah ...', 'sabun', ['cuka', 'air jeruk', 'air aki'], 'Sabun bersifat basa.'),
            $this->q('Kertas lakmus merah akan berubah menjadi biru jika dicelupkan ke dalam larutan ...', 'basa', ['asam', 'netral', 'asam kuat'], 'Lakmus merah menjadi biru pada larutan basa.'),
            $this->q('Rumus kimia air adalah ...', 'H₂O', ['CO₂', 'O₂', 'NaCl'], 'Satu molekul air terdiri atas dua atom hidrogen dan satu atom oksigen.'),
            $this->q('Gas yang dihasilkan manusia saat bernapas dan dibutuhkan tumbuhan untuk fotosintesis adalah ...', 'karbon dioksida', ['oksigen', 'nitrogen', 'hidrogen'], 'Manusia mengeluarkan CO₂ yang digunakan tumbuhan.'),
            $this->q('Partikel terkecil dari suatu unsur yang masih memiliki sifat unsur tersebut adalah ...', 'atom', ['molekul', 'senyawa', 'campuran'], 'Atom adalah partikel terkecil unsur.'),
            $this->q('Partikel penyusun atom yang bermuatan positif adalah ...', 'proton', ['elektron', 'neutron', 'kuark bebas'], 'Proton bermuatan positif, elektron negatif, neutron netral.'),
            $this->q('Karat pada paku besi terjadi karena besi bereaksi dengan ...', 'oksigen dan air', ['nitrogen dan minyak', 'helium dan air', 'karbon dioksida kering saja'], 'Perkaratan membutuhkan oksigen dan uap air.'),
        ];
    }

    private function ipaTataSurya(): array
    {
        return [
            $this->q('Pusat tata surya adalah ...', 'Matahari', ['Bumi', 'Bulan', 'Jupiter'], 'Planet-planet mengorbit Matahari.'),
            $this->q('Planet yang paling dekat dengan Matahari adalah ...', 'Merkurius', ['Venus', 'Mars', 'Bumi'], 'Urutan: Merkurius, Venus, Bumi, Mars, ...'),
            $this->q('Planet terbesar di tata surya adalah ...', 'Jupiter', ['Saturnus', 'Neptunus', 'Bumi'], 'Jupiter memiliki diameter terbesar di antara planet.'),
            $this->q('Planet yang dikenal sebagai planet merah adalah ...', 'Mars', ['Venus', 'Jupiter', 'Merkurius'], 'Permukaan Mars kaya besi oksida sehingga tampak merah.'),
            $this->q('Planet yang memiliki cincin paling jelas terlihat adalah ...', 'Saturnus', ['Mars', 'Venus', 'Merkurius'], 'Cincin Saturnus tersusun dari es dan batuan.'),
            $this->q('Pergantian siang dan malam disebabkan oleh ...', 'rotasi Bumi', ['revolusi Bumi', 'rotasi Bulan', 'revolusi Bulan'], 'Bumi berputar pada porosnya selama sekitar 24 jam.'),
            $this->q('Pergantian musim di bumi disebabkan oleh ...', 'revolusi Bumi dan kemiringan sumbu Bumi', ['rotasi Bumi', 'jarak Bumi ke Bulan', 'perubahan bentuk Matahari'], 'Kemiringan sumbu menyebabkan penyinaran Matahari berubah selama Bumi mengelilingi Matahari.'),
            $this->q('Waktu yang dibutuhkan Bumi untuk satu kali revolusi adalah ...', 'sekitar 365¼ hari', ['24 jam', '30 hari', '12 jam'], 'Satu tahun = sekitar 365¼ hari.'),
            $this->q('Gerhana matahari terjadi apabila ...', 'Bulan berada di antara Matahari dan Bumi', ['Bumi berada di antara Matahari dan Bulan', 'Matahari berada di antara Bumi dan Bulan', 'Mars berada di antara Bumi dan Bulan'], 'Bayangan Bulan menutupi sebagian permukaan Bumi.'),
            $this->q('Gerhana bulan terjadi pada saat ...', 'bulan purnama, Bumi berada di antara Matahari dan Bulan', ['bulan baru, Bulan berada di antara Matahari dan Bumi', 'bulan sabit, Mars berada di antara Bumi dan Bulan', 'bulan separuh, Matahari berada di antara Bumi dan Bulan'], 'Bulan masuk ke bayangan Bumi saat purnama.'),
            $this->q('Pasang surut air laut terutama dipengaruhi oleh gaya gravitasi ...', 'Bulan', ['Mars', 'Venus', 'Jupiter'], 'Bulan paling dekat dengan Bumi sehingga pengaruhnya terbesar.'),
            $this->q('Sabuk asteroid terletak di antara orbit planet ...', 'Mars dan Jupiter', ['Bumi dan Mars', 'Jupiter dan Saturnus', 'Venus dan Bumi'], 'Sabuk asteroid utama berada di antara Mars dan Jupiter.'),
            $this->q('Satelit alami Bumi adalah ...', 'Bulan', ['Fobos', 'Titan', 'Europa'], 'Fobos milik Mars, Titan milik Saturnus, Europa milik Jupiter.'),
            $this->q('Galaksi tempat tata surya berada bernama ...', 'Bimasakti', ['Andromeda', 'Magellan', 'Sombrero'], 'Tata surya berada di galaksi Bimasakti (Milky Way).'),
            $this->q('Planet yang dijuluki kembaran Bumi karena ukurannya hampir sama adalah ...', 'Venus', ['Mars', 'Merkurius', 'Neptunus'], 'Venus memiliki ukuran dan massa mirip Bumi.'),
        ];
    }

    private function ipaStruktur(): array
    {
        return [
            $this->q('Organel sel yang berfungsi sebagai tempat respirasi sel adalah ...', 'mitokondria', ['ribosom', 'kloroplas', 'vakuola'], 'Mitokondria menghasilkan energi (ATP).'),
            $this->q('Bagian sel yang mengendalikan seluruh aktivitas sel adalah ...', 'inti sel (nukleus)', ['ribosom', 'dinding sel', 'lisosom'], 'Inti sel menyimpan materi genetik dan mengatur kegiatan sel.'),
            $this->q('Perbedaan sel tumbuhan dengan sel hewan adalah sel tumbuhan memiliki ...', 'dinding sel dan kloroplas', ['inti sel dan sitoplasma', 'membran sel dan mitokondria', 'ribosom dan lisosom'], 'Dinding sel dan kloroplas khas sel tumbuhan.'),
            $this->q('Akar pada tumbuhan berfungsi untuk ...', 'menyerap air dan mineral dari tanah', ['tempat fotosintesis', 'menghasilkan biji', 'tempat pertukaran gas'], 'Akar menyerap air dan mineral serta menopang tumbuhan.'),
            $this->q('Bagian daun yang berfungsi sebagai tempat pertukaran gas adalah ...', 'stomata', ['xilem', 'floem', 'kutikula'], 'Stomata adalah celah kecil tempat keluar masuk gas.'),
            $this->q('Jaringan pengangkut air dan mineral dari akar ke daun adalah ...', 'xilem', ['floem', 'kambium', 'epidermis'], 'Xilem mengangkut air dan mineral; floem mengangkut hasil fotosintesis.'),
            $this->q('Tempat pertukaran oksigen dan karbon dioksida di paru-paru adalah ...', 'alveolus', ['bronkus', 'trakea', 'faring'], 'Alveolus berdinding tipis dan dikelilingi kapiler darah.'),
            $this->q('Urutan jalur makanan dalam sistem pencernaan manusia yang benar adalah ...', 'mulut → kerongkongan → lambung → usus halus → usus besar', ['mulut → lambung → kerongkongan → usus besar → usus halus', 'mulut → kerongkongan → usus halus → lambung → usus besar', 'kerongkongan → mulut → lambung → usus halus → usus besar'], 'Makanan melewati organ-organ pencernaan secara berurutan.'),
            $this->q('Enzim ptialin pada air liur berfungsi mengubah ...', 'amilum menjadi maltosa', ['protein menjadi asam amino', 'lemak menjadi asam lemak dan gliserol', 'glukosa menjadi glikogen'], 'Ptialin (amilase) memulai pencernaan karbohidrat di mulut.'),
            $this->q('Pembuluh darah yang membawa darah dari jantung ke seluruh tubuh adalah ...', 'arteri', ['vena', 'kapiler', 'pembuluh limfa'], 'Arteri membawa darah keluar dari jantung.'),
            $this->q('Bilik jantung yang memompa darah kaya oksigen ke seluruh tubuh adalah ...', 'bilik kiri', ['bilik kanan', 'serambi kanan', 'serambi kiri'], 'Bilik kiri berdinding paling tebal untuk memompa darah ke seluruh tubuh.'),
            $this->q('Zat sisa yang dikeluarkan melalui paru-paru adalah ...', 'karbon dioksida dan uap air', ['urea dan garam', 'keringat dan urea', 'feses dan urin'], 'Paru-paru mengeluarkan CO₂ dan uap air saat ekspirasi.'),
            $this->q('Golongan darah yang dikenal sebagai donor universal adalah ...', 'O', ['A', 'B', 'AB'], 'Golongan darah O tidak memiliki antigen A maupun B.'),
            $this->q('Sendi yang memungkinkan gerakan ke segala arah, seperti pada bahu, adalah sendi ...', 'peluru', ['engsel', 'pelana', 'putar'], 'Sendi peluru memungkinkan gerak bebas ke segala arah.'),
            $this->q('Otot yang bekerja di luar kehendak dan terdapat pada dinding lambung adalah otot ...', 'polos', ['lurik', 'jantung', 'rangka'], 'Otot polos bekerja tidak sadar pada organ dalam.'),
        ];
    }

    private function ipaPemakan(): array
    {
        return $this->pairQuestions([
            ['sapi', 'Herbivora'], ['harimau', 'Karnivora'], ['ayam', 'Omnivora'],
            ['kambing', 'Herbivora'], ['singa', 'Karnivora'], ['tikus', 'Omnivora'],
            ['trenggiling', 'Insektivora'], ['elang', 'Karnivora'], ['beruang', 'Omnivora'],
            ['kelinci', 'Herbivora'],
        ], 'Berdasarkan jenis makanannya, {k} tergolong hewan ...', '{k} tergolong {v} berdasarkan jenis makanan utamanya.');
    }
}
