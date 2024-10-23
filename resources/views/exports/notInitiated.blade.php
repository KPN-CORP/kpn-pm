<table>
    <thead>
    <tr>
        <th>Employee ID</th>
        <th>Name</th>
        <th>Designation</th>
        <th>Business Unit</th>
        <th>Company</th>
        <th>Location</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($data as $row)
        <tr>
            <td>{{ $row->employee_id }}</td>
            <td>{{ $row->employee->fullname }}</td>
            <td>{{ $row->employee->designation_name }}</td>
            <td>{{ $row->employee->group_company }}</td>
            <td>{{ $row->employee->contribution_level_code }}</td>
            <td>{{ $row->employee->office_area }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
