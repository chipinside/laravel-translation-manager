<div class="card mt-2 mb-4">
    <div class="card-body">
        @if(Translator::checkCreateKeyPermission($user))
        <form action="{{ action($controller.'@postAdd', [$group]) }}" method="POST" role="form">
            @csrf()
            <div>Add new keys to this group:</div>
            <div class="form-floating mb-3">
                <textarea class="form-control" rows="3" style="height: 100px" id="keys" name="keys" placeholder="Add 1 key per line, without the group prefix">{{ old('keys') }}</textarea>
                <label for="keys">Add 1 key per line, without the group prefix</label>
            </div>
            <input type="submit" value="Add keys" class="btn btn-primary">
        </form>
        <hr>
        @endif
        <h4>Total: {{ $numTranslations }}</h4>
        <table class="table">
            <thead>
                <tr>
                    <th width="15%">Key</th>
                    @foreach ($locales as $locale)
                        <th>{{ $locale }}</th>
                    @endforeach
                    @if ($deleteEnabled)
                        <th>&nbsp;</th>
                    @endif
                </tr>
            </thead>
            <tbody>

            @foreach ($translations as $key => $translation)
                <tr id="{{ $key }}">
                    <td>{{ $key }}</td>
                    @foreach ($locales as $locale)
                        @php($t = isset($translation[$locale]) ? $translation[$locale] : null)
                        <td>
                            <a href="#edit"
                               class="editable locale-{{ $locale }}"
                               data-locale="{{ $locale }}" data-name="{{ $locale }}|{{ $key }}"
                               id="username" data-type="textarea" data-pk="{{ $t ? $t->id : 0 }}"
                               data-url="{{ $editUrl }}"
                               data-title="Enter translation">{{ $t ? $t->value : '' }}</a>
                        </td>
                    @endforeach
                    @if ($deleteEnabled)
                        <td>
                            <a href="{{ action($controller . '@postDelete', [$group, $key]) }}"
                               class="delete-key"
                               data-confirm="Are you sure you want to delete the translations for '{{ $key }}'?">
                                <span class=" fa fa-trash"></span>
                            </a>
                        </td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
