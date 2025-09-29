@extends('layouts.app')

@section('content')
  <h2 class="mb-16">{{ __('Create case') }}</h2>
  @if ($errors->any())
    <div class="alert alert-error mb-16">
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form class="card" method="POST" action="{{ route('cases.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="grid grid-2">
      <div class="field">
        <label class="label">{{ __('Title') }}</label>
        <input class="input" name="title" value="{{ old('title') }}" required>
      </div>
      <div class="field">
        <label class="label">{{ __('Assign to executor') }}</label>
        <select class="input" name="executor_id">
          <option value="">{{ __('Unassigned') }}</option>
          @foreach($executors as $executor)
            <option value="{{ $executor->id }}" @selected(old('executor_id') == $executor->id)>{{ $executor->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label class="label">{{ __('Claimant') }}</label>
        <input class="input" name="claimant_name" value="{{ old('claimant_name') }}">
      </div>
      <div class="field">
        <label class="label">{{ __('Debtor') }}</label>
        <input class="input" name="debtor_name" value="{{ old('debtor_name') }}">
      </div>
      <div class="field">
        <label class="label">{{ __('Deadline') }}</label>
        <input class="input" type="date" name="deadline_at" value="{{ old('deadline_at') }}">
      </div>
    </div>

    <div class="field">
      <label class="label">{{ __('Description') }}</label>
      <textarea class="input" name="description" rows="4">{{ old('description') }}</textarea>
    </div>

    <div class="field">
      <label class="label">{{ __('Attachments') }}</label>
      <input class="input" type="file" name="documents[]" multiple>
      <div class="help">{{ __('Accepted formats: PDF, JPG, PNG up to 20MB each.') }}</div>
    </div>

    <div class="center">
      <button class="btn btn-primary">{{ __('Create') }}</button>
      <a class="btn" style="border:1px solid #cbd5e1" href="{{ route('cases.index') }}">{{ __('Cancel') }}</a>
    </div>
  </form>
@endsection
