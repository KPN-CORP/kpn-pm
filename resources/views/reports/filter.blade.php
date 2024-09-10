<div id="modalFilter" class="modal fade"  aria-labelledby="modalFilterLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-right">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Filters</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <form id="report_filter" action="{{ route('reports.content') }}" method="POST">
            @csrf
                <div class="mt-2">
                    <div class="form-group">
                        <label class="form-label" for="report_type">Report Type:</label>
                        <select class="form-select" name="report_type" id="report_type">
                        <option value="">select report</option>
                        <option value="Goal">Goal</option>
                        </select>
                    </div> 
                </div>
            </form>
            </div>
            <div class="modal-footer">
                <a class="btn btn-outline-secondary me-3" data-dismiss="modal">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary" form="report_filter">Apply</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>