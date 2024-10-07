<div class="form-group mb-4">
    <input type="hidden" name="formData[{{ $formIndex }}][formName]" value="{{ $name }}">
    @if(is_array($data))
        @foreach($data as $index => $dataItem)
        <div class="row fs-14 gy-2">
            <div class="col-lg">
                    <h5><strong>{{ $dataItem['title'] }}</strong></h5>
                    <p class="mb-2"><b>{{ $dataItem['description'] }}</b></p>
                    @if(is_array($dataItem['items']))
                        <ul>
                            @foreach($dataItem['items'] as $indexItem => $item)
                                <li>
                                    <div class="row mb-2 align-items-center">
                                        <div  class="col-md">
                                            <div class="mb-2 mb-lg-auto">
                                                <span>{{ $item }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-auto justify-content-end">
                                            <select class="form-select form-select-sm" name="formData[{{ $formIndex }}][{{ $index }}][{{ $indexItem }}][score]" id="score" required>
                                                <option value="">select</option>
                                                <option value="5" {{ isset($dataItem['score'][$indexItem]) && $dataItem['score'][$indexItem] == "5" ? 'selected' : '' }}>Expert</option>
                                                <option value="4" {{ isset($dataItem['score'][$indexItem]) && $dataItem['score'][$indexItem] == "4" ? 'selected' : '' }}>Advanced</option>
                                                <option value="3" {{ isset($dataItem['score'][$indexItem]) && $dataItem['score'][$indexItem] == "3" ? 'selected' : '' }}>Practitioner</option>
                                                <option value="2" {{ isset($dataItem['score'][$indexItem]) && $dataItem['score'][$indexItem] == "2" ? 'selected' : '' }}>Comprehension</option>
                                                <option value="1" {{ isset($dataItem['score'][$indexItem]) && $dataItem['score'][$indexItem] == "1" ? 'selected' : '' }}>Basic</option>
                                            </select>
                                            <div class="text-danger error-message"></div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
            </div>
        </div>
        <hr/>
        @endforeach
    @endif
</div>