<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Article;
use App\Models\Category;
use App\Models\Message;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BlogAndContactTest extends TestCase
{
    use RefreshDatabase;

    // ── Blog ───────────────────────────────────────────────────────

    public function test_blog_lists_published_stories_with_a_featured_one_and_topic_filter(): void
    {
        $guides = $this->blogCategory('Buying guides');
        $tips = $this->blogCategory('Tips');
        $this->article($guides, 'Best phones under Tk 30,000', daysAgo: 0);
        $this->article($tips, 'Make your laptop battery last', daysAgo: 2);
        $this->article($guides, 'Secret draft', status: 'draft');

        $this->get('/en/articles')
            ->assertOk()
            ->assertSee('Featured')
            ->assertSee('Best phones under Tk 30,000')
            ->assertSee('Make your laptop battery last')
            ->assertDontSee('Secret draft')
            ->assertSee('Buying guides');

        $this->get('/en/articles?category=' . $tips->id)
            ->assertOk()
            ->assertSee('Make your laptop battery last')
            ->assertDontSee('Best phones under Tk 30,000')
            ->assertDontSee('Featured');
    }

    public function test_blog_search_matches_titles_and_shows_an_empty_state(): void
    {
        $guides = $this->blogCategory('Buying guides');
        $this->article($guides, 'Air fryer vs oven');
        $this->article($guides, 'Choosing a smart TV');

        $this->get('/en/articles?search=fryer')->assertOk()->assertSee('Air fryer vs oven')->assertDontSee('Choosing a smart TV');
        $this->get('/en/articles?search=nothing-like-this')->assertOk()->assertSee('No articles match');
    }

    public function test_article_page_has_readable_content_related_stories_and_structured_data(): void
    {
        $guides = $this->blogCategory('Buying guides');
        $article = $this->article($guides, 'Best phones under Tk 30,000', content: '<h2>Battery</h2><p>Look for 5000 mAh.</p><script>alert(1)</script>');
        $this->article($guides, 'Related guide');

        $this->get('/en/articles/' . $article->slug)
            ->assertOk()
            ->assertSee('<h2>Battery</h2>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('Keep reading')
            ->assertSee('Related guide')
            ->assertSee('Share on WhatsApp');
    }

    public function test_excerpt_keeps_spaces_between_blocks(): void
    {
        $article = new Article(['content' => ['en' => '<p>Bangladesh.</p><h2>1. Battery</h2>']]);

        $this->assertSame('Bangladesh. 1. Battery', $article->excerpt());
    }

    // ── Contact ────────────────────────────────────────────────────

    public function test_contact_page_shows_the_store_details_from_settings(): void
    {
        Setting::set('contact_phone', '+880 1711-000000');
        Setting::set('contact_email', 'help@onemall.test');
        Setting::set('contact_address', 'Dhanmondi, Dhaka');

        $this->get('/en/contact')
            ->assertOk()
            ->assertSee('+880 1711-000000')
            ->assertSee('help@onemall.test')
            ->assertSee('Dhanmondi, Dhaka')
            ->assertDontSee('Silicon Valley');
    }

    public function test_contact_form_reaches_the_admin_inbox_and_notifies_admins(): void
    {
        Role::findOrCreate('Super Admin', 'admin');
        Admin::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret123'])->assignRole('Super Admin');

        $this->post('/en/contact', [
            'topic' => 'order', 'name' => 'Rahim', 'phone' => '+8801712345678', 'order_id' => 'ORD-20261113-ABC123',
            'message' => 'My parcel has not arrived yet, please check.',
        ])->assertRedirect(route('contact'))->assertSessionHas('contact_sent');

        $message = Message::sole();
        $this->assertFalse((bool) $message->is_admin);
        $this->assertNotNull($message->session_id);
        $this->assertStringContainsString('An order I placed', $message->message);
        $this->assertStringContainsString('01712345678', $message->message);
        $this->assertStringContainsString('ORD-20261113-ABC123', $message->message);
        $this->assertDatabaseCount('notifications', 1);

        $this->get('/en/contact')->assertSee('Message sent');
    }

    public function test_contact_form_needs_a_way_to_reply_and_rejects_bots(): void
    {
        $base = ['topic' => 'product', 'name' => 'Rahim', 'message' => 'Do you have the S24 in stock?'];

        $this->post('/en/contact', $base)->assertSessionHasErrors(['phone', 'email']);
        $this->post('/en/contact', $base + ['phone' => '12345'])->assertSessionHasErrors('phone');
        $this->post('/en/contact', $base + ['email' => 'rahim@example.com', 'website' => 'http://spam.example'])->assertSessionHasErrors('website');

        $this->assertSame(0, Message::count());
    }

    private function blogCategory(string $name): Category
    {
        return Category::create(['type' => 'blog', 'name' => ['en' => $name, 'bn' => $name]]);
    }

    private function article(Category $category, string $title, int $daysAgo = 1, string $status = 'published', string $content = '<p>Useful tips for buyers.</p>'): Article
    {
        $article = Article::create([
            'category_id' => $category->id,
            'title' => ['en' => $title, 'bn' => $title],
            'content' => ['en' => $content, 'bn' => $content],
            'slug' => \Illuminate\Support\Str::slug($title),
            'status' => $status,
            'published_at' => now()->subDays($daysAgo),
        ]);
        $article->forceFill(['created_at' => now()->subDays($daysAgo)])->save();

        return $article;
    }
}
