<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_and_omnichannel_sidebar_items_are_visible(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('WHATSAPP MARKETING')
            ->assertSee('WhatsApp Providers')
            ->assertSee('WhatsApp Cloud API')
            ->assertSee('WhatsApp Templates')
            ->assertSee('WhatsApp Broadcast')
            ->assertSee('WhatsApp Reply Inbox')
            ->assertSee('SERVICE MANAGEMENT')
            ->assertSee('Omnichannel Inbox')
            ->assertSee(route('admin.system.whatsapp-providers.index'), false)
            ->assertSee(route('admin.marketing.whatsapp-cloud-api.index'), false)
            ->assertSee(route('admin.marketing.whatsapp-templates.index'), false)
            ->assertSee(route('admin.marketing.whatsapp-broadcasts.index'), false)
            ->assertSee(route('admin.marketing.whatsapp-replies.index'), false)
            ->assertSee(route('admin.service.omnichannel.index'), false);
    }
}
