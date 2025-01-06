<div class="form-group mb-4">
    <input type="hidden" name="formData[{{ $formIndex }}][formName]" value="{{ $name }}">
    @if(is_array($data))
        <div class="row fs-14">
            <div class="col-lg">
                <div class="mb-4">
                    <?php 
                        $lang = session('locale') ? session('locale') : env('APP_LOCALE', env('APP_FALLBACK_LOCALE'));
                    ?>
                    @foreach ($ratings as $rating)
                    <ul>
                        <li>
                            <p><strong>{{ $rating['value'] }}</strong> : {{ $lang == 'id' ? $rating['desc_idn'] : $rating['desc_eng'] }}</p>
                        </li>
                    </ul>
                    @endforeach
                </div>
            </div>
        </div>
        @foreach($data as $index => $dataItem)
        <div class="row fs-16">
            <div class="col-lg">
                <div class="mb-4">
                    <h4><strong>{{ $dataItem['title'] }}</strong></h4>
                    <p class="mb-3"><strong>{!! $dataItem['description'] !!}</strong></p>
                    @if(is_array($dataItem['items']))
                        <ul>
                            @foreach($dataItem['items'] as $indexItem => $item)
                                <li>
                                    <div class="row mb-3 align-items-center">
                                        <div  class="col-md">
                                            <div class="mb-2 mb-lg-auto">
                                                <span>{!! $item !!}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-auto justify-content-end">
                                            <select class="form-select" name="formData[{{ $formIndex }}][{{ $index }}][{{ $indexItem }}][score]" id="score" required>
                                                <option value="">select</option>
                                                @foreach ($ratings as $item) {{-- $isManager --}}
                                                    <option value="{{ $item['value'] }}" 
                                                        {{ isset($dataItem['score'][$indexItem]) && $dataItem['score'][$indexItem] == $item['value'] && $viewCategory != 'Review' ? 'selected' : '' }}>
                                                        {{ $item['value'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="text-danger error-message"></div>
                                        </div>
                                    </div>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    @endif
</div>