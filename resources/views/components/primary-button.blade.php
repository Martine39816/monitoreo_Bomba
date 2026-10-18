<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white whitespace-nowrap shadow-sm hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-150']) }}>
    {{ $slot }}
</button>
