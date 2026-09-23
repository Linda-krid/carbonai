@section('guest-layout', 'split')

<x-guest-layout>
    @include('auth.partials.split-auth', ['initialMode' => 'login'])
</x-guest-layout>
