<?php

namespace Tests\Feature;

use App\Models\FamilyMember;
use App\Models\FamilyNotification;
use App\Models\MemoryPost;
use App\Models\MemoryPostComment;
use App\Models\SamuelProfile;
use App\Support\AppTheme;
use App\Support\FamilySession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SamuelSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function adminMember(): FamilyMember
    {
        $member = new FamilyMember([
            'cedula' => '1007846945',
            'name' => 'Admin prueba',
            'avatar_emoji' => '💛',
            'is_admin' => true,
            'is_active' => true,
        ]);
        $member->setPin('6945');
        $member->save();

        return $member;
    }

    private function extraMember(): FamilyMember
    {
        $member = new FamilyMember([
            'cedula' => '1007846946',
            'name' => 'Tío prueba',
            'avatar_emoji' => '🙂',
            'is_admin' => false,
            'is_active' => true,
        ]);
        $member->setPin('6946');
        $member->save();

        return $member;
    }

    public function test_login_page_returns_ok(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Celular o cédula', false);
    }

    public function test_home_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_member_can_login_and_see_home(): void
    {
        $member = $this->adminMember();

        $response = $this->post('/login', [
            'login' => $member->cedula,
            'pin' => '6945',
        ]);

        $response->assertRedirect(route('home'));
        $this->get('/')->assertOk();
    }

    public function test_samuel_profile_has_dino_theme_by_default_after_migration(): void
    {
        SamuelProfile::query()->delete();

        $colors = SamuelProfile::current()->themeColors();

        $this->assertSame('#5ead65', strtolower($colors['primary']));
        $this->assertSame('#a8d08d', strtolower($colors['secondary']));
        $this->assertSame('#f3faf0', strtolower($colors['background']));
    }

    public function test_app_theme_presets_and_hex_normalization(): void
    {
        $presets = AppTheme::presets();

        $this->assertArrayHasKey('dino', $presets);
        $this->assertSame('#5EAD65', $presets['dino']['primary']);
        $this->assertSame('#5ead65', AppTheme::normalizeHex('#5EAD65'));
        $this->assertNull(AppTheme::normalizeHex('bad'));
    }

    public function test_admin_can_open_theme_page(): void
    {
        $member = $this->adminMember();
        FamilySession::login($member);

        $this->get('/admin/tonos')
            ->assertOk()
            ->assertSee('Verde dinosaurio', false)
            ->assertSee('Tonos de la app', false);
    }

    public function test_admin_can_apply_theme_preset(): void
    {
        $member = $this->adminMember();
        FamilySession::login($member);

        $profile = SamuelProfile::current();
        $profile->update([
            'theme_primary' => '#f472b6',
            'theme_secondary' => '#a78bfa',
            'theme_background' => '#fff7fb',
        ]);

        $this->put('/admin/tonos', ['preset' => 'dino'])
            ->assertRedirect(route('admin.theme.edit'));

        $profile->refresh();
        $this->assertSame('#5EAD65', $profile->theme_primary);
        $this->assertSame('#A8D08D', $profile->theme_secondary);
        $this->assertSame('#F3FAF0', $profile->theme_background);
    }

    public function test_admin_personas_and_samuel_routes_load(): void
    {
        $member = $this->adminMember();
        FamilySession::login($member);

        $this->get('/admin')->assertOk();
        $this->get('/admin/personas')->assertOk();
        $this->get('/admin/personas/nuevo')->assertOk();
        $this->get('/samuel')->assertOk();
        $this->get('/muro')->assertOk();
    }

    public function test_guest_cannot_open_admin_theme(): void
    {
        $this->get('/admin/tonos')->assertRedirect();
    }

    public function test_member_can_upload_profile_avatar_with_preview_url_version(): void
    {
        Storage::fake('local');
        $member = $this->adminMember();
        $member->update(['relationship' => 'madrina']);
        FamilySession::login($member);

        $file = UploadedFile::fake()->image('yo.jpg', 320, 320);

        $this->put('/mi-perfil', [
            'avatar_photo' => $file,
        ])->assertRedirect(route('profile.edit'));

        $member->refresh();
        $this->assertTrue($member->hasAvatarPhoto());
        $this->assertStringContainsString('?v=', (string) $member->avatarPhotoUrl());

        $response = $this->get($member->avatarPhotoUrl());
        $response->assertOk();
        $this->assertStringContainsString('max-age=31536000', (string) $response->headers->get('cache-control'));

        $topbarUrl = FamilySession::topbarAvatarUrl($member);
        $this->assertSame($member->avatarPhotoUrl(), $topbarUrl);
        $member->update(['name' => 'Admin prueba dos']);
        $this->assertSame($topbarUrl, FamilySession::topbarAvatarUrl($member->fresh()));
    }

    public function test_profile_update_keeps_relationship_when_only_avatar_sent(): void
    {
        Storage::fake('local');
        $member = $this->adminMember();
        $member->update(['relationship' => 'madrina']);
        FamilySession::login($member);

        $this->put('/mi-perfil', [
            'avatar_emoji' => '💛',
        ])->assertRedirect(route('profile.edit'));

        $member->refresh();
        $this->assertSame('madrina', $member->relationship);
        $this->assertSame('💛', $member->avatar_emoji);
    }

    public function test_member_can_publish_post_with_multiple_files(): void
    {
        Storage::fake('local');
        config(['samuel.storage_driver' => 'local']);

        $member = $this->adminMember();
        FamilySession::login($member);

        $this->post(route('upload.store'), [
            'memory_taken' => 'today',
            'caption' => 'Varias fotos juntas',
            'show_on_wall' => '1',
            'media' => [
                UploadedFile::fake()->image('uno.jpg'),
                UploadedFile::fake()->image('dos.jpg'),
            ],
        ])->assertRedirect(route('memories.index'));

        $post = MemoryPost::query()->latest('id')->first();
        $this->assertNotNull($post);
        $this->assertSame(2, $post->mediaItems()->count());
    }

    public function test_publish_notifies_other_active_members(): void
    {
        Storage::fake('local');
        config(['samuel.storage_driver' => 'local']);

        $author = $this->adminMember();
        $other = $this->extraMember();
        FamilySession::login($author);

        $this->post(route('upload.store'), [
            'memory_taken' => 'today',
            'caption' => 'Hola familia',
            'show_on_wall' => '1',
            'media' => [UploadedFile::fake()->image('foto.jpg')],
        ])->assertRedirect(route('memories.index'));

        $this->assertSame(
            1,
            FamilyNotification::query()->where('family_member_id', $other->id)->count()
        );

        FamilySession::login($other);
        $this->get(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 1);
    }

    public function test_authenticated_shell_includes_notification_bell_assets(): void
    {
        $member = $this->adminMember();
        FamilySession::login($member);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="samuelNotifOpen"', false)
            ->assertSee('samuel-notifications.js', false)
            ->assertSee('samuelNotificationsConfig', false);
    }

    public function test_upload_create_page_loads_preview_script(): void
    {
        $member = $this->adminMember();
        FamilySession::login($member);

        $this->get(route('upload.create'))
            ->assertOk()
            ->assertSee('samuel-page-upload', false)
            ->assertSee('interactive-widget=overlays-content', false)
            ->assertSee('samuel-upload-file-preview.js', false)
            ->assertSee('heic2any.min.js', false)
            ->assertSee('samuel-upload-preview.js', false)
            ->assertSee('samuel-post-carousel.js', false)
            ->assertSee('samuel-upload-thumbs-hint', false)
            ->assertSee('Publicar recuerdo', false)
            ->assertSee('samuelUploadPreview', false);
    }

    public function test_upload_preview_endpoint_returns_jpeg(): void
    {
        $member = $this->adminMember();
        FamilySession::login($member);

        $this->post(route('upload.preview'), [
            'file' => UploadedFile::fake()->image('iphone.jpg', 800, 600),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_comment_notifies_post_author(): void
    {
        $author = $this->adminMember();
        $other = $this->extraMember();

        $post = MemoryPost::query()->create([
            'family_member_id' => $author->id,
            'caption' => 'Mi recuerdo',
            'show_on_wall' => true,
            'memory_taken_at' => now(),
        ]);

        FamilySession::login($other);

        $this->postJson(route('posts.comment.store', $post), [
            'body' => 'Qué bonito',
        ])->assertOk();

        $notification = FamilyNotification::query()
            ->where('family_member_id', $author->id)
            ->where('type', FamilyNotification::TYPE_POST_COMMENT)
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('comentó tu recuerdo', $notification->title);
        $this->assertSame($other->id, $notification->actor_family_member_id);
    }

    public function test_reaction_notifies_post_author(): void
    {
        $author = $this->adminMember();
        $other = $this->extraMember();

        $post = MemoryPost::query()->create([
            'family_member_id' => $author->id,
            'caption' => 'Para reaccionar',
            'show_on_wall' => true,
            'memory_taken_at' => now(),
        ]);

        FamilySession::login($other);

        $this->post(route('posts.reaction', $post), [
            'emotion' => 'love',
        ], ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $notification = FamilyNotification::query()
            ->where('family_member_id', $author->id)
            ->where('type', FamilyNotification::TYPE_POST_REACTION)
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('reaccionó', $notification->title);
        $this->assertStringContainsString('❤️', $notification->body);
    }

    public function test_member_can_comment_and_reply_on_wall_post(): void
    {
        $author = $this->adminMember();
        $other = $this->extraMember();

        $post = MemoryPost::query()->create([
            'family_member_id' => $author->id,
            'caption' => 'Recuerdo en el muro',
            'show_on_wall' => true,
            'memory_taken_at' => now(),
        ]);

        FamilySession::login($other);

        $this->postJson(route('posts.comment.store', $post), [
            'body' => 'Qué lindo recuerdo',
        ])
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('comments.0.body', 'Qué lindo recuerdo');

        $parentId = MemoryPostComment::query()->where('memory_post_id', $post->id)->value('id');
        $this->assertNotNull($parentId);

        FamilySession::login($author);

        $this->postJson(route('posts.comment.store', $post), [
            'body' => 'Gracias por comentar',
            'parent_comment_id' => $parentId,
        ])
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('comments.0.replies.0.body', 'Gracias por comentar')
            ->assertJsonPath('comments.0.replies.0.reply_to_name', 'Tío prueba');
    }

    public function test_wall_comments_toggle_markup_when_post_has_comments(): void
    {
        $member = $this->adminMember();
        $post = MemoryPost::query()->create([
            'family_member_id' => $member->id,
            'caption' => 'Con comentarios',
            'show_on_wall' => true,
            'memory_taken_at' => now(),
        ]);
        $post->comments()->create([
            'family_member_id' => $member->id,
            'body' => 'Hola muro',
        ]);

        FamilySession::login($member);

        $this->get(route('wall'))
            ->assertOk()
            ->assertSee('data-samuel-comments-toggle', false)
            ->assertSee('Ocultar comentarios (1)', false)
            ->assertSee('data-samuel-comment-reply', false);
    }

    public function test_wall_loads_media_lightbox(): void
    {
        $member = $this->adminMember();
        FamilySession::login($member);

        $this->get(route('wall'))
            ->assertOk()
            ->assertSee('samuel-media-lightbox.js', false)
            ->assertSee('samuelMediaLightbox', false);
    }

    public function test_wall_shows_reactions_who_sheet(): void
    {
        $member = $this->adminMember();
        FamilySession::login($member);

        $this->get(route('wall'))
            ->assertOk()
            ->assertSee('samuel-reactions-who.js', false)
            ->assertSee('samuelReactionsWhoSheet', false);
    }

    public function test_member_can_view_other_family_profile_from_wall(): void
    {
        $admin = $this->adminMember();
        $admin->update(['relationship' => 'madrina']);

        $other = new FamilyMember([
            'cedula' => '1000000098',
            'name' => 'Tía prueba',
            'avatar_emoji' => '🌸',
            'is_admin' => false,
            'is_active' => true,
            'relationship' => 'tia',
        ]);
        $other->setPin('1234');
        $other->save();

        FamilySession::login($admin);

        $this->get('/familia/'.$other->id)
            ->assertOk()
            ->assertSee('Tía prueba', false)
            ->assertSee('Tía de', false);
    }

    public function test_admin_can_delete_other_member(): void
    {
        $admin = $this->adminMember();
        $other = new FamilyMember([
            'cedula' => '1000000099',
            'name' => 'Usuario prueba',
            'avatar_emoji' => '🙂',
            'is_admin' => false,
            'is_active' => true,
            'relationship' => 'tio',
        ]);
        $other->setPin('1234');
        $other->save();

        FamilySession::login($admin);

        $this->delete('/admin/personas/'.$other->id)
            ->assertRedirect(route('admin.members.index'));

        $this->assertNull(FamilyMember::query()->find($other->id));
    }
}
