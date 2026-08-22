<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Axis;
use App\Models\Game;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * AssetController and GameAssetController — the media library and the pivot that
 * links files to games. Neither had any coverage.
 */
class AdminAssetTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'assets@test.com',
            'password' => Hash::make('password'),
            'status' => 'ACTIVE',
        ]);

        $this->token = $admin->createToken('admin')->plainTextToken;
    }

    private function asAdmin(): self
    {
        $this->withHeader('Authorization', 'Bearer '.$this->token);

        return $this;
    }

    private function lottieFile(string $name = 'star.json'): UploadedFile
    {
        $lottie = json_encode([
            'v' => '5.7.4', 'fr' => 30, 'ip' => 0, 'op' => 60,
            'w' => 512, 'h' => 512, 'layers' => [],
        ]);

        return UploadedFile::fake()->createWithContent($name, $lottie);
    }

    public function test_admin_can_upload_a_lottie_asset_and_metadata_is_extracted(): void
    {
        $res = $this->asAdmin()->post('/api/v1/admin/assets', [
            'file' => $this->lottieFile(),
            'type' => 'LOTTIE',
            'name' => 'نجمة ذهبية',
            'code' => 'LOTTIE-STAR',
        ], ['Accept' => 'application/json']);

        $res->assertStatus(201)
            ->assertJsonPath('data.type', 'LOTTIE')
            ->assertJsonPath('data.code', 'LOTTIE-STAR')
            ->assertJsonPath('data.metadata.frame_rate', 30)
            ->assertJsonPath('data.metadata.total_frames', 60);

        $this->assertNotEmpty($res->json('data.checksum'));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', parse_url($res->json('data.url'), PHP_URL_PATH)));
    }

    public function test_a_malformed_lottie_file_is_rejected_in_arabic(): void
    {
        $res = $this->asAdmin()->post('/api/v1/admin/assets', [
            'file' => UploadedFile::fake()->createWithContent('broken.json', '{not json'),
            'type' => 'LOTTIE',
            'name' => 'تالف',
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422)->assertJsonPath('success', false);
        $this->assertMatchesRegularExpression('/\p{Arabic}/u', $res->json('message'));
        $this->assertStringNotContainsString('Syntax error', $res->json('message'));
    }

    public function test_asset_list_filters_by_type_and_paginates(): void
    {
        Asset::create(['code' => 'A-1', 'name' => 'صورة', 'type' => 'IMAGE', 'mime_type' => 'image/png', 'disk' => 'public', 'path' => 'a/1.png', 'size' => 10, 'checksum' => 'x1', 'status' => 'ACTIVE', 'version' => 1]);
        Asset::create(['code' => 'A-2', 'name' => 'صوت', 'type' => 'AUDIO', 'mime_type' => 'audio/mp3', 'disk' => 'public', 'path' => 'a/2.mp3', 'size' => 20, 'checksum' => 'x2', 'status' => 'ACTIVE', 'version' => 1]);

        $this->asAdmin()->getJson('/api/v1/admin/assets?type=AUDIO')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.type', 'AUDIO');

        $this->asAdmin()->getJson('/api/v1/admin/assets')
            ->assertStatus(200)
            ->assertJsonStructure(['meta' => ['current_page', 'per_page', 'total', 'last_page']]);
    }

    public function test_admin_can_attach_update_and_detach_a_game_asset(): void
    {
        $axis = Axis::create(['name' => 'أ', 'slug' => 'a', 'status' => 'ACTIVE', 'sort_order' => 1]);
        $skill = Skill::create(['axis_id' => $axis->id, 'name' => 'م', 'slug' => 's', 'status' => 'ACTIVE', 'sort_order' => 1]);

        $game = Game::create([
            'code' => 'G-1', 'name' => 'لعبة', 'slug' => 'g-1', 'type' => 'TAP',
            'axis_id' => $axis->id, 'skill_id' => $skill->id,
            'level' => 1, 'difficulty' => 'easy', 'min_age' => 3, 'max_age' => 6,
            'duration_seconds' => 60, 'status' => 'DRAFT', 'version' => 1, 'config' => [],
        ]);

        $asset = Asset::create(['code' => 'A-1', 'name' => 'خلفية', 'type' => 'IMAGE', 'mime_type' => 'image/png', 'disk' => 'public', 'path' => 'a/1.png', 'size' => 10, 'checksum' => 'x1', 'status' => 'ACTIVE', 'version' => 1]);

        $attached = $this->asAdmin()->postJson("/api/v1/admin/games/{$game->id}/assets", [
            'asset_id' => $asset->id,
            'role' => 'BACKGROUND',
            'sort_order' => 1,
        ]);
        $attached->assertStatus(201);

        // the same file in the same role twice must be refused, not duplicated
        $this->asAdmin()->postJson("/api/v1/admin/games/{$game->id}/assets", [
            'asset_id' => $asset->id,
            'role' => 'BACKGROUND',
        ])->assertStatus(422)->assertJsonPath('error_code', 'GAME_ASSET_ALREADY_LINKED');

        $gameAssetId = $game->fresh()->gameAssets()->first()->id;

        $this->asAdmin()->putJson("/api/v1/admin/games/{$game->id}/assets/{$gameAssetId}", ['role' => 'ANIMATION'])
            ->assertStatus(200);

        $this->asAdmin()->deleteJson("/api/v1/admin/games/{$game->id}/assets/{$gameAssetId}")
            ->assertStatus(200);

        $this->assertSame(0, $game->fresh()->gameAssets()->count());
    }

    public function test_game_asset_validation_errors_are_arabic(): void
    {
        $axis = Axis::create(['name' => 'أ', 'slug' => 'a', 'status' => 'ACTIVE', 'sort_order' => 1]);
        $skill = Skill::create(['axis_id' => $axis->id, 'name' => 'م', 'slug' => 's', 'status' => 'ACTIVE', 'sort_order' => 1]);
        $game = Game::create([
            'code' => 'G-2', 'name' => 'لعبة', 'slug' => 'g-2', 'type' => 'TAP',
            'axis_id' => $axis->id, 'skill_id' => $skill->id,
            'level' => 1, 'difficulty' => 'easy', 'min_age' => 3, 'max_age' => 6,
            'duration_seconds' => 60, 'status' => 'DRAFT', 'version' => 1, 'config' => [],
        ]);

        $res = $this->asAdmin()->postJson("/api/v1/admin/games/{$game->id}/assets", []);

        $res->assertStatus(422);
        $this->assertSame('حقل الملف مطلوب.', $res->json('errors.asset_id.0'));
        $this->assertSame('حقل الدور مطلوب.', $res->json('errors.role.0'));
    }
}
