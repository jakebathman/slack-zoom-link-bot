<?php

namespace Tests\Feature;

use App\Exceptions\SlackApiVerificationException;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SlackMiddlewareTest extends TestCase
{
    protected int $timestamp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timestamp = Carbon::now()->timestamp;
    }

    #[Test]
    public function middleware_correctly_validates_signature(): void
    {
        $response = $this->withHeaders([
            'X-Slack-Request-Timestamp' => $this->timestamp,
            'X-Slack-Signature' => $this->getHeaderSignature(),
        ])
        ->get('/api/slack/test?foo=bar');

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    #[Test]
    public function middleware_correctly_validates_signature_with_request_body(): void
    {
        $body = ['text' => 'foo bar'];

        $response = $this->withHeaders([
            'X-Slack-Request-Timestamp' => $this->timestamp,
            'X-Slack-Signature' => $this->getHeaderSignature($body),
        ])
        ->json('POST', '/api/slack/test?foo=bar', $body);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    #[Test]
    public function middleware_rejects_invalid_signature(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(SlackApiVerificationException::class);

        $this->withHeaders([
            'X-Slack-Request-Timestamp' => $this->timestamp,
            'X-Slack-Signature' => $this->getHeaderSignature('?foo=bar'),
        ])
        ->get('/api/slack/test');
    }

    #[Test]
    public function middleware_rejects_timestamp_skewed_into_the_future(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(SlackApiVerificationException::class);

        $futureTimestamp = Carbon::now()->addMinutes(10)->timestamp;
        $basestring = "v0:{$futureTimestamp}:";
        $signature = 'v0='.hash_hmac('sha256', $basestring, config('services.slack.signing_secret'));

        $this->withHeaders([
            'X-Slack-Request-Timestamp' => $futureTimestamp,
            'X-Slack-Signature' => $signature,
        ])
        ->get('/api/slack/test');
    }

    protected function getHeaderSignature(array|string $body = ''): string
    {
        $body = is_array($body) ? json_encode($body) : $body;
        $basestring = "v0:{$this->timestamp}:{$body}";

        return 'v0='.hash_hmac('sha256', $basestring, config('services.slack.signing_secret'));
    }
}
