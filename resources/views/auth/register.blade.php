@section('guest-layout', 'split')

<x-guest-layout>
    @include('auth.partials.split-auth', ['initialMode' => 'register'])
</x-guest-layout>
