@extends('layouts.master')

@section('title') Route Templates @endsection
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-sm-8">
                        <div class="text-sm-end">
                             <h4 class="card-title">Route Templates</h4>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-sm-end">
                            <button type="button" class="btn btn-success waves-effect waves-light mb-2 me-2" data-bs-toggle="modal" data-bs-target="#createTemplateModal"><i class="mdi mdi-plus me-1"></i> Add New Template</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Items Count</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $template)
                                <tr>
                                    <td><a href="{{ url('route-templates/'.$template->id) }}" class="text-body fw-bold">#{{ $template->id }}</a> </td>
                                    <td>{{ $template->name }}</td>
                                    <td>{{ $template->items_count }}</td>
                                    <td>{{ $template->created_at }}</td>
                                    <td>
                                        <ul class="list-inline mb-0">
                                            <li class="list-inline-item">
                                                <a href="{{ url('route-templates/'.$template->id) }}" class="btn btn-primary btn-sm waves-effect waves-light" title="Edit">
                                                    <i class="uil uil-pen font-size-18"></i> Edit
                                                </a>
                                            </li>
                                            <li class="list-inline-item">
                                                <a href="javascript:void(0);" onclick="deleteTemplate({{ $template->id }})" class="btn btn-danger btn-sm waves-effect waves-light" title="Delete">
                                                    <i class="uil uil-trash-alt font-size-18"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                        <form id="delete-form-{{ $template->id }}" action="{{ url('route-templates/'.$template->id) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        {{ $templates->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="createTemplateModal" tabindex="-1" role="dialog" aria-labelledby="createTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createTemplateModalLabel">Create New Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('route-templates') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Template Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function deleteTemplate(id) {
        if(confirm('Are you sure you want to delete this template?')) {
            document.getElementById('delete-form-'+id).submit();
        }
    }
</script>

@endsection
