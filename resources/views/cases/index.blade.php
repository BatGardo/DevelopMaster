@extends('layouts.app')

@section('content')
  <h2 class="mb-16">{{ __('Case portfolio') }}</h2>

  <div class="card mb-16">
    @php $currentRegion = $selectedRegion ?? request('region'); @endphp
    <form method="GET" class="grid grid-4 gap-12">
      <div>
        <label class="label">{{ __('Executor') }}</label>
        <select class="input" name="executor">
          <option value="">{{ __('Any executor') }}</option>
          @foreach($executors as $ex)
            <option value="{{ $ex->id }}" @selected(request('executor') == $ex->id)>{{ $ex->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="label">{{ __('Status') }}</label>
        <select class="input" name="status">
          <option value="">{{ __('Any status') }}</option>
          @foreach(\App\Models\CaseModel::statusOptions() as $key => $label)
            <option value="{{ $key }}" @selected(request('status') == $key)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="label">{{ __('Region') }}</label>
        <select class="input" name="region">
          <option value="">{{ __('Any region') }}</option>
          @if(!empty($hasUnspecifiedRegion))
            <option value="__null__" @selected($currentRegion === '__null__')>{{ __('Not specified') }}</option>
          @endif
          @foreach(($regions ?? []) as $region)
            <option value="{{ $region }}" @selected($currentRegion === $region)>{{ $region }}</option>
          @endforeach
        </select>
      </div>
      <div style="align-self:end; display:flex; gap:12px; flex-wrap:wrap;">
        <button class="btn">{{ __('Apply filters') }}</button>
        @can('create-case')
          <a class="btn btn-primary" href="{{ route('cases.create') }}">{{ __('Create case') }}</a>
        @endcan
      </div>
    </form>
  </div>

  <div class="card">
    <table class="table">
      <thead>
      <tr>
        <th><a href="{{ route('cases.index', array_merge(request()->all(), ['sort' => 'id', 'direction' => $sort === 'id' && $direction === 'asc' ? 'desc' : 'asc'])) }}">ID</a></th>
        <th>{{ __('Title') }}</th>
        <th>{{ __('Executor') }}</th>
        <th>{{ __('Region') }}</th>
        <th>{{ __('Status') }}</th>
        <th>{{ __('Deadline') }}</th>
        <th></th>
      </tr>
      </thead>
      <tbody>
      @forelse($cases as $case)
        <tr>
          <td>{{ $case->id }}</td>
          <td>{{ \Illuminate\Support\Str::limit($case->title, 60) }}</td>
          <td>{{ $case->executor?->name ?? __('Unassigned') }}</td>
          <td>{{ $case->region_label }}</td>
          <td><span class="badge">{{ $case->status_label }}</span></td>
          <td>{{ $case->deadline_at?->format('Y-m-d') ?? __('N/A') }}</td>
          <td><a class="btn" href="{{ route('cases.show', $case) }}">{{ __('Open') }}</a></td>
        </tr>
      @empty
        <tr><td colspan="7" class="help">{{ __('No cases satisfy your filters yet.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
    <div class="mt-20">{{ $cases->links() }}</div>
  </div>
@endsection