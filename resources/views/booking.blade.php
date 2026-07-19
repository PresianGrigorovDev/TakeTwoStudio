@extends('layouts.app')

@section('title', 'Резервирай Дата | Take Two Studio 1603')
@section('meta_description', 'Резервирайте дата за вашата фотосесия или видеозаснемане с Take Two Studio 1603 във Варна. Изберете свободна дата от нашия календар.')
@section('meta_keywords', 'резервация фотограф варна, запази дата сватба, резервация фотосесия, Take Two Studio')

@push('styles')
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/booking.css') }}">
@endpush

@section('content')
    <!-- HERO -->
    <section class="booking-hero">
        <div class="hero-overlay"></div>
        <div class="hero-title" data-aos="fade-up">
            <h1>Резервирай Дата</h1>
            <p>Изберете свободна дата за вашето събитие</p>
        </div>
    </section>

    <!-- CALENDAR -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @if(session('success'))
                        <div class="alert alert-success text-center mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- Calendar -->
                    <div class="booking-calendar-wrapper" data-aos="fade-up">
                        <div class="cal-header">
                            <button type="button" class="cal-nav" id="calPrev" aria-label="Предишен месец">&larr;</button>
                            <h3 class="cal-month-title" id="calTitle"></h3>
                            <button type="button" class="cal-nav" id="calNext" aria-label="Следващ месец">&rarr;</button>
                        </div>
                        <div class="cal-weekdays">
                            <div>Пон</div>
                            <div>Вто</div>
                            <div>Сря</div>
                            <div>Чет</div>
                            <div>Пет</div>
                            <div>Съб</div>
                            <div>Нед</div>
                        </div>
                        <div class="cal-grid" id="calGrid"></div>
                        <div class="cal-legend">
                            <span><i class="leg-dot available"></i> Свободна</span>
                            <span><i class="leg-dot partial"></i> Частично заета</span>
                            <span><i class="leg-dot unavailable"></i> Заета</span>
                        </div>
                    </div>

                    <!-- Booking Form (hidden until date selected, or shown again if a previous submit had errors) -->
                    <div class="booking-form-wrapper" id="bookingFormWrapper" style="{{ $errors->any() ? '' : 'display:none' }}" data-aos="fade-up">
                        <h3 class="text-center mb-4">Заявка за резервация</h3>

                        @if($errors->any() && (! old('event_date') || ! old('start_time') || ! old('end_time')))
                            <div class="alert alert-warning text-center">
                                Моля, изберете отново дата и часове от календара по-горе.
                            </div>
                        @endif

                        <div class="selected-date-display text-center mb-4">
                            Избрана дата: <strong id="selectedDateDisplay">{{ old('event_date') }}</strong>
                        </div>

                        <form action="/submit-booking" method="POST" id="bookingForm">
                            @csrf
                            <input type="hidden" name="event_date" id="eventDateInput" value="{{ old('event_date') }}">
                            <input type="hidden" name="start_time" id="startTimeInput" value="{{ old('start_time') }}">
                            <input type="hidden" name="end_time" id="endTimeInput" value="{{ old('end_time') }}">

                            <!-- Time Slots -->
                            <div class="mb-4">
                                <label class="form-label">Изберете часове *</label>
                                <div id="timeSlotsWrapper">
                                    <p class="text-muted text-center">Изберете дата от календара</p>
                                </div>
                                <div id="selectedTimeDisplay" class="selected-time-display" style="display:none"></div>
                                <small class="text-muted">Кликнете отново върху часовия диапазон за да изберете наново</small>
                            </div>

                            <div class="mb-3">
                                <label for="service_type" class="form-label">Тип услуга *</label>
                                <select name="service_type" id="service_type" class="form-select" required>
                                    <option value="">-- Изберете --</option>
                                    <option value="Сватба">Сватба</option>
                                    <option value="Абитуриентски Бал">Абитуриентски Бал</option>
                                    <option value="Свето Кръщене">Свето Кръщене</option>
                                    <option value="Изпращане">Изпращане</option>
                                    <option value="Реклама и Бизнес">Реклама и Бизнес</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Вашето име *</label>
                                    <input type="text" name="name" id="name" class="form-control" required value="{{ old('name') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Телефон *</label>
                                    <input type="tel" name="phone" id="phone" class="form-control" required value="{{ old('phone') }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Имейл (незадължителен)</label>
                                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}">
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label">Съобщение (незадължително)</label>
                                <textarea name="message" id="message" class="form-control" rows="3">{{ old('message') }}</textarea>
                            </div>

                            @include('partials.gdpr-consent', ['consentId' => 'booking'])

                            @if($errors->any())
                                <div class="alert alert-danger">
                                    @foreach($errors->all() as $error)
                                        <p class="mb-0">{{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif

                            <div class="text-center mt-4">
                                <button type="submit" class="btn-custom-full">Изпрати Заявка</button>
                            </div>
                        </form>
                    </div>

                    <!-- Info -->
                    <div class="booking-info text-center mt-4" data-aos="fade-up">
                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Резервациите подлежат на потвърждение от нашия екип. Ще се свържем с вас в рамките на 24 часа.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/booking-calendar.js') }}" defer></script>
    @if(request('service'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var map = {weddings:'Сватба', proms:'Абитуриентски Бал', baptism:'Свето Кръщене', graduation:'Изпращане', commercial:'Реклама и Бизнес'};
            var val = map['{{ request("service") }}'];
            if (val) document.getElementById('service_type').value = val;
        });
    </script>
    @endif
@endpush
