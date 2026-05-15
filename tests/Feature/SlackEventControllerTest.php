<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SlackEventControllerTest extends TestCase
{
    #[Test]
    public function it_posts_a_shareable_link_when_a_zoom_call_block_event_is_received(): void
    {
        config()->set('services.slack.signing_secret', 'test-signing-secret');
        config()->set('services.slack.bot_user_token', 'xoxb-test-bot-token');

        Http::fake([
            'https://slack.com/api/*' => Http::response(['ok' => true], 200),
        ]);

        $eventPayload = [
            'token' => 'verification-token',
            'team_id' => 'T123',
            'api_app_id' => 'A123',
            'type' => 'event_callback',
            'event' => [
                'type' => 'message',
                'subtype' => 'bot_message',
                'channel' => 'C0B4898T620',
                'blocks' => [
                    [
                        'type' => 'call',
                        'call_id' => 'R0B4YV941Q8',
                        'block_id' => '4i9hB',
                        'api_decoration_available' => false,
                        'call' => [
                            'v1' => [
                                'id' => 'R0B4YV941Q8',
                                'app_id' => 'A5GE9BMQC',
                                'date_start' => 1778864818,
                                'active_participants' => [],
                                'all_participants' => [],
                                'display_id' => '928-422-8549',
                                'join_url' => 'https://us02web.zoom.us/j/9284228549?pwd=UkZzVjg3V2ZGQ1Jjd3dXUmVkdFlHUT09&omn=84517238804',
                                'name' => 'Zoom meeting started by jake',
                                'created_by' => 'U01CP1SJ6ES',
                                'date_end' => 0,
                                'channels' => [
                                    'C0B4898T620',
                                ],
                                'is_dm_call' => false,
                                'was_rejected' => false,
                                'was_missed' => false,
                                'was_accepted' => false,
                                'has_ended' => false,
                            ],
                            'media_backend_type' => 'platform_call',
                        ],
                    ],
                ],
            ],
        ];

        $timestamp = Carbon::now()->timestamp;
        $signature = $this->getHeaderSignature($timestamp, $eventPayload);

        $response = $this->withHeaders([
            'X-Slack-Request-Timestamp' => $timestamp,
            'X-Slack-Signature' => $signature,
        ])->json('POST', '/api/slack/event', $eventPayload);

        $response->assertStatus(200);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://slack.com/api/chat.postMessage'
                && $request['channel'] === 'C0B4898T620'
                && $request['text'] === 'Shareable link: <https://us02web.zoom.us/j/9284228549?pwd=UkZzVjg3V2ZGQ1Jjd3dXUmVkdFlHUT09&omn=84517238804|928-422-8549>';
        });
    }

    private function getHeaderSignature(int $timestamp, array|string $body): string
    {
        $body = is_array($body) ? json_encode($body) : $body;
        $basestring = "v0:{$timestamp}:{$body}";

        return 'v0='.hash_hmac('sha256', $basestring, config('services.slack.signing_secret'));
    }
}
