{{--
    The label/value block every message uses to state its facts.

    Each row: ['label' => '', 'value' => '', 'url' => null, 'accent' => false].
    A row with 'url' renders its value as a link; 'accent' prints it in brand red, a size
    up, for the one number a message is really about. Callers pass null for rows that have
    no data and those are dropped here, so a row only exists when it has something to say.

    Rows stack label-over-value below 620px.
--}}
@props(['rows' => [], 'space' => 32])
@php
    $rows = array_values(array_filter($rows));
@endphp
@if(count($rows))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    @if($space)
    <tr><td style="height: {{ $space }}px; line-height: {{ $space }}px; font-size: 0;">&nbsp;</td></tr>
    @endif
    <tr>
        <td>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border: 1px solid #ebedf0; border-radius: 12px;">
                <tr>
                    <td style="padding: 8px 24px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            @foreach($rows as $i => $row)
                            @php
                                $divider = $i > 0 ? 'border-top: 1px solid #ebedf0;' : '';
                                $accent = $row['accent'] ?? false;
                            @endphp
                            <tr>
                                <td class="sm-stack sm-stack-label" style="padding: 14px 0; {{ $divider }} color: #8b919c; font-size: 12px; font-weight: 600; letter-spacing: 0.6px; text-transform: uppercase; white-space: nowrap;">
                                    {{ $row['label'] }}
                                </td>
                                <td class="sm-stack" align="right" style="padding: 14px 0; {{ $divider }} color: {{ $accent ? '#ed2537' : '#0f1115' }}; font-size: {{ $accent ? '18' : '15' }}px; font-weight: {{ $accent ? '700' : '600' }}; text-align: right; word-break: break-word;">
                                    @if(!empty($row['url']))
                                        <a href="{{ $row['url'] }}" style="color: #ed2537; text-decoration: none;">{{ $row['value'] }}</a>
                                    @else
                                        {{ $row['value'] }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endif
