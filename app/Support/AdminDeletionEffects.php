<?php

namespace App\Support;

use App\Models;
use Illuminate\Database\Eloquent\Model;
use WeakMap;

/** Database cascades and SET NULL changes do not fire Eloquent events. */
final class AdminDeletionEffects
{
    private const REFERENCES = [
        Models\ArchitecturalGallery::class => [[Models\ArchitecturalGalleryPhoto::class, 'architectural_gallery_id']],
        Models\AutomotiveGallery::class => [[Models\AutomotiveGalleryPhoto::class, 'automotive_gallery_id']],
        Models\BaptismGallery::class => [[Models\BaptismGalleryPhoto::class, 'baptism_gallery_id']],
        Models\BlogCategory::class => [[Models\BlogPost::class, 'category_id']],
        Models\Booking::class => [
            [Models\TeamAssignment::class, 'booking_id', 'booking_team_member'],
            [Models\FinanceProject::class, 'booking_id'],
        ],
        Models\EventGallery::class => [[Models\EventGalleryPhoto::class, 'event_gallery_id']],
        Models\FamilyGallery::class => [[Models\FamilyGalleryPhoto::class, 'family_gallery_id']],
        Models\NavigationItem::class => [[Models\NavigationItem::class, 'parent_id']],
        Models\Order::class => [
            [Models\Booking::class, 'order_id'],
            [Models\TeamAssignment::class, 'order_id', 'order_team_member'],
            [Models\FinanceProject::class, 'order_id'],
        ],
        Models\PortfolioCategory::class => [[Models\PortfolioItem::class, 'category_id']],
        Models\PortraitGallery::class => [[Models\PortraitGalleryPhoto::class, 'portrait_gallery_id']],
        Models\PromoCode::class => [[Models\Promotion::class, 'promo_code_id']],
        Models\Service::class => [
            [Models\ServicePackage::class, 'service_id'],
            [Models\ServiceExtra::class, 'service_id'],
            [Models\ServicePromotion::class, 'service_id'],
        ],
        Models\ServicePackage::class => [[Models\ServicePromotion::class, 'service_package_id']],
        Models\TeamMember::class => [
            [Models\BlogPost::class, 'author_team_member_id'],
            [Models\FinanceAccount::class, 'team_member_id'],
            [Models\TeamAssignment::class, 'team_member_id', 'booking_team_member'],
            [Models\TeamAssignment::class, 'team_member_id', 'order_team_member'],
        ],
        Models\WeddingGallery::class => [[Models\WeddingGalleryPhoto::class, 'wedding_gallery_id']],
    ];

    private WeakMap $pending;

    public function __construct()
    {
        $this->pending = new WeakMap;
    }

    public function capture(Model $model): void
    {
        if (! isset(self::REFERENCES[$model::class])) {
            return;
        }

        $seen = [$model->getTable() => [$model->getKey() => true]];
        $records = [];
        $model->newQuery()->whereKey($model->getKey())->lockForUpdate()->first([$model->getKeyName()]);
        $this->collect($model, $records, $seen);
        $this->pending[$model] = $records;
    }

    /** Compare actual results so a missing foreign key cannot create a false audit entry. */
    public function afterDelete(Model $model): iterable
    {
        $records = $this->pending[$model] ?? [];
        unset($this->pending[$model]);

        foreach ($records as $group) {
            $example = reset($group)['model'];
            $remaining = $example->newQuery()->whereKey(array_keys($group))->get()->keyBy($example->getKeyName());

            foreach ($group as $id => ['model' => $before, 'fields' => $fields]) {
                $after = $remaining->get($id);
                if (! $after) {
                    yield [$before, true, []];

                    continue;
                }

                $changes = [];
                foreach (array_keys($fields) as $field) {
                    if ($after->getRawOriginal($field) === null && $before->getRawOriginal($field) !== null) {
                        $changes[$field] = ['old' => $before->getRawOriginal($field), 'new' => null];
                    }
                }

                if ($changes !== []) {
                    yield [$before, false, $changes];
                }
            }
        }
    }

    private function collect(Model $parent, array &$records, array &$seen): void
    {
        foreach (self::REFERENCES[$parent::class] ?? [] as $reference) {
            [$class, $foreignKey] = $reference;
            $related = new $class;
            if (in_array($class, [Models\FinanceAccount::class, Models\FinanceProject::class], true) && ! Models\FinanceProject::installed()) {
                continue;
            }
            $table = $reference[2] ?? $related->getTable();
            $related->setTable($table);

            $rows = $related->newQuery()->where($foreignKey, $parent->getKey())
                ->orderBy($related->getKeyName())->lockForUpdate()->get();

            foreach ($rows as $row) {
                $id = $row->getKey();
                if (! isset($seen[$table][$id])) {
                    $seen[$table][$id] = true;
                    $records[$table][$id] = ['model' => $row, 'fields' => []];
                    $this->collect($row, $records, $seen);
                }

                if (isset($records[$table][$id])) {
                    $records[$table][$id]['fields'][$foreignKey] = true;
                }
            }
        }
    }
}
