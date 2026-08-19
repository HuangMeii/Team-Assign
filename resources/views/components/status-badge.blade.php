@props(['status', 'type' => 'invite', 'showIcon' => true])

@php
    $configs = [
        'Pending' => ['color' => 'warning', 'text' => 'dark', 'icon' => 'hourglass-half', 'label' => 'Chờ xử lý'],
        'Accepted' => ['color' => 'success', 'text' => 'white', 'icon' => 'check', 'label' => 'Đã chấp nhận'],
        'Approved' => ['color' => 'success', 'text' => 'white', 'icon' => 'check', 'label' => 'Đã chấp nhận'],
        'Rejected' => ['color' => 'danger', 'text' => 'white', 'icon' => 'times', 'label' => 'Đã từ chối'],
    ];
    
    $config = $configs[$status] ?? ['color' => 'secondary', 'text' => 'white', 'icon' => 'question', 'label' => $status];
@endphp

<span class="badge bg-{{ $config['color'] }} text-{{ $config['text'] }}" {{ $attributes }}>
    @if ($showIcon)
        <i class="fas fa-{{ $config['icon'] }} me-1"></i>
    @endif
    {{ $config['label'] }}
</span>