<?php

namespace Tests\Feature;

use App\Filament\Resources\PartnerResource\Pages\ListPartners;
use App\Livewire\ServiceHubTable;
use App\Models\ActivityLog;
use App\Models\Partner;
use App\Models\Service;
use App\Models\ServiceExtra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTableReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_dragging_a_partner_to_a_new_position_logs_only_changed_rows(): void
    {
        $first = Partner::create(['name' => 'Студио А', 'logo_path' => 'partners/a.png', 'display_order' => 1]);
        $second = Partner::create(['name' => 'Студио Б', 'logo_path' => 'partners/b.png', 'display_order' => 2]);
        $admin = User::factory()->create(['name' => 'Симеон', 'is_admin' => true]);
        $this->actingAs($admin);

        Livewire::test(ListPartners::class)
            ->call('toggleTableReordering')
            ->call('reorderTable', [(string) $second->id, (string) $first->id])
            ->call('reorderTable', [(string) $second->id, (string) $first->id]);

        $this->assertSame(2, $first->refresh()->display_order);
        $this->assertSame(1, $second->refresh()->display_order);
        $entries = ActivityLog::where('subject_type', Partner::class)->get()->keyBy('subject_id');
        $this->assertDatabaseCount('activity_logs', 2);
        $this->assertSame($admin->id, $entries[$first->id]->user_id);
        $this->assertSame('Симеон', $entries[$first->id]->user_name);
        $this->assertStringContainsString('Променена е подредбата', $entries[$first->id]->description);
        $this->assertSame(['old' => 1, 'new' => 2], $entries[$first->id]->properties['display_order']);
        $this->assertSame(['old' => 2, 'new' => 1], $entries[$second->id]->properties['display_order']);
    }

    public function test_dragging_a_service_extra_keeps_the_update_and_log_in_that_service(): void
    {
        $proms = Service::firstOrCreate(['slug' => 'proms'], ['name_bg' => 'Балове']);
        $events = Service::firstOrCreate(['slug' => 'events'], ['name_bg' => 'Събития']);
        $outside = ServiceExtra::create(['service_id' => $events->id, 'label_bg' => 'Час за събитие', 'price_eur' => 100, 'display_order' => 8]);
        $inside = ServiceExtra::create(['service_id' => $proms->id, 'label_bg' => 'Час за бал', 'price_eur' => 150, 'display_order' => 6]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ServiceHubTable::class, ['serviceId' => $proms->id, 'section' => 'extras'])
            ->call('toggleTableReordering')
            ->call('reorderTable', [$inside->id, $outside->id]);

        $this->assertSame(1, $inside->refresh()->display_order);
        $this->assertSame(8, $outside->refresh()->display_order);
        $entry = ActivityLog::sole();
        $this->assertSame(ServiceExtra::class, $entry->subject_type);
        $this->assertEquals($inside->id, $entry->subject_id);
        $this->assertSame(['old' => 6, 'new' => 1], $entry->properties['display_order']);
    }
}
