<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\TeamMemberResource\Pages\EditTeamMember;
use App\Filament\Resources\WeddingGalleryResource\Pages\EditWeddingGallery;
use App\Filament\Widgets\ActivityLogWidget;
use App\Models\ActivityLog;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WeddingGallery;
use App\Models\WeddingGalleryPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

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
}
