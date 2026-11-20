<?php

namespace App\Observers;

use App\Models;
use App\Support\AdminDeletionEffects;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class AdminActivityObserver
{
    public function __construct(private AdminDeletionEffects $deletions) {}

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
        Models\FinanceAccount::class => 'Финансова сметка',
        Models\FinanceMovement::class => 'Финансово движение',
        Models\FinanceProject::class => 'Финансов проект',
        Models\Faq::class => 'Често задаван въпрос',
        Models\GameEvent::class => 'Запис от играта',
        Models\GameSticker::class => 'QR стикер',
        Models\GraduationPackage::class => 'Пакет за предбална фотосесия',
        Models\GraduationPortfolioPhoto::class => 'Снимка от предбална фотосесия',
        Models\Inquiry::class => 'Запитване',
        Models\LegalPage::class => 'Правна страница',
        Models\NavigationItem::class => 'Елемент от менюто',
        Models\Order::class => 'Поръчка',
        Models\OrderShoot::class => 'Заснемане по поръчка',
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
        if ($model instanceof Models\TeamAssignment) {
            $this->assignment($model, true);

            return;
        }

        $this->record($model, 'created', 'Създаден е запис');
    }

    public function updated(Model $model): void
    {
        $changes = [];
        foreach (Arr::except($model->getChanges(), [$model->getUpdatedAtColumn()]) as $field => $value) {
            $changes[$field] = ['old' => $model->getRawOriginal($field), 'new' => $value];
        }

        if ($model instanceof Models\FinanceMovement && isset($changes['voided_at']['new'])) {
            $description = $model->kind === Models\FinanceMovement::EXPENSE && $model->project?->isUtility()
                ? 'Изтрита е битова сметка' : 'Анулирано е финансово движение';
            $this->changes($model, $changes, $description);

            return;
        }

        if ($model instanceof Models\OrderShoot && isset($changes['shot_on'])) {
            $this->changes($model, $changes, $model->shot_on
                ? 'Заснемането е отбелязано като заснето'
                : 'Заснемането е върнато като оставащо');

            return;
        }

        $this->changes($model, $changes);
    }

    public function deleting(Model $model): void
    {
        if (self::actor()) {
            $this->deletions->capture($model);
        }
    }

    public function deleted(Model $model): void
    {
        if ($model instanceof Models\TeamAssignment) {
            $this->assignment($model, false);

            return;
        }

        foreach ($this->deletions->afterDelete($model) as [$related, $deleted, $changes]) {
            if ($deleted && $related instanceof Models\TeamAssignment) {
                $this->assignment($related, false);
            } elseif ($deleted) {
                $this->record($related, 'deleted', 'Изтрит е свързан запис');
            } else {
                $this->changes($related, $changes, 'Премахната е връзка след изтриване');
            }
        }

        $this->record($model, 'deleted', 'Изтрит е запис');
    }

    public static function actor(): ?Models\User
    {
        $user = Auth::user();

        return $user instanceof Models\User && $user->isAdmin() ? $user : null;
    }

    /** @param array<string, array{old: mixed, new: mixed}> $changes */
    public function changes(Model $model, array $changes, string $description = 'Редактиран е запис'): void
    {
        if ($changes !== []) {
            $this->record($model, 'updated', $description, $changes);
        }
    }

    private function assignment(Models\TeamAssignment $assignment, bool $assigned): void
    {
        $booking = $assignment->getTable() === 'booking_team_member';
        $subject = $booking ? new Models\Booking : new Models\Order;
        $subject->setAttribute($subject->getKeyName(), $booking ? $assignment->booking_id : $assignment->order_id);
        $member = (int) $assignment->team_member_id;

        $this->changes($subject, [
            'team_member_id' => ['old' => $assigned ? null : $member, 'new' => $assigned ? $member : null],
        ], $assigned ? 'Назначен е член на екипа' : 'Премахнат е член на екипа');
    }

    private function record(Model $model, string $action, string $description, ?array $changes = null): void
    {
        $user = self::actor();
        $type = $model::class;
        if (! $user || ! isset(self::SUBJECTS[$type])) {
            return;
        }

        $private = in_array($type, [
            Models\Booking::class, Models\Order::class, Models\OrderShoot::class, Models\Inquiry::class, Models\GameEvent::class,
        ], true);

        if ($action === 'updated') {
            foreach (array_keys($changes) as $field) {
                $redact = in_array($field, ['address', 'egn', 'email', 'phone', 'setting_value', 'void_reason'], true)
                    || ($private && ! in_array($field, ['status', 'kind', 'event_date', 'end_date', 'start_time', 'end_time', 'team_member_id', 'order_id', 'redeemed_at', 'booking_id', 'shot_on', 'shot_by_id'], true));

                if ($redact) {
                    $changes[$field] = ['old' => 'Скрита стойност', 'new' => 'Скрита стойност'];
                }
            }
        }

        $name = $private ? null : ($model->name ?? $model->name_bg ?? $model->title ?? $model->label_bg ?? $model->question
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
