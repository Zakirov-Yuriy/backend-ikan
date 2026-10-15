<!DOCTYPE html>
<html lang="ru">
<head><meta charset="utf-8"><title>Новая заявка с сайта ИКАН</title></head>
<body style="font-family: Arial, sans-serif; font-size: 15px; line-height: 1.5; color: #102b3b;">
@if($test)
    <p style="color: #8a6d1d; background: #fff6d6; padding: 8px 12px; border-radius: 6px;">Это проверочное письмо из админки. Настоящей заявки нет.</p>
@endif
<h2 style="margin: 0 0 12px;">Новая заявка с сайта ИКАН</h2>
<table cellpadding="4" cellspacing="0" style="border-collapse: collapse;">
    <tr><td style="color: #667781;">Имя</td><td><strong>{{ $lead->name }}</strong></td></tr>
    <tr><td style="color: #667781;">Телефон</td><td><a href="tel:{{ preg_replace('/[^\d+]/', '', $lead->phone) }}">{{ $lead->phone }}</a></td></tr>
    @if($lead->interest)
        <tr><td style="color: #667781; vertical-align: top;">Что интересует</td><td>{{ $lead->interest }}</td></tr>
    @endif
    <tr><td style="color: #667781;">Откуда</td><td>{{ $lead->sourceLabel() }}@if($lead->page), страница {{ $lead->page }}@endif</td></tr>
    <tr><td style="color: #667781;">Когда</td><td>{{ $lead->created_at?->format('d.m.Y H:i') }}</td></tr>
</table>
<p style="margin-top: 16px;"><a href="{{ route('admin.leads.index') }}">Открыть заявки в админке</a></p>
</body>
</html>
