<x-layout>
    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
			<div class="flex flex-col justify-between gap-6 rounded-2xl border border-gray-200 bg-white px-6 py-5 sm:flex-row sm:items-center dark:border-gray-800 dark:bg-white/3">
                  <div class="flex items-center gap-2 sm:pr-3">
                    <span class="text-base font-medium text-gray-700 dark:text-gray-400">
                      Welcome Back</span>
                    <span class="bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500 inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-lg font-medium">{{ auth()->user()->first_name  }} {{ auth()->user()->last_name }}</span>
                  </div>
              </div>
			  
           
    </main>
   
</x-layout>
