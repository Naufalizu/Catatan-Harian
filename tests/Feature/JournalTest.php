<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\JournalPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('nas');
    }

    public function test_can_view_journal_dashboard()
    {
        Journal::create([
            'title' => 'Catatan Pertama',
            'journal_date' => '2026-09-06',
            'mood' => 'Happy',
            'content' => 'Hari ini menyenangkan!',
        ]);

        $response = $this->get(route('journals.index'));

        $response->assertStatus(200);
        $response->assertSee('Catatan Pertama');
        $response->assertSee('Happy');
    }

    public function test_can_store_journal_with_nas_photos()
    {
        $file1 = UploadedFile::fake()->image('photo1.jpg');
        $file2 = UploadedFile::fake()->image('photo2.png');

        $response = $this->postJson(route('journals.store'), [
            'title' => 'Liburan Akhir Pekan',
            'journal_date' => '2026-09-06',
            'mood' => 'Excited',
            'content' => 'Jalan-jalan bersama keluarga.',
            'photos' => [$file1, $file2],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('journals', [
            'title' => 'Liburan Akhir Pekan',
            'mood' => 'Excited',
        ]);

        $journal = Journal::first();
        $this->assertCount(2, $journal->photos);

        foreach ($journal->photos as $photo) {
            Storage::disk('nas')->assertExists($photo->file_path);
        }
    }

    public function test_can_serve_nas_photo()
    {
        $file = UploadedFile::fake()->image('test_nas.jpg');

        $this->postJson(route('journals.store'), [
            'title' => 'Foto Test',
            'journal_date' => '2026-09-06',
            'mood' => 'Calm',
            'content' => 'Test serve photo',
            'photos' => [$file],
        ]);

        $photo = JournalPhoto::first();

        $response = $this->get(route('photos.show', $photo->id));
        $response->assertStatus(200);
    }

    public function test_can_update_journal_and_delete_specific_nas_photo()
    {
        $file1 = UploadedFile::fake()->image('keep.jpg');
        $file2 = UploadedFile::fake()->image('delete_me.jpg');

        $this->postJson(route('journals.store'), [
            'title' => 'Judul Lama',
            'journal_date' => '2026-09-06',
            'mood' => 'Neutral',
            'content' => 'Konten lama',
            'photos' => [$file1, $file2],
        ]);

        $journal = Journal::first();
        $photoToDelete = $journal->photos->where('file_name', 'delete_me.jpg')->first();
        $photoToKeep = $journal->photos->where('file_name', 'keep.jpg')->first();

        $newFile = UploadedFile::fake()->image('new_photo.jpg');

        $response = $this->postJson(route('journals.update', $journal->id), [
            '_method' => 'PUT',
            'title' => 'Judul Baru',
            'journal_date' => '2026-09-06',
            'mood' => 'Productive',
            'content' => 'Konten diperbarui',
            'delete_photo_ids' => [$photoToDelete->id],
            'photos' => [$newFile],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('journals', [
            'id' => $journal->id,
            'title' => 'Judul Baru',
            'mood' => 'Productive',
        ]);

        $this->assertDatabaseMissing('journal_photos', [
            'id' => $photoToDelete->id,
        ]);

        Storage::disk('nas')->assertMissing($photoToDelete->file_path);
        Storage::disk('nas')->assertExists($photoToKeep->file_path);

        $this->assertCount(2, $journal->fresh()->photos);
    }

    public function test_can_delete_journal_and_cascade_delete_nas_photos()
    {
        $file = UploadedFile::fake()->image('photo_cascade.jpg');

        $this->postJson(route('journals.store'), [
            'title' => 'Catatan Untuk Dihapus',
            'journal_date' => '2026-09-06',
            'mood' => 'Sad',
            'content' => 'Catatan ini akan dihapus.',
            'photos' => [$file],
        ]);

        $journal = Journal::first();
        $photo = $journal->photos->first();

        $response = $this->deleteJson(route('journals.destroy', $journal->id));

        $response->assertStatus(200);

        $this->assertDatabaseMissing('journals', ['id' => $journal->id]);
        $this->assertDatabaseMissing('journal_photos', ['id' => $photo->id]);

        Storage::disk('nas')->assertMissing($photo->file_path);
    }
}
