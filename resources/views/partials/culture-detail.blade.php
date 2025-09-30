@foreach ($formData['formData'] as $form)
    @if ($form['formName'] === 'Culture')
        @foreach ($form as $key => $item)
            @if (is_numeric($key))
                <div class="{{ $loop->last ? '' : 'border-bottom' }} mb-3">
                    @if (isset($item['title']))
                        <h5 class="mb-3"><u>{{ $item['title'] }}</u></h5>
                    @endif
                    @foreach ($item as $subKey => $subItem)
                        @if (is_array($subItem))
                            <ul class="ps-3">
                                <li>
                                    <div>
                                        @if (isset($subItem['formItem']))
                                            <p class="mb-1">{!! $subItem['formItem'] !!}</p>
                                        @endif
                                        @if (isset($subItem['score']))
                                            <p><strong>Score:</strong> {{ $subItem['score'] }}</p>
                                        @endif
                                    </div>
                                </li>
                            </ul>
                        @endif
                    @endforeach
                </div>
            @endif
        @endforeach
    @endif
@endforeach