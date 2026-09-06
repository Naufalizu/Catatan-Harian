@extends('layouts.app')

@section('content')
<div x-data="journalApp()" x-init="initData()" class="space-y-6" x-cloak>

    <!-- Toast Notification -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 max-w-md px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3 border text-sm font-medium"
         :class="{
             'bg-emerald-900/90 text-emerald-100 border-emerald-700': toast.type === 'success',
             'bg-rose-900/90 text-rose-100 border-rose-700': toast.type === 'error'
         }">
        <i class="fa-solid" :class="toast.type === 'success' ? 'fa-circle-check text-emerald-400' : 'fa-circle-exclamation text-rose-400'"></i>
        <span x-text="toast.message"></span>
        <button @click="toast.show = false" class="ml-auto text-white/60 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <!-- Dashboard Summary Cards & Title Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                Catatan Harian Saya
            </h2>
            <p class="text-slate-500 text-sm mt-1">Kelola memori, aktivitas, dan lampiran foto resolusi tinggi di storage NAS Anda.</p>
        </div>
        
        <button @click="openCreateModal()" 
                class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white font-semibold px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-200 group">
            <i class="fa-solid fa-plus text-sm group-hover:scale-110 transition-transform"></i>
            <span>Tambah Catatan Baru</span>
        </button>
    </div>

    <!-- Dashboard Stats Widget -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-brand-900 to-brand-800 text-white p-5 rounded-2xl shadow-sm border border-brand-700/50 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-brand-200 uppercase tracking-wider">Total Catatan</p>
                <h3 class="text-3xl font-extrabold mt-1">{{ $totalCount }}</h3>
            </div>
            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-brand-200">
                <i class="fa-solid fa-journal-whills text-2xl"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Bulan Ini</p>
                <h3 class="text-3xl font-extrabold text-slate-800 mt-1">{{ $thisMonthCount }}</h3>
            </div>
            <div class="w-12 h-12 bg-brand-50 rounded-xl flex items-center justify-center text-brand-600">
                <i class="fa-solid fa-calendar-check text-2xl"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Mood Terbanyak</p>
                <h3 class="text-2xl font-extrabold text-brand-700 mt-1 flex items-center gap-2">
                    {{ $dominantMood }}
                </h3>
            </div>
            <div class="w-12 h-12 bg-brand-50 rounded-xl flex items-center justify-center text-brand-600">
                <i class="fa-solid fa-face-smile text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200/80">
        <form method="GET" action="{{ route('journals.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            
            <!-- Search Input -->
            <div class="sm:col-span-5 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}"
                       placeholder="Cari berdasarkan judul atau isi..." 
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all">
            </div>

            <!-- Mood Filter -->
            <div class="sm:col-span-3">
                <select name="mood" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all">
                    <option value="">-- Semua Mood --</option>
                    @foreach($availableMoods as $key => $label)
                        <option value="{{ $key }}" {{ $moodFilter === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Date Filter -->
            <div class="sm:col-span-3">
                <input type="date" 
                       name="date" 
                       value="{{ $dateFilter }}"
                       class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all">
            </div>

            <!-- Action Buttons -->
            <div class="sm:col-span-1 flex items-center gap-1">
                <button type="submit" 
                        class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-semibold transition-colors flex items-center justify-center shadow-sm"
                        title="Terapkan Filter">
                    <i class="fa-solid fa-filter"></i>
                </button>
                @if($search || $moodFilter || $dateFilter)
                    <a href="{{ route('journals.index') }}" 
                       class="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm transition-colors flex items-center justify-center"
                       title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>

        </form>
    </div>

    <!-- Journal Cards Grid -->
    @if($journals->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($journals as $journal)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 hover:shadow-md hover:border-brand-200 transition-all duration-200 flex flex-col overflow-hidden group">
                    
                    <!-- Card Top Header -->
                    <div class="p-5 pb-3">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 flex items-center gap-1.5 border border-slate-200/60">
                                <i class="fa-regular fa-calendar text-brand-600"></i>
                                {{ \Carbon\Carbon::parse($journal->journal_date)->translatedFormat('d M Y') }}
                            </span>
                            
                            @php
                                $moodBadges = [
                                    'Happy' => 'bg-emerald-50 text-emerald-700 border-emerald-200 icon-fa-face-smile',
                                    'Neutral' => 'bg-slate-100 text-slate-700 border-slate-200 icon-fa-face-meh',
                                    'Sad' => 'bg-amber-50 text-amber-700 border-amber-200 icon-fa-face-frown',
                                    'Productive' => 'bg-blue-50 text-blue-700 border-blue-200 icon-fa-bolt',
                                    'Excited' => 'bg-purple-50 text-purple-700 border-purple-200 icon-fa-rocket',
                                    'Calm' => 'bg-teal-50 text-teal-700 border-teal-200 icon-fa-leaf',
                                ];
                                $badgeStyle = $moodBadges[$journal->mood] ?? 'bg-brand-50 text-brand-700 border-brand-200';
                            @endphp

                            <span class="text-xs font-bold px-2.5 py-1 rounded-lg border {{ $badgeStyle }} flex items-center gap-1">
                                {{ $journal->mood }}
                            </span>
                        </div>

                        <h3 class="text-lg font-bold text-slate-800 group-hover:text-brand-700 transition-colors line-clamp-1">
                            {{ $journal->title }}
                        </h3>

                        <p class="text-slate-600 text-sm mt-2 line-clamp-3 leading-relaxed">
                            {{ $journal->content }}
                        </p>
                    </div>

                    <!-- Photo Thumbnails Preview -->
                    @if($journal->photos->count() > 0)
                        <div class="px-5 py-2 border-t border-slate-100 bg-slate-50/50">
                            <div class="flex items-center gap-2 overflow-x-auto py-1 scrollbar-none">
                                @foreach($journal->photos->take(4) as $photo)
                                    <div class="w-12 h-12 rounded-lg bg-slate-200 flex-shrink-0 overflow-hidden border border-slate-200 relative group/img">
                                        <img src="{{ route('photos.show', $photo->id) }}" alt="{{ $photo->file_name }}" class="w-full h-full object-cover">
                                    </div>
                                @endforeach
                                @if($journal->photos->count() > 4)
                                    <div class="w-12 h-12 rounded-lg bg-brand-900 text-white font-bold text-xs flex items-center justify-center flex-shrink-0 shadow-inner">
                                        +{{ $journal->photos->count() - 4 }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Card Actions Footer -->
                    <div class="mt-auto p-4 border-t border-slate-100 bg-slate-50/80 flex items-center justify-between text-xs">
                        <span class="text-slate-400 font-medium flex items-center gap-1">
                            <i class="fa-solid fa-paperclip"></i>
                            {{ $journal->photos->count() }} foto NAS
                        </span>

                        <div class="flex items-center gap-2">
                            <button @click="openDetailModal({{ json_encode($journal->load('photos')) }})" 
                                    class="px-3 py-1.5 bg-brand-50 hover:bg-brand-100 text-brand-700 font-semibold rounded-lg transition-colors flex items-center gap-1">
                                <i class="fa-regular fa-eye"></i> Detail
                            </button>

                            <button @click="openEditModal({{ json_encode($journal->load('photos')) }})" 
                                    class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold rounded-lg transition-colors flex items-center gap-1">
                                <i class="fa-regular fa-pen-to-square"></i> Edit
                            </button>

                            <button @click="openDeleteModal({{ json_encode($journal) }})" 
                                    class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold rounded-lg transition-colors flex items-center gap-1">
                                <i class="fa-regular fa-trash-can"></i> Hapus
                            </button>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $journals->links() }}
        </div>
    @else
        <div class="bg-white rounded-2xl p-12 text-center border border-slate-200/80 shadow-sm max-w-lg mx-auto">
            <div class="w-16 h-16 bg-brand-50 text-brand-600 rounded-full flex items-center justify-center mx-auto text-2xl mb-4">
                <i class="fa-solid fa-feather-pointed"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800">Belum Ada Catatan</h3>
            <p class="text-slate-500 text-sm mt-1 max-w-sm mx-auto">
                @if($search || $moodFilter || $dateFilter)
                    Tidak ada catatan yang cocok dengan kriteria filter pencarian Anda.
                @else
                    Mulai tulis catatan harian pertama Anda dan lampirkan foto kenangan langsung ke storage NAS.
                @endif
            </p>
            <div class="mt-6 flex justify-center gap-3">
                @if($search || $moodFilter || $dateFilter)
                    <a href="{{ route('journals.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm">
                        Reset Filter
                    </a>
                @endif
                <button @click="openCreateModal()" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-xl text-sm shadow-md">
                    + Tambah Catatan Baru
                </button>
            </div>
        </div>
    @endif


    <!-- ========================================== -->
    <!-- CUSTOM MODAL: TAMBAH CATATAN               -->
    <!-- ========================================== -->
    <div x-show="activeModal === 'create'" 
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal()"></div>

        <!-- Modal Box -->
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full border border-slate-200 overflow-hidden transform transition-all"
                 @click.away="closeModal()">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-brand-900 to-brand-700 text-white px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold flex items-center gap-2">
                        <i class="fa-solid fa-pen-nib text-brand-300"></i>
                        Tambah Catatan Harian Baru
                    </h3>
                    <button @click="closeModal()" class="text-white/70 hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="submitCreate()" class="p-6 space-y-5">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <!-- Judul -->
                        <div class="sm:col-span-8 space-y-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Judul Catatan <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="form.title" required placeholder="Judul momen atau kegiatan hari ini..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <!-- Tanggal -->
                        <div class="sm:col-span-4 space-y-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Tanggal <span class="text-rose-500">*</span></label>
                            <input type="date" x-model="form.journal_date" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Mood Selection -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pilih Mood Hari Ini <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                            <template x-for="(label, key) in moodOptions" :key="key">
                                <button type="button" 
                                        @click="form.mood = key"
                                        class="py-2 px-3 rounded-xl border text-xs font-semibold flex items-center justify-center gap-1.5 transition-all"
                                        :class="form.mood === key 
                                            ? 'bg-brand-600 text-white border-brand-700 shadow-md ring-2 ring-brand-300' 
                                            : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'">
                                    <span x-text="label"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Content Textarea -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Isi Catatan <span class="text-rose-500">*</span></label>
                        <textarea x-model="form.content" rows="5" required placeholder="Tuliskan pengalaman, cerita, refleksi, atau rencana hari ini..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none leading-relaxed"></textarea>
                    </div>

                    <!-- Upload Multiple Photos to NAS -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Lampiran Foto (Disimpan Langsung ke NAS)</label>
                        
                        <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 bg-slate-50 hover:bg-brand-50/50 hover:border-brand-300 transition-colors text-center cursor-pointer relative"
                             @click="$refs.createFileInput.click()">
                            <input type="file" x-ref="createFileInput" @change="handleFileSelect($event)" multiple accept="image/*" class="hidden">
                            <i class="fa-solid fa-cloud-arrow-up text-2xl text-brand-600 mb-1"></i>
                            <p class="text-xs font-semibold text-slate-700">Klik untuk memilih foto dari perangkat</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Format JPG, PNG, WEBP resolusi tinggi didukung</p>
                        </div>

                        <!-- Selected Files Preview -->
                        <template x-if="photoPreviews.length > 0">
                            <div class="flex items-center gap-2 overflow-x-auto py-2">
                                <template x-for="(preview, index) in photoPreviews" :key="index">
                                    <div class="relative w-16 h-16 rounded-lg overflow-hidden border border-slate-200 flex-shrink-0 group">
                                        <img :src="preview.url" class="w-full h-full object-cover">
                                        <button type="button" @click="removeNewPhoto(index)" class="absolute top-1 right-1 w-5 h-5 bg-rose-600 text-white rounded-full flex items-center justify-center text-[10px] shadow">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Modal Footer Actions -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" @click="closeModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold transition-colors">
                            Batal
                        </button>
                        <button type="submit" :disabled="isSubmitting" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-semibold transition-colors flex items-center gap-2 shadow-md disabled:opacity-50">
                            <i x-show="isSubmitting" class="fa-solid fa-spinner fa-spin"></i>
                            <span x-text="isSubmitting ? 'Menyimpan ke NAS...' : 'Simpan Catatan'"></span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>


    <!-- ========================================== -->
    <!-- CUSTOM MODAL: EDIT CATATAN                 -->
    <!-- ========================================== -->
    <div x-show="activeModal === 'edit'" 
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal()"></div>

        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full border border-slate-200 overflow-hidden transform transition-all"
                 @click.away="closeModal()">
                
                <div class="bg-gradient-to-r from-amber-800 to-amber-700 text-white px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold flex items-center gap-2">
                        <i class="fa-regular fa-pen-to-square text-amber-300"></i>
                        Edit Catatan Harian
                    </h3>
                    <button @click="closeModal()" class="text-white/70 hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                <form @submit.prevent="submitEdit()" class="p-6 space-y-5">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <div class="sm:col-span-8 space-y-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Judul Catatan <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="form.title" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <div class="sm:col-span-4 space-y-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Tanggal <span class="text-rose-500">*</span></label>
                            <input type="date" x-model="form.journal_date" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pilih Mood <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                            <template x-for="(label, key) in moodOptions" :key="key">
                                <button type="button" 
                                        @click="form.mood = key"
                                        class="py-2 px-3 rounded-xl border text-xs font-semibold flex items-center justify-center gap-1.5 transition-all"
                                        :class="form.mood === key 
                                            ? 'bg-amber-600 text-white border-amber-700 shadow-md ring-2 ring-amber-300' 
                                            : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'">
                                    <span x-text="label"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Isi Catatan <span class="text-rose-500">*</span></label>
                        <textarea x-model="form.content" rows="5" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none leading-relaxed"></textarea>
                    </div>

                    <!-- Existing NAS Photos Management -->
                    <template x-if="activeJournal && activeJournal.photos && activeJournal.photos.length > 0">
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Foto Terlampir di NAS (Klik Hapus untuk menghapus fisik file)</label>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                                <template x-for="photo in activeJournal.photos" :key="photo.id">
                                    <div class="relative rounded-xl overflow-hidden border border-slate-200 bg-slate-100 group">
                                        <img :src="photo.url" class="w-full h-20 object-cover" :class="form.delete_photo_ids.includes(photo.id) ? 'opacity-30 grayscale' : ''">
                                        
                                        <button type="button" 
                                                @click="toggleDeleteExistingPhoto(photo.id)"
                                                class="absolute inset-0 flex items-center justify-center bg-slate-900/40 text-xs font-bold transition-all"
                                                :class="form.delete_photo_ids.includes(photo.id) ? 'bg-rose-900/80 text-rose-200' : 'opacity-0 group-hover:opacity-100 text-white'">
                                            <span x-text="form.delete_photo_ids.includes(photo.id) ? 'Batal Hapus' : 'Hapus Foto'"></span>
                                        </button>
                                        
                                        <template x-if="form.delete_photo_ids.includes(photo.id)">
                                            <div class="absolute top-1 left-1 bg-rose-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow">
                                                Akan Dihapus
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Add New Photos -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Tambah Foto Baru ke NAS</label>
                        <div class="border-2 border-dashed border-slate-300 rounded-xl p-3 bg-slate-50 hover:bg-amber-50/50 hover:border-amber-300 transition-colors text-center cursor-pointer relative"
                             @click="$refs.editFileInput.click()">
                            <input type="file" x-ref="editFileInput" @change="handleFileSelect($event)" multiple accept="image/*" class="hidden">
                            <i class="fa-solid fa-plus text-xl text-amber-600 mb-1"></i>
                            <p class="text-xs font-semibold text-slate-700">Pilih foto tambahan</p>
                        </div>

                        <template x-if="photoPreviews.length > 0">
                            <div class="flex items-center gap-2 overflow-x-auto py-2">
                                <template x-for="(preview, index) in photoPreviews" :key="index">
                                    <div class="relative w-16 h-16 rounded-lg overflow-hidden border border-slate-200 flex-shrink-0 group">
                                        <img :src="preview.url" class="w-full h-full object-cover">
                                        <button type="button" @click="removeNewPhoto(index)" class="absolute top-1 right-1 w-5 h-5 bg-rose-600 text-white rounded-full flex items-center justify-center text-[10px]">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" @click="closeModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold">
                            Batal
                        </button>
                        <button type="submit" :disabled="isSubmitting" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-semibold flex items-center gap-2 shadow-md disabled:opacity-50">
                            <i x-show="isSubmitting" class="fa-solid fa-spinner fa-spin"></i>
                            <span x-text="isSubmitting ? 'Memperbarui NAS...' : 'Perbarui Catatan'"></span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>


    <!-- ========================================== -->
    <!-- CUSTOM MODAL: DETAIL PREVIEW CATATAN       -->
    <!-- ========================================== -->
    <div x-show="activeModal === 'detail'" 
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal()"></div>

        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full border border-slate-200 overflow-hidden transform transition-all"
                 @click.away="closeModal()">
                
                <template x-if="activeJournal">
                    <div>
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-brand-900 to-brand-800 text-white px-6 py-5 flex items-center justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-xs text-brand-200">
                                    <span class="bg-white/10 px-2.5 py-0.5 rounded-full border border-white/10 font-semibold" x-text="activeJournal.journal_date"></span>
                                    <span>•</span>
                                    <span class="bg-brand-500/30 text-white px-2.5 py-0.5 rounded-full border border-brand-300/30 font-semibold" x-text="activeJournal.mood"></span>
                                </div>
                                <h3 class="text-xl font-bold text-white tracking-tight" x-text="activeJournal.title"></h3>
                            </div>
                            <button @click="closeModal()" class="text-white/70 hover:text-white transition-colors">
                                <i class="fa-solid fa-xmark text-xl"></i>
                            </button>
                        </div>

                        <!-- Content Body -->
                        <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                            
                            <!-- Text Content -->
                            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200/80 text-slate-700 text-sm leading-relaxed whitespace-pre-line font-normal">
                                <p x-text="activeJournal.content"></p>
                            </div>

                            <!-- NAS Photo Gallery -->
                            <template x-if="activeJournal.photos && activeJournal.photos.length > 0">
                                <div class="space-y-2">
                                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                        <i class="fa-solid fa-images text-brand-600"></i>
                                        Galeri Foto Lampiran NAS (<span x-text="activeJournal.photos.length"></span> foto)
                                    </h4>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        <template x-for="photo in activeJournal.photos" :key="photo.id">
                                            <div class="group relative aspect-video bg-slate-100 rounded-xl overflow-hidden border border-slate-200 cursor-pointer shadow-sm"
                                                 @click="selectedPhotoForLightbox = photo.url">
                                                <img :src="photo.url" :alt="photo.file_name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                                <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-semibold gap-1">
                                                    <i class="fa-solid fa-magnifying-glass-plus"></i> Lihat Foto
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                        </div>

                        <!-- Footer Actions -->
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button @click="openEditModal(activeJournal)" class="px-4 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-xl text-xs font-bold transition-colors">
                                    <i class="fa-regular fa-pen-to-square"></i> Edit
                                </button>
                                <button @click="openDeleteModal(activeJournal)" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl text-xs font-bold transition-colors">
                                    <i class="fa-regular fa-trash-can"></i> Hapus
                                </button>
                            </div>
                            <button @click="closeModal()" class="px-5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold">
                                Tutup
                            </button>
                        </div>

                    </div>
                </template>

            </div>
        </div>
    </div>


    <!-- ========================================== -->
    <!-- CUSTOM LIGHTBOX PHOTO MODAL                -->
    <!-- ========================================== -->
    <div x-show="selectedPhotoForLightbox" 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click="selectedPhotoForLightbox = null">
        
        <div class="relative max-w-5xl w-full flex flex-col items-center">
            <button @click="selectedPhotoForLightbox = null" class="absolute -top-12 right-0 text-white text-2xl hover:text-brand-300">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <img :src="selectedPhotoForLightbox" class="max-h-[85vh] max-w-full rounded-2xl shadow-2xl border border-white/20 object-contain">
        </div>
    </div>


    <!-- ========================================== -->
    <!-- CUSTOM MODAL: KONFIRMASI HAPUS             -->
    <!-- ========================================== -->
    <div x-show="activeModal === 'delete'" 
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal()"></div>

        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden transform transition-all text-center p-6 space-y-4"
                 @click.away="closeModal()">
                
                <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto text-2xl shadow-inner">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-800">Konfirmasi Hapus Catatan</h3>
                    <p class="text-slate-500 text-sm mt-1">
                        Apakah Anda yakin ingin menghapus catatan <strong class="text-slate-800" x-text="activeJournal ? activeJournal.title : ''"></strong>?
                    </p>
                </div>

                <div class="bg-rose-50 border border-rose-200/80 rounded-xl p-3 text-left text-xs text-rose-800 space-y-1">
                    <div class="font-bold flex items-center gap-1.5 text-rose-900">
                        <i class="fa-solid fa-hard-drive"></i> Peringatan Storage NAS:
                    </div>
                    <p>Tindakan ini akan menghapus permanen record di MySQL serta **seluruh file foto fisik** dari direktori NAS terkait untuk menghindari orphaned files.</p>
                </div>

                <div class="pt-2 flex items-center justify-center gap-3">
                    <button type="button" @click="closeModal()" class="w-1/2 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="submitDelete()" :disabled="isSubmitting" class="w-1/2 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold transition-colors flex items-center justify-center gap-2 shadow-md disabled:opacity-50">
                        <i x-show="isSubmitting" class="fa-solid fa-spinner fa-spin"></i>
                        <span x-text="isSubmitting ? 'Menghapus...' : 'Ya, Hapus'"></span>
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>

<script>
    function journalApp() {
        return {
            activeModal: null,
            activeJournal: null,
            isSubmitting: false,
            selectedPhotoForLightbox: null,
            toast: {
                show: false,
                type: 'success',
                message: ''
            },
            moodOptions: {
                'Happy': '😊 Happy',
                'Neutral': '😐 Neutral',
                'Sad': '😢 Sad',
                'Productive': '⚡ Productive',
                'Excited': '🚀 Excited',
                'Calm': '🌿 Calm'
            },
            form: {
                id: null,
                title: '',
                journal_date: new Date().toISOString().split('T')[0],
                mood: 'Happy',
                content: '',
                photos: [],
                delete_photo_ids: []
            },
            photoPreviews: [],

            initData() {
                @if(session('success'))
                    this.showToast("{{ session('success') }}", 'success');
                @endif
                @if(session('error'))
                    this.showToast("{{ session('error') }}", 'error');
                @endif
            },

            showToast(msg, type = 'success') {
                this.toast.message = msg;
                this.toast.type = type;
                this.toast.show = true;
                setTimeout(() => {
                    this.toast.show = false;
                }, 4000);
            },

            openCreateModal() {
                this.form = {
                    id: null,
                    title: '',
                    journal_date: new Date().toISOString().split('T')[0],
                    mood: 'Happy',
                    content: '',
                    photos: [],
                    delete_photo_ids: []
                };
                this.photoPreviews = [];
                this.activeModal = 'create';
            },

            openEditModal(journal) {
                this.activeJournal = journal;
                this.form = {
                    id: journal.id,
                    title: journal.title,
                    journal_date: journal.journal_date,
                    mood: journal.mood,
                    content: journal.content,
                    photos: [],
                    delete_photo_ids: []
                };
                this.photoPreviews = [];
                this.activeModal = 'edit';
            },

            openDetailModal(journal) {
                this.activeJournal = journal;
                this.activeModal = 'detail';
            },

            openDeleteModal(journal) {
                this.activeJournal = journal;
                this.activeModal = 'delete';
            },

            closeModal() {
                this.activeModal = null;
                this.activeJournal = null;
                this.selectedPhotoForLightbox = null;
                this.photoPreviews = [];
            },

            handleFileSelect(event) {
                const files = Array.from(event.target.files);
                files.forEach(file => {
                    this.form.photos.push(file);
                    this.photoPreviews.push({
                        name: file.name,
                        url: URL.createObjectURL(file)
                    });
                });
            },

            removeNewPhoto(index) {
                this.form.photos.splice(index, 1);
                this.photoPreviews.splice(index, 1);
            },

            toggleDeleteExistingPhoto(photoId) {
                const idx = this.form.delete_photo_ids.indexOf(photoId);
                if (idx > -1) {
                    this.form.delete_photo_ids.splice(idx, 1);
                } else {
                    this.form.delete_photo_ids.push(photoId);
                }
            },

            async submitCreate() {
                this.isSubmitting = true;
                const formData = new FormData();
                formData.append('title', this.form.title);
                formData.append('journal_date', this.form.journal_date);
                formData.append('mood', this.form.mood);
                formData.append('content', this.form.content);
                
                this.form.photos.forEach(file => {
                    formData.append('photos[]', file);
                });

                try {
                    const response = await fetch("{{ route('journals.store') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const resData = await response.json();
                    if (resData.success) {
                        this.closeModal();
                        this.showToast(resData.message, 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        this.showToast(resData.message || 'Gagal menyimpan catatan.', 'error');
                    }
                } catch (err) {
                    this.showToast('Terjadi kesalahan koneksi.', 'error');
                } finally {
                    this.isSubmitting = false;
                }
            },

            async submitEdit() {
                this.isSubmitting = true;
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('title', this.form.title);
                formData.append('journal_date', this.form.journal_date);
                formData.append('mood', this.form.mood);
                formData.append('content', this.form.content);

                this.form.delete_photo_ids.forEach(id => {
                    formData.append('delete_photo_ids[]', id);
                });

                this.form.photos.forEach(file => {
                    formData.append('photos[]', file);
                });

                try {
                    const editUrl = `{{ url('/journals') }}/${this.form.id}`;
                    const response = await fetch(editUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const resData = await response.json();
                    if (resData.success) {
                        this.closeModal();
                        this.showToast(resData.message, 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        this.showToast(resData.message || 'Gagal memperbarui catatan.', 'error');
                    }
                } catch (err) {
                    this.showToast('Terjadi kesalahan koneksi.', 'error');
                } finally {
                    this.isSubmitting = false;
                }
            },

            async submitDelete() {
                if (!this.activeJournal) return;
                this.isSubmitting = true;

                try {
                    const deleteUrl = `{{ url('/journals') }}/${this.activeJournal.id}`;
                    const response = await fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });

                    const resData = await response.json();
                    if (resData.success) {
                        this.closeModal();
                        this.showToast(resData.message, 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        this.showToast(resData.message || 'Gagal menghapus catatan.', 'error');
                    }
                } catch (err) {
                    this.showToast('Terjadi kesalahan koneksi.', 'error');
                } finally {
                    this.isSubmitting = false;
                }
            }
        }
    }
</script>
@endsection
