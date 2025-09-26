@component('mail::message')
# Reminder Performance Appraisal

Halo {{ $employee->name }},

{!! $reminder->messages !!}

Terima kasih,<br>
**HR Department**
@endcomponent