<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\JournalPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class JournalController extends Controller
{
    /**
     * Display a listing of journals with search & filter.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $moodFilter = $request->input('mood');
        $dateFilter = $request->input('date');

        $query = Journal::with('photos')->orderBy('journal_date', 'desc')->orderBy('id', 'desc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if (!empty($moodFilter)) {
            $query->where('mood', $moodFilter);
        }

        if (!empty($dateFilter)) {
            $query->whereDate('journal_date', $dateFilter);
        }

        $journals = $query->paginate(12)->withQueryString();

        // Calculate statistics for dashboard summary
        $totalCount = Journal::count();
        $thisMonthCount = Journal::whereMonth('journal_date', date('m'))
            ->whereYear('journal_date', date('Y'))
            ->count();

        $topMoodRecord = Journal::select('mood', DB::raw('count(*) as count'))
            ->groupBy('mood')
            ->orderBy('count', 'desc')
            ->first();
        $dominantMood = $topMoodRecord ? $topMoodRecord->mood : '-';

        $availableMoods = [
            'Happy' => '😊 Happy',
            'Neutral' => '😐 Neutral',
            'Sad' => '😢 Sad',
            'Productive' => '⚡ Productive',
            'Excited' => '🚀 Excited',
            'Calm' => '🌿 Calm',
        ];

        return view('journals.index', compact(
            'journals',
            'search',
            'moodFilter',
            'dateFilter',
            'totalCount',
            'thisMonthCount',
            'dominantMood',
            'availableMoods'
        ));
    }

    /**
     * Store a newly created journal entry in storage.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'journal_date' => 'required|date',
            'mood' => 'required|string|max:50',
            'content' => 'required|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,webp,heic|max:20480',
        ]);

        DB::beginTransaction();
        try {
            $journal = Journal::create([
                'title' => $validated['title'],
                'journal_date' => $validated['journal_date'],
                'mood' => $validated['mood'],
                'content' => $validated['content'],
            ]);

            if ($request->hasFile('photos')) {
                $year = date('Y', strtotime($validated['journal_date']));
                $month = date('m', strtotime($validated['journal_date']));
                $folder = "journals/{$year}/{$month}";

                foreach ($request->file('photos') as $photoFile) {
                    $originalName = $photoFile->getClientOriginalName();
                    $storedPath = $photoFile->store($folder, 'nas');

                    JournalPhoto::create([
                        'journal_id' => $journal->id,
                        'file_path' => $storedPath,
                        'file_name' => $originalName,
                    ]);
                }
            }

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Catatan harian berhasil disimpan!',
                    'journal' => $journal->load('photos'),
                ]);
            }

            return redirect()->route('journals.index')->with('success', 'Catatan harian berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan catatan: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Gagal menyimpan catatan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified journal entry.
     */
    public function show(Journal $journal, Request $request): JsonResponse|View
    {
        $journal->load('photos');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'journal' => $journal,
            ]);
        }

        return view('journals.show', compact('journal'));
    }

    /**
     * Update the specified journal entry in storage.
     */
    public function update(Request $request, Journal $journal): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'journal_date' => 'required|date',
            'mood' => 'required|string|max:50',
            'content' => 'required|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,webp,heic|max:20480',
            'delete_photo_ids' => 'nullable|array',
            'delete_photo_ids.*' => 'integer|exists:journal_photos,id',
        ]);

        DB::beginTransaction();
        try {
            $journal->update([
                'title' => $validated['title'],
                'journal_date' => $validated['journal_date'],
                'mood' => $validated['mood'],
                'content' => $validated['content'],
            ]);

            // Handle deletion of individual photos
            if (!empty($validated['delete_photo_ids'])) {
                $photosToDelete = JournalPhoto::where('journal_id', $journal->id)
                    ->whereIn('id', $validated['delete_photo_ids'])
                    ->get();

                foreach ($photosToDelete as $photo) {
                    if (Storage::disk('nas')->exists($photo->file_path)) {
                        Storage::disk('nas')->delete($photo->file_path);
                    }
                    $photo->delete();
                }
            }

            // Handle uploading new photos
            if ($request->hasFile('photos')) {
                $year = date('Y', strtotime($validated['journal_date']));
                $month = date('m', strtotime($validated['journal_date']));
                $folder = "journals/{$year}/{$month}";

                foreach ($request->file('photos') as $photoFile) {
                    $originalName = $photoFile->getClientOriginalName();
                    $storedPath = $photoFile->store($folder, 'nas');

                    JournalPhoto::create([
                        'journal_id' => $journal->id,
                        'file_path' => $storedPath,
                        'file_name' => $originalName,
                    ]);
                }
            }

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Catatan harian berhasil diperbarui!',
                    'journal' => $journal->load('photos'),
                ]);
            }

            return redirect()->route('journals.index')->with('success', 'Catatan harian berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui catatan: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Gagal memperbarui catatan: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified journal entry and its NAS physical files.
     */
    public function destroy(Journal $journal, Request $request): JsonResponse|RedirectResponse
    {
        DB::beginTransaction();
        try {
            $photos = $journal->photos;

            // Delete physical files from NAS storage
            foreach ($photos as $photo) {
                if (Storage::disk('nas')->exists($photo->file_path)) {
                    Storage::disk('nas')->delete($photo->file_path);
                }
            }

            // Delete journal record (cascade deletes DB records in journal_photos)
            $journal->delete();

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Catatan harian dan file lampiran NAS berhasil dihapus!',
                ]);
            }

            return redirect()->route('journals.index')->with('success', 'Catatan harian dan file lampiran NAS berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus catatan: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Gagal menghapus catatan: ' . $e->getMessage());
        }
    }

    /**
     * Serve photo binary content from NAS storage.
     */
    public function servePhoto(JournalPhoto $photo)
    {
        if (!Storage::disk('nas')->exists($photo->file_path)) {
            abort(404, 'File gambar tidak ditemukan di storage NAS.');
        }

        return Storage::disk('nas')->response($photo->file_path);
    }
}
