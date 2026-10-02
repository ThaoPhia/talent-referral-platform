<x-mail::message>
# New referrer application

{{ $referrer->name }} ({{ $referrer->email }}) signed up as a referrer.

<x-mail::button :url="route('admin.referrers.show', $referrer)">
Review referrer
</x-mail::button>
</x-mail::message>