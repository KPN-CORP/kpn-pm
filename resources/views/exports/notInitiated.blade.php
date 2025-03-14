<table>
    <thead>
    <tr>
        <th>Employee_ID</th>
        <th>Employee_Name</th>
        <th>KPI</th>
        <th>Target</th>
        <th>UoM</th>
        <th>Weightage</th>
        <th>Type</th>
        <th>Description</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($data as $row)
        <tr>
            <td>{{ $row->employee_id }}</td>
            <td>{{ $row->employee->fullname }}</td>
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
