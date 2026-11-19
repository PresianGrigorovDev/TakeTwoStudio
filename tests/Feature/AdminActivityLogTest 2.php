<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\BlogCategoryResource\Pages\EditBlogCategory;
use App\Filament\Resources\BlogCategoryResource\Pages\ListBlogCategories;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\TeamMemberResource\Pages\EditTeamMember;
use App\Filament\Resources\WeddingGalleryResource\Pages\EditWeddingGallery;
use App\Filament\Resources\WeddingGalleryResource\Pages\ListWeddingGalleries;
use App\Filament\Widgets\ActivityLogWidget;
use App\Models\ActivityLog;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Booking;
use App\Models\Order;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WeddingGallery;
use App\Models\WeddingGalleryPhoto;
use App\Observers\AdminActivityObserver;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_admin_resource_that_can_change_records_has_an_audit_subject(): void
    {
        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            if ($resource !== ActivityLogResource::class) {
                $this->assertArrayHasKey($resource::getModel(), AdminActivityObserver::SUBJECTS, $resource);
            }
        }
    }

    public function test_a_gallery_photo_replacement_is_visible_to_another_admin(): void
    {
        Storage::fake('public');
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('wedding_galleries/photos', 'public');
        $gallery = WeddingGallery::create([
            'title' => 'Сватбена галерия',
            'slug' => 'wedding-test',
            'cover_image' => $oldPath,
            'is_active' => true,
        ]);
        $photo = $gallery->photos()->create(['image_path' => $oldPath, 'sort_order' => 1]);
        $author = User::factory()->create(['name' => 'Симеон', 'is_admin' => true]);

        $this->actingAs($author);

        Livewire::test(EditWeddingGallery::class, ['record' => $gallery->id])
            ->set('data.photos.record-'.$photo->id.'.image_path', [UploadedFile::fake()->image('new.jpg')])
            ->call('save')
            ->assertHasNoFormErrors();

        $newPath = $photo->refresh()->image_path;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertExists($newPath);

        $entry = ActivityLog::where('subject_type', WeddingGalleryPhoto::class)->sole();
        $this->assertEquals($photo->id, $entry->subject_id);
        $this->assertSame($author->id, $entry->user_id);
        $this->assertSame('Симеон', $entry->user_name);
        $this->assertSame('updated', $entry->action);
        $this->assertSame(['old' => $oldPath, 'new' => $newPath], $entry->properties['image_path']);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ActivityLogWidget::class)->assertCanSeeTableRecords([$entry]);
        Livewire::test(ListActivityLogs::class)
            ->assertCanSeeTableRecords([$entry])
            ->mountTableAction('view', $entry)
            ->assertSee($oldPath)
            ->assertSee($newPath);
    }

    public function test_team_edits_keep_private_values_out_of_the_log(): void
    {
        $member = TeamMember::create(['name' => 'Фотограф', 'role_bg' => 'Фотограф', 'email' => 'old@example.test']);
        $this->assertDatabaseCount('activity_logs', 0);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(EditTeamMember::class, ['record' => $member->id])
            ->fillForm(['email' => 'new@example.test', 'bio_bg' => '<strong>Обновена биография</strong>'])
            ->call('save')
            ->assertHasNoFormErrors();

        $entry = ActivityLog::sole();
        $this->assertSame(['old' => 'Скрита стойност', 'new' => 'Скрита стойност'], $entry->properties['email']);

        Livewire::test(ListActivityLogs::class)
            ->mountTableAction('view', $entry)
            ->assertSee('<strong>Обновена биография</strong>')
            ->assertDontSeeHtml('<strong>Обновена биография</strong>');
    }

    public function test_deleting_a_gallery_from_the_admin_also_records_its_deleted_photos(): void
    {
        $gallery = WeddingGallery::create(['title' => 'Сватбена галерия', 'slug' => 'cascade-test', 'cover_image' => 'wedding/cover.jpg']);
        $photo = $gallery->photos()->create(['image_path' => 'wedding/photo.jpg']);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        Livewire::test(ListWeddingGalleries::class)->callTableBulkAction('delete', [$gallery]);

        $this->assertModelMissing($gallery);
        $this->assertModelMissing($photo);
        $this->assertDatabaseCount('activity_logs', 2);
        foreach ([$photo, $gallery] as $record) {
            $this->assertDatabaseHas('activity_logs', [
                'user_id' => $admin->id, 'action' => 'deleted', 'subject_type' => $record::class, 'subject_id' => $record->id,
            ]);
        }
    }

    public function test_an_inline_toggle_and_removing_a_blog_category_both_record_changed_values(): void
    {
        $category = BlogCategory::create(['name' => 'Примерна категория', 'is_visible' => true]);
        $post = BlogPost::create([
            'title' => 'Примерна публикация', 'excerpt' => 'Резюме', 'body' => 'Текст',
            'cover_image' => 'blog/cover.jpg', 'category_id' => $category->id,
        ]);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        Livewire::test(ListBlogCategories::class)->call('updateTableColumnState', 'is_visible', (string) $category->id, false);
        $this->assertFalse($category->refresh()->is_visible);
        $entry = ActivityLog::sole();
        $this->assertSame($admin->id, $entry->user_id);
        $this->assertEquals(['old' => 1, 'new' => 0], $entry->properties['is_visible']);

        Livewire::test(EditBlogCategory::class, ['record' => $category->id])->callAction('delete');

        $this->assertNull($post->refresh()->category_id);
        $entry = ActivityLog::where('subject_type', BlogPost::class)->sole();
        $this->assertSame('updated', $entry->action);
        $this->assertSame(['old' => $category->id, 'new' => null], $entry->properties['category_id']);
    }

    public function test_deleting_an_order_records_its_calendar_entries_and_removed_assignments(): void
    {
        $order = Order::create(['name' => 'Клиент', 'email' => 'client@example.test', 'phone' => '0888123456', 'service_type' => 'Сватба']);
        $booking = $order->bookings()->create([
            'name' => 'Сватба', 'event_date' => '2031-06-15', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);
        $member = TeamMember::create(['name' => 'Член на екипа', 'role_bg' => 'Фотограф']);
        $order->teamMembers()->attach($member);
        $booking->teamMembers()->attach($member);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        Livewire::test(EditOrder::class, ['record' => $order->id])->callAction('delete');

        $this->assertModelMissing($order);
        $this->assertModelMissing($booking);
        $this->assertDatabaseCount('activity_logs', 4);
        foreach ([Order::class, Booking::class] as $type) {
            $entry = ActivityLog::where('subject_type', $type)->where('action', 'updated')->sole();
            $this->assertSame($admin->id, $entry->user_id);
            $this->assertSame(['old' => $member->id, 'new' => null], $entry->properties['team_member_id']);
        }
    }

    public function test_a_failed_journal_write_rolls_back_an_inline_edit(): void
    {
        $category = BlogCategory::create(['name' => 'Примерна категория', 'is_visible' => true]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->withoutExceptionHandling();

        $event = 'eloquent.creating: '.ActivityLog::class;
        Event::listen($event, fn () => throw new RuntimeException('Simulated journal write failure'));

        try {
            Livewire::test(ListBlogCategories::class)->call('updateTableColumnState', 'is_visible', (string) $category->id, false);
            $this->fail('Expected an unsuccessful journal write.');
        } catch (RuntimeException $error) {
            $this->assertSame('Simulated journal write failure', $error->getMessage());
        } finally {
            Event::forget($event);
        }

        $this->assertTrue($category->refresh()->is_visible);
        $this->assertDatabaseCount('activity_logs', 0);
    }
}
