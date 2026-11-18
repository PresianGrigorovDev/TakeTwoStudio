<?php

namespace App\Observers;

use App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class AdminActivityObserver
{
    public const SUBJECTS = [
        Models\ArchitecturalGallery::class => 'Архитектурна галерия',
        Models\ArchitecturalGalleryPhoto::class => 'Снимка в архитектурна галерия',
        Models\ArchitecturalPackage::class => 'Пакет за архитектурна фотография',
        Models\AutomotiveGallery::class => 'Автомобилна галерия',
        Models\AutomotiveGalleryPhoto::class => 'Снимка в автомобилна галерия',
        Models\AutomotivePackage::class => 'Пакет за автомобилна фотография',
        Models\BaptismGallery::class => 'Галерия за кръщене',
        Models\BaptismGalleryPhoto::class => 'Снимка в галерия за кръщене',
        Models\BlogCategory::class => 'Категория в блога',
        Models\BlogPost::class => 'Публикация',
        Models\Booking::class => 'Ангажимент',
        Models\CommercialPackage::class => 'Бизнес пакет',
        Models\CommercialPortfolioPhoto::class => 'Снимка в бизнес портфолиото',
        Models\EventGallery::class => 'Галерия за събитие',
        Models\EventGalleryPhoto::class => 'Снимка в галерия за събитие',
        Models\EventPackage::class => 'Пакет за събитие',
        Models\EventPortfolioPhoto::class => 'Снимка в портфолиото за събития',
        Models\FamilyGallery::class => 'Семейна галерия',
        Models\FamilyGalleryPhoto::class => 'Снимка в семейна галерия',
        Models\FamilyPackage::class => 'Пакет за семейна фотография',
        Models\Faq::class => 'Често задаван въпрос',
        Models\GameEvent::class => 'Запис от играта',
        Models\GameSticker::class => 'QR стикер',
        Models\GraduationPackage::class => 'Пакет за предбална фотосесия',
        Models\GraduationPortfolioPhoto::class => 'Снимка от предбална фотосесия',
        Models\Inquiry::class => 'Запитване',
        Models\LegalPage::class => 'Правна страница',
        Models\NavigationItem::class => 'Елемент от менюто',
        Models\Order::class => 'Поръчка',
        Models\PageContent::class => 'Съдържание на страница',
        Models\Partner::class => 'Партньор',
        Models\PortfolioCategory::class => 'Категория в портфолиото',
        Models\PortfolioItem::class => 'Запис в портфолиото',
        Models\PortraitGallery::class => 'Портретна галерия',
        Models\PortraitGalleryPhoto::class => 'Снимка в портретна галерия',
        Models\PortraitPackage::class => 'Пакет за портретна фотография',
        Models\PortraitPortfolioPhoto::class => 'Снимка в портретното портфолио',
        Models\PromPackage::class => 'Пакет за бал',
        Models\PromPortfolioPhoto::class => 'Снимка от бал',
        Models\PromoCode::class => 'Промо код',
        Models\Promotion::class => 'Промоция',
        Models\ReasonToChoose::class => 'Предимство на услуга',
        Models\Service::class => 'Услуга',
        Models\ServiceExtra::class => 'Допълнение към услуга',
        Models\ServicePackage::class => 'Пакет за услуга',
        Models\ServicePromotion::class => 'Промоция на услуга',
        Models\SiteSetting::class => 'Настройка на сайта',
        Models\SocialMedia::class => 'Социална мрежа',
        Models\TeamMember::class => 'Член на екипа',
        Models\Testimonial::class => 'Отзив',
        Models\WeddingGallery::class => 'Сватбена галерия',
        Models\WeddingGalleryPhoto::class => 'Снимка в сватбена галерия',
    ];

    public function created(Model $model): void
    {
        $this->record($model, 'created', 'Създаден е запис');
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated', 'Редактиран е запис');
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', 'Изтрит е запис');
    }

    private function record(Model $model, string $action, string $description): void
    {
        $user = Auth::user();
        if (! $user instanceof Models\User || ! $user->isAdmin()) {
            return;
        }

        $type = $model::class;
        $private = in_array($type, [
            Models\Booking::class, Models\Order::class, Models\Inquiry::class, Models\GameEvent::class,
        ], true);

        $changes = null;
        if ($action === 'updated') {
            $changes = [];
            foreach (Arr::except($model->getChanges(), [$model->getUpdatedAtColumn()]) as $field => $value) {
                $redact = in_array($field, ['address', 'egn', 'email', 'phone', 'setting_value'], true)
                    || ($private && ! in_array($field, ['status', 'kind', 'event_date', 'end_date', 'start_time', 'end_time'], true));

                $changes[$field] = [
                    'old' => $redact ? 'Скрита стойност' : $model->getRawOriginal($field),
                    'new' => $redact ? 'Скрита стойност' : $value,
                ];
            }

            if ($changes === []) {
                return;
            }
        }

        $name = $private ? null : ($model->name ?? $model->name_bg ?? $model->title ?? $model->question
            ?? $model->client_name_bg ?? $model->client_name ?? $model->setting_key ?? $model->alt_text);
        $subject = self::SUBJECTS[$type].($name ? ' „'.$name.'“' : ' #'.$model->getKey());

        Models\ActivityLog::create([
            'user_id' => $user->getKey(),
            'user_name' => $user->name,
            'action' => $action,
            'subject_type' => $type,
            'subject_id' => $model->getKey(),
            'description' => $description.': '.$subject,
            'properties' => $changes,
        ]);
    }
}
