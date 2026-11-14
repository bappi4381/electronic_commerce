{{-- Delivery-risk pill for an order (compact hides Low); nothing while the check has not run. --}}
@props(['order', 'compact' => false])

@php
    $level = $order->riskLevelEnum();
    $flagged = $order->hasRiskFlag(\App\Services\Fraud\CheckoutGuard::FLAG_IP_VELOCITY);
@endphp

@if($level && ($level !== \App\Enums\RiskLevel::Low || !$compact))
    <x-admin.status-badge :tone="$order->risk_reviewed_at ? 'green' : $level->tone()" :dot="false"
                          :label="$order->risk_reviewed_at ? 'Reviewed safe' : $level->label()" :title="$level->description()" />
@endif
@if($flagged)
    <x-admin.status-badge tone="orange" :dot="false" label="Many orders from IP" title="Placed after this IP address reached the hourly order limit." />
@endif
