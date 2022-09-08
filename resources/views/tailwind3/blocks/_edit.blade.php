<div class="bg-white border border-gray-300 overflow-hidden shadow rounded-lg my-2">
    <div class="px-4 py-5 sm:p-6">
        @if(Translator::checkCreateKeyPermission($user))
        <form action="{{ action($controller.'@postAdd', [$group]) }}" method="POST" role="form" class="mb-4">
            @csrf()
            <div class="block text-sm font-medium text-gray-700 mb-1">Add new keys to this group:</div>
            <div class="form-floating mb-3">
                <textarea class="form-control shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" rows="3" style="height: 100px" id="keys" name="keys">{{ old('keys') }}</textarea>
                <label for="keys" class="text-sm font-medium">Add 1 key per line, without the group prefix</label>
            </div>
            <input type="submit" value="Add keys" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        </form>
        <hr>
        @endif
        <div class="flex justify-between">
            <!-- Left -->
            <h4 class="font-medium text-xl my-4">Total: {{ $numTranslations }}</h4>
            <!-- Right -->
            <form action="{{ action($controller.'@getIndex', [$group]) }}" method="GET" role="form" class="mb-4" style="margin: auto 0;">
                <div class="flex">
                    <button id="dropdown-button" data-modal-toggle="locale-modal" class="flex-shrink-0 z-10 inline-flex items-center py-2.5 px-4 text-sm font-medium text-center rounded-l-lg text-white bg-blue-700" type="button">
                        Languages
                        <svg xmlns="http://www.w3.org/2000/svg" fill="white" viewBox="0 0 24 24" class="ml-3 w-5 h-5"><path d="M6 13h12v-2H6M3 6v2h18V6M10 18h4v-2h-4v2Z"/></svg>
                    </button>
                    <!-- Search -->
                    <div class="relative w-full" style="min-width:500px">
                        <input name="search" value="{{ $search }}" type="search" id="search-dropdown" class="block p-2.5 w-full z-20 text-sm text-gray-900 rounded-r-lg border-l-gray-100 border-l-2 border border-gray-300 " placeholder="Search" autocomplete="off">
                        <button type="submit" class="absolute top-0 right-0 p-2.5 text-sm font-medium text-white bg-blue-700 rounded-r-lg border border-blue-700">
                            <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </button>
                    </div>
                </div>
                <!-- Hidden -->
                @if($custom_locales)
                @foreach($locales as $locale)
                <input type="hidden" name="locale[]" value="{{ $locale }}" />
                @endforeach
                @endif
                @if ($order)
                <input type="hidden" name="order" value="{{ $order }}" />
                @if ($desc)
                <input type="hidden" name="desc" value="{{ $desc ? '1' : '0' }}" />
                @endif
                @endif
            </form>
            
            <!-- Main modal -->
            <div id="locale-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 h-modal md:h-full justify-center items-center">
                <div class="relative p-4 w-full max-w-2xl h-full md:h-auto">
                    <!-- Modal content -->
                    <div class="relative bg-white rounded-lg shadow">
                        <form action="{{ action($controller.'@getIndex', [$group]) }}" method="GET" role="form">
                            @if($search)
                            <input type="hidden" name="search" value="{{ $search }}" />
                            @endif
                            <!-- Modal header -->
                            <div class="flex justify-between items-start p-4 rounded-t border-b">Languages</div>
                            <!-- Modal body -->
                            <div class="p-6 space-y-6 columns-3">
                                @foreach($all_locales as $locale)
                                <div class="flex items-center mb-4">
                                    <input {{ $locales->contains($locale) ? 'checked' : '' }} id="locale-checkbox{{$locale}}" name="locale[]" type="checkbox" value="{{ $locale }}" class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500">
                                    <label for="locale-checkbox{{$locale}}" class="ml-2 text-sm font-medium text-gray-900 uppercase">{{ $locale }}</label>
                                </div>
                                @endforeach
                            </div>
                            <!-- Modal footer -->
                            <div class="flex justify-between items-center p-6 space-x-2 rounded-b border-t border-gray-200">
                                <button data-modal-toggle="locale-modal" class="text-blue border border-gray-300 rounded-lg text-sm px-5 py-2.5 text-center">Cancel</button>
                                <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 rounded-lg text-sm px-5 py-2.5 text-center">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="text-xs bg-gray-100 text-gray-700 uppercase ">
                <tr>
                    <th scope="col" class="py-3 px-6" width="15%">
                        <form action="{{ action($controller.'@getIndex', [$group]) }}" method="GET" role="form">
                            <button type="submit" class="flex items-center uppercase">
                                Key
                                <!-- Icons -->
                                @if(!$order)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ml-1 w-5 h-5"><path d="m7 10 5 5 5-5H7Z"/></svg>
                                @else
                                @if(($order == 'key'))
                                @if(!$desc)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ml-1 w-5 h-5"><path d="m7 10 5 5 5-5H7Z"/></svg>
                                @endif
                                @if($desc)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ml-1 w-5 h-5"><path d="m7 15 5-5 5 5H7Z"/></svg>
                                @endif
                                @else
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ml-1 w-5 h-5"><path d="m12 6-5 5h10l-5-5m-5 7 5 5 5-5H7Z"/></svg>
                                @endif
                                @endif
                            </button>
                            
                            <!-- Hidden -->
                            @if($custom_locales) 
                            @foreach($locales as $o_locale)
                            <input type="hidden" name="locale[]" value="{{ $o_locale }}" />
                            @endforeach 
                            @endif
                            @if($search)
                            <input type="hidden" name="search" value="{{ $search }}" />
                            @endif
                            @if(($order == 'key' and !$desc))
                            <input type="hidden" name="desc" value="1">
                            @endif
                            @if(($order != 'key') or !$desc)
                            <input type="hidden" name="order" value="key" />
                            @endif
                        </form>
                    </th>
                    
                    @foreach ($locales as $locale)
                    <th th scope="col" class="py-3 px-6">
                        <form action="{{ action($controller.'@getIndex', [$group]) }}" method="GET" role="form">
                            <button type="submit" class="flex items-center uppercase">
                                {{ $locale }}
                                <!-- Icons -->
                                @if(($order == $locale))
                                @if(!$desc)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ml-1 w-5 h-5"><path d="m7 10 5 5 5-5H7Z"/></svg>
                                @endif
                                @if($desc)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ml-1 w-5 h-5"><path d="m7 15 5-5 5 5H7Z"/></svg>
                                @endif
                                @else
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ml-1 w-5 h-5"><path d="m12 6-5 5h10l-5-5m-5 7 5 5 5-5H7Z"/></svg>
                                @endif
                            </button>
                            
                            <!-- Hidden -->
                            @if($custom_locales) 
                            @foreach($locales as $o_locale)
                            <input type="hidden" name="locale[]" value="{{ $o_locale }}" />
                            @endforeach 
                            @endif
                            @if($search)
                            <input type="hidden" name="search" value="{{ $search }}" />
                            @endif
                            @if(($order == $locale and !$desc))
                            <input type="hidden" name="desc" value="1">
                            @endif
                            @if(($order != $locale) or !$desc)
                            <input type="hidden" name="order" value="{{ $locale }}" />
                            @endif
                        </form>
                    </th>
                    @endforeach
                    
                    @if ($deleteEnabled)
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">&nbsp;</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($translations as $key => $translation)
                <tr id="{{ $key }}" class="{{ $loop->index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $key }}</td>
                    @foreach ($locales as $locale)
                    @php($t = isset($translation[$locale]) ? $translation[$locale] : null)
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        <a href="#edit"
                        class="editable locale-{{ $locale }}"
                        data-locale="{{ $locale }}" data-name="{{ $locale }}|{{ $key }}"
                        id="username" data-type="textarea" data-pk="{{ $t ? $t->id : 0 }}"
                        data-url="{{ $editUrl }}"
                        data-title="Enter translation">{{ $t ? $t->value : '' }}</a>
                    </td>
                    @endforeach
                    @if ($deleteEnabled)
                    <td class="text-right">
                        <a href="{{ action($controller . '@postDelete', [$group, $key]) }}"
                        class="delete-key inline-flex justify-center py-2 px-4 border border-transparent text-sm font-medium text-red-600 hover:text-red-700 focus:outline-none"
                        data-confirm="Are you sure you want to delete the translations for '{{ $key }}'?">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </a>
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
</div>
