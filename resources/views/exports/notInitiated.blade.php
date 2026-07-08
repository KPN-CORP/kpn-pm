<table>
    <thead>
    <tr>
        <th>Employee_ID</th>
        <th>Employee_Name</th>
        <th>Designation</th>
        <th>Business Unit</th>
        <th>Category</th>
        <th>KPI</th>
        <th>Description</th>
        <th>Target</th>
        <th>UoM</th>
        <th>Weightage</th>
        <th>Type</th>
        <th>Review Period</th>
        <th>Calculation Method</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($data as $row)
        <tr>
            <td>{{ $row->employee_id }}</td>
            <td>{{ $row->employee->fullname }}</td>
            <td>{{ $row->employee->designation_name }}</td>
            <td>{{ $row->employee->group_company }}</td>
            <td>Goals</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    @endforeach
    </tbody>
</table>
