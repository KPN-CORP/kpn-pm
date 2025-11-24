<table>
    <thead>
    <tr>
        <th>Employee ID</th>
        <th>Employee Name</th>
        <th>Category</th>
        <th>KPI</th>
        <th>Target</th>
        <th>Uom</th>
        <th>Weightage</th>
        <th>Type</th>
        <th>Form Status</th>
        <th>Approval Status</th>
        <th>Current Approver</th>
        <th>Current Approver ID</th>
        <th>Initiated By</th>
        <th>Initiated By ID</th>
    </tr>
    </thead>
    <tbody>
        @foreach ($data as $row)
             @php
                $formData = $row->goal && $row->goal->form_data 
                            ? json_decode($row->goal->form_data, true) 
                            : null;
            @endphp
            @if ($formData)
                @foreach ($formData as $item)
                    <tr>
                        <td>{{ $row->employee_id }}</td>
                        <td>{{ $row->employee->fullname }}</td>
                        <td>{{ $row->goal->category }}</td>
                        <td>{{ $item['kpi'] }}</td>
                        <td>{{ $item['target'] }}</td>
                        <td>{{ $item['uom']==='Other' ? $item['custom_uom'] : $item['uom'] }}</td>
                        <td>{{ $item['weightage'] }}</td>
                        <td>{{ $item['type'] }}</td>
                        <td>{{ $row->goal->form_status }}</td>
                        <td>{{ $row->status=='Pending'? ($row->sendback_to ? 'Waiting For Revision' : ($row->goal->form_status=='Draft'? 'Not Started' : 'Waiting For Approval')) : $row->status }}</td>
                        <td>{{ $row->status=='Sendback' && $row->sendback_to == $row->employee_id || $row->goal->form_status=='Draft' ? '-' : $row->manager->fullname }}</td>
                        <td>{{ $row->manager->employee_id }}</td>
                        <td>{{ $row->initiated->name }}</td>
                        <td>{{ $row->initiated->employee_id }}</td>
                    </tr>
                @endforeach
            @endif
        @endforeach
    </tbody>
</table>
