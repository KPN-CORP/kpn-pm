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
        @foreach($row->subordinates as $subordinate)
             @php
                $formData = $subordinate->goal && $subordinate->goal->form_data 
                            ? json_decode($subordinate->goal->form_data, true) 
                            : null;
            @endphp
        @endforeach
            @if ($formData)
                @foreach ($formData as $item)
                    <tr>
                        <td>{{ $subordinate->employee_id }}</td>
                        <td>{{ $subordinate->employee->fullname }}</td>
                        <td>{{ $subordinate->goal->category }}</td>
                        <td>{{ $item['kpi'] }}</td>
                        <td>{{ $item['target'] }}</td>
                        <td>{{ $item['uom']==='Other' ? $item['custom_uom'] : $item['uom'] }}</td>
                        <td>{{ $item['weightage'] }}</td>
                        <td>{{ $item['type'] }}</td>
                        <td>{{ $subordinate->goal->form_status }}</td>
                        <td>{{ $subordinate->status=='Pending'? ($subordinate->sendback_to ? 'Waiting For Revision' : ($subordinate->goal->form_status=='Draft'? 'Not Started' : 'Waiting For Approval')) : $subordinate->status }}</td>
                        <td>{{ $subordinate->status=='Sendback' && $subordinate->sendback_to == $subordinate->employee_id || $subordinate->goal->form_status=='Draft' ? '-' : $subordinate->manager->fullname }}</td>
                        <td>{{ $subordinate->manager->employee_id }}</td>
                        <td>{{ $subordinate->initiated->name }}</td>
                        <td>{{ $subordinate->initiated->employee_id }}</td>
                    </tr>
                @endforeach
            @endif
        @endforeach
    </tbody>
</table>
