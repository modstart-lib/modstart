@extends('modstart::admin.frame')

@section($_tabSectionName)
    <div class="{{$_isTab?'tw-p-3':''}}">
        {!! $content !!}
    </div>
@endsection
