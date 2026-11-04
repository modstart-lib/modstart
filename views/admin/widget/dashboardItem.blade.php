<?php
// Icon accent color: fall back to the theme primary so every dashboard card
// shares one accent instead of random saturated colors.
$itemAccent = $color ? $color : 'var(--color-primary)';
$itemAccentBg = $color ? $color . '1a' : 'var(--color-primary-light-bg)';
$itemCount = count($value);
$itemFirst = $itemCount > 0 ? $value[0] : null;
$itemRest = $itemCount > 1 ? array_slice($value, 1) : array();
// Dense cards (4+ metrics) need tighter type to keep secondary rows readable.
$itemMainClass = $itemCount >= 4 ? 'tw-text-xl' : 'tw-text-2xl';
$itemMetaClass = $itemCount >= 4 ? 'tw-text-xs' : 'tw-text-xs';
$itemMetaGap = $itemCount >= 4 ? '.5rem' : '.75rem';
?>
<div class="ub-content-box margin-bottom tw-h-36 tw-flex tw-flex-col tw-overflow-hidden">
    <div class="tw-flex tw-items-center tw-gap-2 tw-pb-3 tw-flex-shrink-0"
         style="border-bottom:1px solid var(--color-body-line);">
        @if(!empty($icon))
            <span class="tw-inline-flex tw-items-center tw-justify-center tw-w-8 tw-h-8 tw-flex-shrink-0"
                  style="border-radius:var(--size-radius-sm);background:{{ $itemAccentBg }};color:{{ $itemAccent }};">
                <i class="{{ $icon }}" style="font-size:1rem;line-height:1;"></i>
            </span>
        @endif
        <span class="tw-flex-grow tw-font-medium tw-truncate"
              style="color:var(--color-heading);">{{ $title }}</span>
    </div>
    <div class="tw-flex-1 tw-flex tw-flex-col tw-justify-center tw-min-w-0">
        @if($itemFirst)
            <div class="tw-flex tw-items-baseline tw-min-w-0" style="gap:.375rem;">
                @if(!empty($itemFirst['url']))
                    <a class="tw-truncate tw-font-semibold tw-tabular-nums {{ $itemMainClass }}"
                       style="color:var(--color-heading);"
                       data-tab-open data-tab-title="加载中..."
                       href="{{ $itemFirst['url'] }}">{{ \ModStart\Admin\Widget\DashboardItem::formatValue($itemFirst["value"]) }}</a>
                @else
                    <span class="tw-truncate tw-font-semibold tw-tabular-nums {{ $itemMainClass }}"
                          style="color:var(--color-heading);">{{ \ModStart\Admin\Widget\DashboardItem::formatValue($itemFirst["value"]) }}</span>
                @endif
                <span class="tw-text-xs tw-flex-shrink-0" style="color:var(--color-tertiary);">{{ $itemFirst['title'] }}</span>
                @if(!empty($itemFirst['delta']))
                    <span class="tw-text-xs tw-font-medium tw-tabular-nums tw-flex-shrink-0"
                          style="color:{{ \ModStart\Admin\Widget\DashboardItem::deltaTone($itemFirst["delta"]) }};">{{ $itemFirst['delta'] }}</span>
                @endif
            </div>
            @if(count($itemRest) > 0)
                <div class="tw-flex tw-flex-wrap tw-items-baseline tw-mt-1 tw-min-w-0"
                     style="gap:.125rem {{ $itemMetaGap }};">
                    @foreach($itemRest as $v)
                        <span class="tw-inline-flex tw-items-baseline tw-flex-shrink-0" style="gap:.25rem;">
                            <span class="{{ $itemMetaClass }} tw-truncate" style="color:var(--color-tertiary);">{{ $v['title'] }}</span>
                            @if(!empty($v['url']))
                                <a class="{{ $itemMetaClass }} tw-font-medium tw-tabular-nums"
                                   style="color:var(--color-heading);"
                                   data-tab-open data-tab-title="加载中..."
                                   href="{{ $v['url'] }}">{{ \ModStart\Admin\Widget\DashboardItem::formatValue($v["value"]) }}</a>
                            @else
                                <b class="{{ $itemMetaClass }} tw-font-medium tw-tabular-nums"
                                   style="color:var(--color-heading);">{{ \ModStart\Admin\Widget\DashboardItem::formatValue($v["value"]) }}</b>
                            @endif
                            @if(!empty($v['delta']))
                                <span class="tw-text-xs tw-tabular-nums"
                                      style="color:{{ \ModStart\Admin\Widget\DashboardItem::deltaTone($v["delta"]) }};">{{ $v['delta'] }}</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</div>
