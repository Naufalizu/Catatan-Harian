<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\JournalPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JournalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Storage fake agar tidak mengotori NAS asli
        Storage::fake('nas');
    }

    public function test_can_store_a_journal_entry_with_photos_to_nas()
    {
        $photo = UploadedFile::fake()->image('holiday.jpg');

        $payload = [
            'title' => 'Catatan Liburan',
            'journal_date' => '2026-09-13',
            'mood' => 'Happy',
            'content' => 'Hari ini jalan-jalan ke pantai.',
            'photos' => [$photo],
        ];

        $response = $this->post(route('journals.store'), $payload);

        $response->assertRedirect(route('journals.index'));

        $this->assertDatabaseHas('journals', [
            'title' => 'Catatan Liburan',
            'mood' => 'Happy',
        ]);

        $journal = Journal::first();
        $this->assertCount(1, $journal->photos);

        $storedPhotoPath = $journal->photos->first()->file_path;
        Storage::disk('nas')->assertExists($storedPhotoPath);
    }

    public function test_can_update_a_journal_and_delete_specified_photos_from_nas()
    {
        $journal = Journal::create([
            'title' => 'Judul Lama',
            'journal_date' => '2026-09-10',
            'mood' => 'Neutral',
            'content' => 'Isi lama.',
        ]);

        $fakePath = 'journals/2026/09/old_photo.jpg';
        Storage::disk('nas')->put($fakePath, 'dummy content');

        $photo = JournalPhoto::create([
            'journal_id' => $journal->id,
            'file_path' => $fakePath,
            'file_name' => 'old_photo.jpg',
        ]);

        $newPhoto = UploadedFile::fake()->image('new_photo.jpg');

        $payload = [
            'title' => 'Judul Baru Diperbarui',
            'journal_date' => '2026-09-10',
            'mood' => 'Excited',
            'content' => 'Isi diperbarui.',
            'delete_photo_ids' => [$photo->id],
            'photos' => [$newPhoto],
        ];

        $response = $this->put(route('journals.update', $journal->id), $payload);

        $response->assertRedirect(route('journals.index'));

        $this->assertDatabaseHas('journals', [
            'id' => $journal->id,
            'title' => 'Judul Baru Diperbarui',
            'mood' => 'Excited',
        ]);

        $this->assertDatabaseMissing('journal_photos', ['id' => $photo->id]);
        Storage::disk('nas')->assertMissing($fakePath);

        $updatedJournal = $journal->fresh();
        $this->assertCount(1, $updatedJournal->photos);
        Storage::disk('nas')->assertExists($updatedJournal->photos->first()->file_path);
    }

    public function test_can_delete_a_journal_and_its_associated_photos_from_nas()
    {
        $journal = Journal::create([
            'title' => 'Catatan Hendak Dihapus',
            'journal_date' => '2026-09-01',
            'mood' => 'Sad',
            'content' => 'Akan dihapus.',
        ]);

        $fakePath = 'journals/2026/09/to_delete.jpg';
        Storage::disk('nas')->put($fakePath, 'dummy content');

        JournalPhoto::create([
            'journal_id' => $journal->id,
            'file_path' => $fakePath,
            'file_name' => 'to_delete.jpg',
        ]);

        $response = $this->delete(route('journals.destroy', $journal->id));

        $response->assertRedirect(route('journals.index'));
        $this->assertDatabaseMissing('journals', ['id' => $journal->id]);
        Storage::disk('nas')->assertMissing($fakePath);
    }
}