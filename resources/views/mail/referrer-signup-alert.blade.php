<x-mail::message>
    # New recruiter application

    {{ $referrer->name }} ({{ $referrer->email }}) signed up as a recruiter.

    <x-mail::button :url="route('admin.recruiters.show', $referrer)">
        Review recruiter
    </x-mail::button>
</x-mail::message>