<section class="col-span-12 rounded-2xl border border-gray-200 bg-white p-5 xl:col-span-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
    <div class="mb-5">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h2>
        <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    </div>

    @if (count($rows) > 0)
        <div class="max-w-full overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        @foreach ($headers as $header)
                            <th class="px-4 py-3 text-left text-theme-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($rows as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td class="px-4 py-3 text-theme-sm text-gray-700 dark:text-gray-300">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="flex h-40 items-center justify-center rounded-lg bg-gray-50 text-theme-sm text-gray-500 dark:bg-gray-900 dark:text-gray-400">No data available for this report.</div>
    @endif
</section>
