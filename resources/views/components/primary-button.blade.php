<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-xl border border-transparent bg-[#213d70] px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-[#183768] focus:outline-none focus:ring-2 focus:ring-[#213d70] focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
