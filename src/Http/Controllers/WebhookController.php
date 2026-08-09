<?php

declare(strict_types=1);

namespace LeefiPay\Mpesa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LeefiPay\Mpesa\DTOs\WebhookEvent;
use LeefiPay\Mpesa\Events\InvoicePaid;
use LeefiPay\Mpesa\Events\PaymentFailed;
use LeefiPay\Mpesa\Events\PaymentSuccessful;
use LeefiPay\Mpesa\Events\WebhookReceived;
use LeefiPay\Mpesa\Exceptions\WebhookSignatureException;
use LeefiPay\Mpesa\Support\WebhookSignature;

class WebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('leefipay.webhook.secret', '');
        $tolerance = (int) config('leefipay.webhook.tolerance_seconds', 300);
        $raw = $request->getContent();
        $signature = (string) $request->header('X-LeefiPay-Signature', '');
        $timestamp = (string) $request->header('X-LeefiPay-Timestamp', '');

        try {
            WebhookSignature::assertValid($raw, $signature, $timestamp, $secret, $tolerance);
        } catch (WebhookSignatureException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();
        $event = WebhookEvent::fromPayload($payload, $timestamp);

        event(new WebhookReceived($event));

        match ($event->name()) {
            'payment.successful' => event(new PaymentSuccessful($event)),
            'payment.failed' => event(new PaymentFailed($event)),
            'invoice.paid' => event(new InvoicePaid($event)),
            default => null,
        };

        return response()->json([
            'success' => true,
            'message' => 'Webhook accepted',
        ]);
    }
}
