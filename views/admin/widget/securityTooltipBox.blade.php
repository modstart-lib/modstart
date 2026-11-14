<?php
// Collect every security warning first, so they can be rendered as one
// collapsible bar instead of stacking several full-height alerts on the page.
// Items only carry the description: the "Security Warning" prefix lives in the
// bar title, so repeating it on every row would triple the same phrase.
$securityItems = array();
if (!file_exists(storage_path('install.lock'))) {
    $securityItems[] = array(
        'text' => L('SystemInstallLockMissing'),
        'mode' => 'ajax',
        'url' => action('\ModStart\Admin\Controller\SystemController@securityFix', array('type' => 'installLock')),
        'label' => L('ProcessNow'),
    );
}
if (file_exists(public_path('install.php'))) {
    $securityItems[] = array(
        'text' => L('InstallPhpNotDeleted'),
        'mode' => 'ajax',
        'url' => action('\ModStart\Admin\Controller\SystemController@securityFix', array('type' => 'installScript')),
        'label' => L('ProcessNow'),
    );
}
if (config('env.APP_DEBUG', false)) {
    $securityItems[] = array(
        'text' => L('SystemInDebugMode'),
        'mode' => 'ajax',
        'url' => action('\ModStart\Admin\Controller\SystemController@securityFix', array('type' => 'appDebug')),
        'label' => L('ProcessNow'),
    );
}
if (in_array(config('env.ADMIN_PATH'), array('/admin/'))) {
    $securityItems[] = array(
        'text' => L('AdminUrlEasyToAttack'),
        'mode' => 'dialog',
        'url' => action('\ModStart\Admin\Controller\SystemController@securityFix', array('type' => 'adminPath')),
        'label' => L('ProcessNow'),
    );
}
if (\Illuminate\Support\Facades\Session::get('_adminUserPasswordWeak', false)) {
    $securityItems[] = array(
        'text' => L('PasswordIsWeak'),
        'mode' => 'link',
        'url' => action('\ModStart\Admin\Controller\ProfileController@changePassword'),
        'label' => L('ProcessNow'),
    );
}
if (config('env.APP_KEY') == 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA') {
    $securityItems[] = array(
        'text' => L('IsDefault', 'APP_KEY'),
    );
}
if (config('env.ENCRYPT_KEY') == 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA') {
    $securityItems[] = array(
        'text' => L('IsDefault', 'ENCRYPT_KEY'),
    );
}
$securityItemCount = count($securityItems);
// Keep at most three items visible by default: below that threshold hiding the
// actual risks behind a collapsed bar costs more than the space it saves.
$securityNoticeOpen = $securityItemCount > 0 && $securityItemCount <= 3;
?>
<div {!! $attributes !!}>
    <div class="ub-alert danger tw-hidden" data-system-notice></div>
    @if($queueDelaySize>0)
        <div class="ub-alert danger" style="padding:.5rem .75rem;">
            <i class="iconfont icon-warning"></i>
            {{L('SystemWarning')}}: {{ L('QueuePendingJobs',$queueDelaySize) }}
            <a href="https://modstart.com/doc" target="_blank" rel="noreferrer">{{L('ViewNow')}}</a>
        </div>
    @endif
    @if($scheduleRunLastRun < time() - 24*3600)
        <div class="ub-alert danger" style="padding:.5rem .75rem;">
            <i class="iconfont icon-warning"></i>
            {{L('SystemWarning')}}: {{ L('ScheduleTasksNotRun') }}
            ( {{date('Y-m-d H:i:s',$scheduleRunLastRun)}} )
            <a href="https://modstart.com/doc" target="_blank" rel="noreferrer">{{L('ViewNow')}}</a>
        </div>
    @endif
    @if (\ModStart\Admin\Auth\AdminPermission::isDemo())
        <div class="ub-alert danger" style="padding:.5rem .75rem;">
            <i class="iconfont icon-warning"></i>
            {{ L('DemoUserForbidden') }}
        </div>
    @endif
    @if($securityItemCount == 1)
        <div class="ub-alert warning ub-security-notice-single" style="padding:.5rem .75rem;">
            <span class="tw-flex tw-items-center" style="gap:.5rem;">
                <i class="iconfont icon-warning tw-flex-shrink-0" style="line-height:1.5;"></i>
                <span class="ub-security-notice-text">
                    {{ L('SecurityWarning') }}: {{ $securityItems[0]['text'] }}
                </span>
                @if(!empty($securityItems[0]['mode']))
                    @if($securityItems[0]['mode'] == 'ajax')
                        <a class="ub-security-notice-action" href="javascript:;" data-ajax-request-loading
                           data-ajax-request="{{ $securityItems[0]['url'] }}">{{ $securityItems[0]['label'] }}</a>
                    @elseif($securityItems[0]['mode'] == 'dialog')
                        <a class="ub-security-notice-action" href="javascript:;" data-dialog-request="{{ $securityItems[0]['url'] }}">{{ $securityItems[0]['label'] }}</a>
                    @else
                        <a class="ub-security-notice-action" href="{{ $securityItems[0]['url'] }}">{{ $securityItems[0]['label'] }}</a>
                    @endif
                @endif
            </span>
        </div>
    @elseif($securityItemCount > 1)
        <details class="ub-alert warning ub-security-notice" style="padding:.5rem .75rem;" @if($securityNoticeOpen) open @endif>
            <summary class="tw-flex tw-items-center tw-cursor-pointer"
                     style="gap:.375rem;list-style:none;">
                <i class="iconfont icon-warning"></i>
                <span class="ub-security-notice-title">
                    {{ L('SecurityWarning') }}: {{ L('SecurityItemsPending', $securityItemCount) }}
                </span>
                <i class="iconfont icon-angle-down ub-security-notice-arrow"></i>
            </summary>
            <div class="ub-security-notice-list">
                @foreach($securityItems as $securityItem)
                    <div class="ub-security-notice-item">
                        <span class="ub-security-notice-dot"></span>
                        <span class="ub-security-notice-text">{{ $securityItem['text'] }}</span>
                        @if(!empty($securityItem['mode']))
                            @if($securityItem['mode'] == 'ajax')
                                <a class="ub-security-notice-action" href="javascript:;" data-ajax-request-loading
                                   data-ajax-request="{{ $securityItem['url'] }}">{{ $securityItem['label'] }}</a>
                            @elseif($securityItem['mode'] == 'dialog')
                                <a class="ub-security-notice-action" href="javascript:;" data-dialog-request="{{ $securityItem['url'] }}">{{ $securityItem['label'] }}</a>
                            @else
                                <a class="ub-security-notice-action" href="{{ $securityItem['url'] }}">{{ $securityItem['label'] }}</a>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </details>
    @endif
</div>
<style>
    .ub-security-notice > summary::-webkit-details-marker {
        display: none;
    }

    /* The summary is the only interactive part of the collapsed bar: give it a
       visible focus ring so it can be reached with the keyboard. */
    .ub-security-notice > summary:focus-visible {
        outline: none;
        box-shadow: 0 0 0 0.1875rem var(--color-warning-soft);
        border-radius: var(--size-radius-sm);
    }

    .ub-security-notice-title {
        min-width: 0;
        font-weight: 500;
        overflow-wrap: break-word;
        word-break: break-word;
    }

    /* The whole summary row is clickable: hint it on hover */
    @media (hover: hover) {
        .ub-security-notice > summary {
            transition: background-color 0.15s var(--ease-out, ease);
        }

        .ub-security-notice > summary:hover {
            background-color: var(--color-warning-soft);
            border-radius: var(--size-radius-sm);
        }
    }

    .ub-security-notice-arrow {
        transition: transform 0.2s var(--ease-out, ease);
    }

    .ub-security-notice[open] .ub-security-notice-arrow {
        transform: rotate(180deg);
    }

    .ub-security-notice-list {
        margin-top: 0.5rem;
        padding-top: 0.375rem;
        border-top: 0.0625rem solid var(--color-warning-soft);
    }

    .ub-security-notice-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.3125rem 0.375rem;
        border-radius: var(--size-radius-sm);
        transition: background-color 0.15s var(--ease-out, ease);
    }

    /* Dot instead of a middle-dot glyph: it stays crisp at small sizes and is
       centred against the row (the row height is driven by the action button,
       so anchoring the dot to flex-start left it visually 4px too high) */
    .ub-security-notice-dot {
        flex-shrink: 0;
        width: 0.375rem;
        height: 0.375rem;
        border-radius: 50%;
        background: var(--color-warning);
    }

    .ub-security-notice-text {
        flex: 0 1 auto;
        min-width: 0;
        line-height: 1.5;
        overflow-wrap: break-word;
        word-break: break-word;
    }

    /* Compact outline button: keeps the action next to the text it belongs to
       instead of pushing it to the far right edge of a wide screen. */
    .ub-security-notice-action {
        position: relative;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: 0.25rem;
        min-height: 1.75rem;
        padding: 0 0.5rem;
        border: 0.0625rem solid var(--color-warning);
        border-radius: var(--size-radius-sm);
        color: var(--color-warning-dark);
        background-color: transparent;
        font-size: 0.75rem;
        line-height: 1;
        white-space: nowrap;
        text-decoration: none;
        transition: var(--transition);
    }

    .ub-security-notice-action:focus-visible {
        outline: none;
        box-shadow: 0 0 0 0.1875rem var(--color-warning-soft);
        background-color: var(--color-content-bg);
    }

    /* Hovering the row lights up the whole line, so the action button is
       unambiguously attached to its own warning. */
    @media (hover: hover) {
        .ub-security-notice-item:hover {
            background-color: var(--color-warning-soft);
        }

        .ub-security-notice-item:hover .ub-security-notice-action {
            background-color: var(--color-warning);
            color: #FFFFFF;
        }
    }

    @media (hover: none) {
        .ub-security-notice-action:active {
            background-color: var(--color-warning);
            color: #FFFFFF;
        }
    }

    /* Narrow screens: let the action wrap under the text, right aligned, with a
       44px tall touch target instead of squeezing the description. */
    @media (max-width: 47.9375rem) {
        .ub-security-notice-item {
            flex-wrap: wrap;
        }

        .ub-security-notice-action {
            margin-left: auto;
            min-height: 2rem;
        }

        /* Keep the visual button compact while extending the touch target to
           44px (32px button + 6px of transparent hit area on each side) */
        .ub-security-notice-action::before {
            content: '';
            position: absolute;
            top: -0.375rem;
            bottom: -0.375rem;
            left: -0.5rem;
            right: -0.5rem;
        }

        .ub-security-notice-single .ub-security-notice-action {
            margin-left: 0;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .ub-security-notice-arrow,
        .ub-security-notice-item,
        .ub-security-notice-action {
            transition: none;
        }
    }
</style>
