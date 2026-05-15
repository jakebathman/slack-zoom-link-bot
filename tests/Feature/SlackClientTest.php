<?php

namespace Tests\Feature;

use App\Exceptions\SlackApiError;
use App\Slack\SlackClient;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SlackClientTest extends TestCase
{
    #[Test]
    public function it_calls_api_methods_successfully(): void
    {
        Http::fake([
            'https://slack.com/api/api.test' => Http::response([
                'ok' => true,
                'args' => ['foo' => 'some return value'],
            ], 200),
        ]);

        $client = app(SlackClient::class);

        $response = $client->apiTest(null, 'some return value');

        $this->assertIsArray($response);
        $this->assertTrue($response['ok']);
        $this->assertEquals('some return value', $response['args']['foo']);
    }

    #[Test]
    public function it_throws_errors_when_slack_returns_not_ok(): void
    {
        $this->withoutExceptionHandling();

        Http::fake([
            'https://slack.com/api/api.test' => Http::response([
                'ok' => false,
                'error' => 'some_error',
            ], 200),
        ]);

        $client = app(SlackClient::class);

        try {
            $client->apiTest('some_error');

            $this->fail('Expected SlackApiError not thrown by SlackClient');
        } catch (SlackApiError $e) {
            $this->assertEquals('some_error', $e->getError());
        }
    }
}
