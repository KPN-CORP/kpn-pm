<table>
    <thead>

    {{-- Row 1 --}}
    <tr>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>

        <th colspan="12">Achievement</th>
    </tr>

    {{-- Row 2 --}}
    <tr>
        <th>No</th>
        <th>Employee ID</th>
        <th>Employee Name</th>
        <th>KPI</th>
        <th>KPI Descriptions</th>
        <th>Annual Target</th>
        <th>UoM</th>
        <th>Weightage</th>
        <th>Type</th>
        <th>Review Period</th>
        <th>Calculation Method</th>

        <th>Jan</th>
        <th>Feb</th>
        <th>Mar</th>
        <th>Apr</th>
        <th>May</th>
        <th>Jun</th>
        <th>Jul</th>
        <th>Aug</th>
        <th>Sep</th>
        <th>Oct</th>
        <th>Nov</th>
        <th>Dec</th>
    </tr>

    </thead>

    <tbody>

    @foreach ($data as $index => $row)

        <tr>
            <td>{{ $loop->iteration }}</td>

            <td>{{ $row->employee_id }}</td>

            <td>{{ $row->employee->fullname ?? '-' }}</td>

            <td>{{ $row->kpi }}</td>

            <td>{{ $row->description }}</td>

            <td>{{ $row->target }}</td>

            <td>{{ $row->custom_uom ?? $row->uom }}</td>

            <td>{{ $row->weightage }}</td>

            <td>{{ $row->type }}</td>

            <td>{{ $row->review_period }}</td>

            <td>{{ $row->calculation_method }}</td>

            <td>{{ $row->jan }}</td>
            <td>{{ $row->feb }}</td>
            <td>{{ $row->mar }}</td>
            <td>{{ $row->apr }}</td>
            <td>{{ $row->may }}</td>
            <td>{{ $row->jun }}</td>
            <td>{{ $row->jul }}</td>
            <td>{{ $row->aug }}</td>
            <td>{{ $row->sep }}</td>
            <td>{{ $row->oct }}</td>
            <td>{{ $row->nov }}</td>
            <td>{{ $row->dec }}</td>

        </tr>

    @endforeach

    </tbody>
</table>