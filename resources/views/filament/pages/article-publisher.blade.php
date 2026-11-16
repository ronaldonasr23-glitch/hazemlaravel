<x-filament-panels::page>
    <form wire:submit="import">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" icon="heroicon-o-calendar-date-range">
                Schedule articles
            </x-filament::button>
        </div>
    </form>

    @php($scheduled = $this->scheduledPosts())

    <x-filament::section class="mt-8">
        <x-slot name="heading">Upcoming scheduled articles ({{ $scheduled->count() }})</x-slot>
        <x-slot name="description">
            Articles waiting to go live. They publish automatically at their scheduled time (requires the
            cron / scheduler to be running on the server).
        </x-slot>

        @if ($scheduled->isEmpty())
            <p class="text-sm text-gray-500">No articles are scheduled right now.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="py-2">Title</th>
                        <th class="py-2">Publishes at</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($scheduled as $post)
                        <tr class="border-t border-gray-100 dark:border-gray-700">
                            <td class="py-2 pr-4">{{ $post->title }}</td>
                            <td class="py-2 whitespace-nowrap">
                                {{ optional($post->published_at)->format('M j, Y · H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-panels::page>
