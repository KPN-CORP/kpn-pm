<div class="form-group mb-4">
    <input type="hidden" name="formData[{{ $formIndex }}][formName]" value="{{ $name }}">
    @if(is_array($data))
        @foreach($data as $index => $dataItem)
        <div class="row fs-16">
            <div class="col-lg">
                <div class="card" style="background-color: #F8F9FA">
                    <div class="card-body">
                        <div class="mb-4">
                            <h4 class="mb-3"><strong>{{ $dataItem['title'] }}</strong></h4>
                            <p class="mb-3"><strong>{{ $dataItem['description'] }}</strong></p>
                            @if(is_array($dataItem['items']))
                                <ul>
                                    @foreach($dataItem['items'] as $indexItem => $item)
                                        <li>
                                            <div class="row mb-3 align-items-center">
                                                <div  class="col-md">
                                                    <div class="mb-2 mb-lg-auto">
                                                        <span>{{ $item }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-auto justify-content-end">
                                                    <select class="form-select" name="formData[{{ $formIndex }}][{{ $index }}][{{ $indexItem }}][score]" id="score" required {{ $viewCategory == "detail" ? 'disabled' : '' }}>
                                                        <option value="">Please select</option>
                                                        <option value="5" {{ isset($dataItem['score'][$indexItem]) && $dataItem['score'][$indexItem] == "5" && $viewCategory == "detail" ? 'selected' : '' }}>Expert</option>
                                                        <option value="4" {{ isset($dataItem['score'][$indexItem]) && $dataItem['score'][$indexItem] == "4" && $viewCategory == "detail" ? 'selected' : '' }}>Advanced</option>
                                                        <option value="3" {{ isset($dataItem['score'][$indexItem]) && $dataItem['score'][$indexItem] == "3" && $viewCategory == "detail" ? 'selected' : '' }}>Practitioner</option>
                                                        <option value="2" {{ isset($dataItem['score'][$indexItem]) && $dataItem['score'][$indexItem] == "2" && $viewCategory == "detail" ? 'selected' : '' }}>Comprehension</option>
                                                        <option value="1" {{ isset($dataItem['score'][$indexItem]) && $dataItem['score'][$indexItem] == "1" && $viewCategory == "detail" ? 'selected' : '' }}>Basic</option>
                                                    </select>
                                                    <div class="text-danger error-message fs-14"></div>
                                                </div>
                                            </div>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    @endif
</div>