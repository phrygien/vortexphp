@if(!empty($flashSuccess))
    <div class="alert alert-success">
        {{ $flashSuccess }}
    </div>
@endif

@if(!empty($flashError))
    <div class="alert alert-error">
        {{ $flashError }}
    </div>
@endif
