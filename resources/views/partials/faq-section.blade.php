@if(isset($faqs) && count($faqs))
    <!-- FAQ -->
    <section class="py-5 px-1 bg-white" id="faq">
        <div class="container">
            <h2 class="text-center mb-5">{{ $title ?? 'Често Задавани Въпроси' }}</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        @foreach($faqs as $i => $faq)
                            <div class="accordion-item">
                                <h3 class="accordion-header">
                                    <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq-{{ $faq->id }}"
                                        aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="faq-{{ $faq->id }}">
                                        {{ $faq->question }}
                                    </button>
                                </h3>
                                <div id="faq-{{ $faq->id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body text-muted">
                                        {{ $faq->answer }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif