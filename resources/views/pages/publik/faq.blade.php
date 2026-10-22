<?php

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::publik'), Title('FAQ')] class extends Component {
    /**
     * @return Collection<int, Faq>
     */
    #[Computed]
    public function faqs(): Collection
    {
        return Faq::query()->published()->get(['id', 'pertanyaan', 'jawaban']);
    }
}; ?>

<div>
    <x-page-header title="FAQ" icon="question-mark-circle" subtitle="Pertanyaan yang sering diajukan seputar layanan Jariyah Batang Hari."
        :breadcrumb="[['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'FAQ']]" />

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-3 lg:px-8">
        <section class="lg:col-span-2" aria-label="Daftar pertanyaan">
            @if ($this->faqs->isEmpty())
                <div class="kartu">
                    <x-empty-state icon="question-mark-circle" title="Belum ada pertanyaan" description="Daftar pertanyaan akan segera ditambahkan." />
                </div>
            @else
                <div x-data="{ buka: {{ $this->faqs->first()->id }} }" class="space-y-3">
                    @foreach ($this->faqs as $faq)
                        <div wire:key="faq-{{ $faq->id }}" class="kartu overflow-hidden"
                            x-bind:class="buka === {{ $faq->id }} && 'border-primary-300'">
                            <h2>
                                <button type="button" id="faq-tombol-{{ $faq->id }}"
                                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-start font-semibold text-primary-900 hover:bg-primary-50/60 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary-700"
                                    x-on:click="buka = buka === {{ $faq->id }} ? null : {{ $faq->id }}"
                                    x-bind:aria-expanded="(buka === {{ $faq->id }}).toString()"
                                    aria-controls="faq-jawaban-{{ $faq->id }}">
                                    <span>{{ $faq->pertanyaan }}</span>
                                    <flux:icon.chevron-down class="size-5 shrink-0 text-primary-700 transition-transform"
                                        x-bind:class="buka === {{ $faq->id }} && 'rotate-180'" />
                                </button>
                            </h2>
                            <div id="faq-jawaban-{{ $faq->id }}" role="region" aria-labelledby="faq-tombol-{{ $faq->id }}"
                                x-show="buka === {{ $faq->id }}" x-collapse @unless ($loop->first) x-cloak @endunless>
                                <p class="px-5 pb-5 text-sm leading-relaxed text-zinc-600">{{ $faq->jawaban }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <aside>
            <div class="kartu relative overflow-hidden bg-primary-50 p-6 lg:sticky lg:top-24">
                <x-ornamen-daun class="pointer-events-none absolute -bottom-4 -end-3 size-24 text-primary-200" />
                <x-ikon-kotak icon="chat-bubble-left-right" />
                <h2 class="mt-4 text-lg font-semibold text-primary-900">Masih punya pertanyaan?</h2>
                <p class="mt-2 text-sm text-zinc-600">Tim kami siap membantu. Kirimkan pertanyaan Anda melalui halaman kontak.</p>
                <a href="{{ route('kontak') }}" wire:navigate class="btn-primary relative mt-5">
                    Hubungi Kami <flux:icon.arrow-right class="size-4" />
                </a>
            </div>
        </aside>
    </div>
</div>
