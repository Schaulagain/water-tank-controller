<?php

namespace Tests\Feature;

use App\Device\DeviceState;
use App\Livewire\Dashboard;
use App\Livewire\Login;
use App\Livewire\SettingsPage;
use App\Livewire\Simulator;
use App\Models\PumpRun;
use App\Models\Reading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create(['email' => 'me@example.com', 'password' => 'secret-password']);
    }

    public function test_pages_need_a_login(): void
    {
        foreach (['/', '/settings', '/simulator'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_logging_in(): void
    {
        $this->owner();

        Livewire::test(Login::class)
            ->set('email', 'me@example.com')->set('password', 'wrong')
            ->call('login')
            ->assertHasErrors('email');

        Livewire::test(Login::class)
            ->set('email', 'me@example.com')->set('password', 'secret-password')
            ->call('login')
            ->assertRedirect('/');

        $this->assertAuthenticated();
    }

    public function test_the_dashboard_shows_the_tanks_and_the_pump(): void
    {
        app(DeviceState::class)->update([
            'online' => true, 'last_seen' => now()->getTimestamp(),
            'roof' => 42.4, 'underground' => 81, 'pump' => true,
            'pump_since' => now()->subMinutes(7)->getTimestamp(), 'reason' => 'roof tank low',
        ]);
        Reading::create(['roof' => 40, 'underground' => 82, 'pump' => true, 'recorded_at' => now()->subHour()]);
        PumpRun::create(['started_at' => now()->subHours(3), 'ended_at' => now()->subHours(3)->addMinutes(18),
            'mode' => 'auto', 'start_reason' => 'roof tank low', 'stop_reason' => 'roof tank full']);

        $this->actingAs($this->owner())->get('/')
            ->assertOk()
            ->assertSee('42%')
            ->assertSee('81%')
            ->assertSee('Pump is running')
            ->assertSee('7 min')
            ->assertSee('Roof tank low')
            ->assertSee('18 min');
    }

    public function test_the_dashboard_says_when_the_controller_is_offline(): void
    {
        app(DeviceState::class)->update(['online' => false, 'last_seen' => now()->subMinutes(10)->getTimestamp()]);

        $this->actingAs($this->owner())->get('/')->assertSee('Can\'t reach the controller', false);
    }

    public function test_the_buttons_send_commands_to_the_device(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(Dashboard::class)->call('command', 'on');
        $this->assertSame(['topic' => 'home/water/command', 'message' => ['action' => 'on'], 'retain' => false], $this->publisher->last());

        Livewire::test(Dashboard::class)->call('command', 'reset');
        $this->assertSame(['action' => 'reset'], $this->publisher->last()['message']);

        Livewire::test(Dashboard::class)->call('command', 'self-destruct');
        $this->assertCount(2, $this->publisher->sent);
    }

    public function test_saving_settings_sends_them_to_the_device(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(SettingsPage::class)
            ->set('roof_on', 30)
            ->call('save')
            ->assertHasNoErrors();

        $sent = $this->publisher->last();
        $this->assertSame('home/water/config', $sent['topic']);
        $this->assertTrue($sent['retain']);
        $this->assertSame(30, $sent['message']['roof_on']);

        Livewire::test(SettingsPage::class)->assertSet('roof_on', 30);
    }

    public function test_settings_must_make_sense(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(SettingsPage::class)
            ->set('roof_on', 80)->set('roof_off', 70)
            ->call('save')
            ->assertHasErrors('roof_off');

        $this->assertSame([], $this->publisher->sent);
    }

    public function test_the_simulator_page_sends_controls(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(Simulator::class)->call('send', 'power', false);

        $this->assertSame(['topic' => 'home/water/sim/control', 'message' => ['action' => 'power', 'value' => false], 'retain' => false], $this->publisher->last());
    }

    public function test_the_simulator_page_can_be_turned_off(): void
    {
        config(['device.simulator' => false]);

        $this->actingAs($this->owner())->get('/simulator')->assertNotFound();
    }
}
