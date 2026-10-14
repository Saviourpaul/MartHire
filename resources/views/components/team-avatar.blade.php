@props(['user' => null])

@php($name = trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')) ?: 'Former member')
<span {{ $attributes->merge(['class' => 'inline-flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-50 text-sm font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300']) }} title="{{ $name }}">
    @if ($user?->profile_image_path)
        <img src="{{ $user->profileImageUrl() }}" alt="" class="h-full w-full object-cover">
    @else
        <span aria-hidden="true">{{ mb_strtoupper(mb_substr($user?->first_name ?? 'F', 0, 1).mb_substr($user?->last_name ?? 'M', 0, 1)) }}</span>
    @endif
</span>
