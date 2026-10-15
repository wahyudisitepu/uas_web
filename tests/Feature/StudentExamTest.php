<?php

use App\Filament\Test\Resources\Exams\Pages\StartingExam;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Question;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure we have a student user
    $this->user = User::firstOrCreate(
        ['username' => 'test_student_nis'],
        [
            'name' => 'Siswa Penguji',
            'is_staff' => false,
            'password' => bcrypt('password'),
        ]
    );

    $this->student = Student::firstOrCreate(
        ['user_id' => $this->user->id],
        [
            'nis' => 'test_student_nis',
            'name' => 'Siswa Penguji',
            'gender' => true,
            'reg_year' => 2026,
            'status' => 1,
        ]
    );

    // Ensure we have an active subject with questions
    $this->subject = Subject::firstOrCreate(
        ['name' => 'UJI KOMPETENSI'],
        [
            'description' => 'Mata pelajaran uji coba',
            'is_active' => true,
        ]
    );

    // Create a question with answers
    $this->question = Question::firstOrCreate(
        ['payload' => '<p>Berapakah hasil 5 + 5?</p>'],
        [
            'subject_id' => $this->subject->id,
            'score' => 10,
            'is_active' => true,
            'description' => 'Hasil 5 + 5 adalah 10.',
        ]
    );

    $this->correctAnswer = $this->question->answers()->firstOrCreate(
        ['text' => '10'],
        ['is_correct' => true, 'is_active' => true]
    );

    $this->wrongAnswer = $this->question->answers()->firstOrCreate(
        ['text' => '20'],
        ['is_correct' => false, 'is_active' => true]
    );

    // Create an exam
    $this->exam = Exam::firstOrCreate(
        ['title' => 'UJIAN SIMULASI CBT'],
        [
            'duration' => 60,
            'threshold' => 70,
            'exact_time' => false,
            'started_at' => now()->subMinutes(10),
            'expired_at' => now()->addHours(2),
            'is_available' => true,
        ]
    );

    // Attach subject to exam
    if (!$this->exam->subjects()->where('subjects.id', $this->subject->id)->exists()) {
        $this->exam->subjects()->attach($this->subject->id, ['qty' => 10]);
    }
});

test('siswa dapat mengakses halaman ujian dengan timer dan soal', function () {
    $this->actingAs($this->user);

    Livewire::test(StartingExam::class, ['exam' => $this->exam])
        ->assertOk()
        ->assertSee('UJIAN SIMULASI CBT')
        ->assertSee('Sisa Waktu')
        ->assertSee('Berapakah hasil 5 + 5?')
        ->assertSee('10')
        ->assertSee('20')
        ->assertSet('isFinished', false);
});

test('siswa dapat memilih jawaban dan menandai ragu-ragu', function () {
    $this->actingAs($this->user);

    Livewire::test(StartingExam::class, ['exam' => $this->exam])
        ->call('selectAnswer', $this->question->id, $this->correctAnswer->id)
        ->assertSet("selectedAnswers.{$this->question->id}", $this->correctAnswer->id)
        ->call('toggleDoubtful', $this->question->id)
        ->assertSet("doubtfulAnswers.{$this->question->id}", true);
});

test('siswa dapat menyelesaikan ujian dan melihat hasil nilai serta pembahasan', function () {
    $this->actingAs($this->user);

    Livewire::test(StartingExam::class, ['exam' => $this->exam])
        ->call('selectAnswer', $this->question->id, $this->correctAnswer->id)
        ->call('finishExam')
        ->assertSet('isFinished', true)
        ->assertSee('Nilai Akhir')
        ->assertSee('LULUS')
        ->assertSee('Hasil Ujian: Lulus')
        ->assertSee('Jawaban Benar')
        ->assertSee('Tingkat Akurasi');

    // Pastikan tersimpan di database
    expect(ExamResult::where('exam_id', $this->exam->id)->where('user_id', $this->user->id)->exists())->toBeTrue();
});

test('siswa mendapatkan status belum mencapai kkm jika nilai di bawah threshold', function () {
    $this->actingAs($this->user);

    Livewire::test(StartingExam::class, ['exam' => $this->exam])
        ->call('selectAnswer', $this->question->id, $this->wrongAnswer->id)
        ->call('finishExam')
        ->assertSet('isFinished', true)
        ->assertSee('Nilai Akhir')
        ->assertSee('Belum Mencapai KKM')
        ->assertSee('TIDAK LULUS');
});

test('siswa dapat bernavigasi antar soal dan mengubah ukuran font atau mode tampilan', function () {
    $this->actingAs($this->user);

    Livewire::test(StartingExam::class, ['exam' => $this->exam])
        ->call('setFontSize', 'large')
        ->assertSet('fontSize', 'large')
        ->call('setViewMode', 'all')
        ->assertSet('viewMode', 'all')
        ->call('setViewMode', 'focus')
        ->assertSet('viewMode', 'focus');
});

test('siswa dapat melakukan uji ulang ujian yang telah selesai', function () {
    $this->actingAs($this->user);

    $test = Livewire::test(StartingExam::class, ['exam' => $this->exam])
        ->call('selectAnswer', $this->question->id, $this->correctAnswer->id)
        ->call('finishExam')
        ->assertSet('isFinished', true);

    expect(ExamResult::where('exam_id', $this->exam->id)->where('user_id', $this->user->id)->exists())->toBeTrue();

    // Call retake
    $test->call('retakeExam')
        ->assertSet('isFinished', false);

    expect(ExamResult::where('exam_id', $this->exam->id)->where('user_id', $this->user->id)->exists())->toBeFalse();
});

