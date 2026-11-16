{{--
    Attachment satu kotak bulan di grid Achievement Tracking (form appraisal).

    Tampilan mengikuti halaman Update Achievement: tombol FILE untuk unggah dan tombol VIEW
    untuk file yang sudah ada. Hanya bulan yang masuk review period ($canAttach) bisa diunggah.

    $attachmentUrl, $canAttach, $fileId, $kpiIndex, $monthNum, $monthLabel
--}}
<div class="ach-file">
    @if ($attachmentUrl)
        <div class="position-relative" data-attachment-wrapper>
            <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener"
                class="ach-file-btn ach-file-view"
                title="{{ __('View attachment') }} ({{ $monthLabel }})">
                <i class="ri-file-line"></i> {{ __('VIEW') }}
            </a>
            @if ($canAttach)
                <button type="button"
                    class="ach-file-remove ach-remove-attachment"
                    data-kpi-index="{{ $kpiIndex }}"
                    data-month="{{ $monthNum }}"
                    title="{{ __('Remove attachment') }}">
                    <i class="ri-close-line"></i>
                </button>
            @endif
        </div>
    @endif

    @if ($canAttach)
        <input type="file"
            id="{{ $fileId }}"
            name="{{ \App\Services\AppraisalAchievementAttachmentService::INPUT }}[{{ $kpiIndex }}][{{ $monthNum }}]"
            class="d-none ach-file-input"
            data-target="label-{{ $fileId }}"
            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.jpg,.jpeg,.png">
        <label for="{{ $fileId }}" id="label-{{ $fileId }}"
            class="ach-file-btn {{ $attachmentUrl ? 'mt-1' : '' }}"
            title="{{ __('Attach supporting document') }} ({{ $monthLabel }}) &mdash; max 2MB">
            <i class="ri-attachment-2 icon-attach"></i>
            <span class="text-attach">{{ $attachmentUrl ? __('REPLACE') : __('FILE') }}</span>
        </label>
    @endif
</div>
