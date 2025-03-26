@extends('layouts.app')

@section('content')
<div class="prose">
    <h1>Export Language Pack to Google Drive</h1>
    <div class="mt-5" id=exportprogress>
          The export is in progress. You will find your exported file in your Google Drive shortly.
          <br>
          <a href="https://drive.google.com/drive/folders/{{$driveRootFolderId}}?usp=drive_link" target="_blank">Go to Google Drive Folder</a>
    </div>
      <div class="mt-5">
        <a href="/dashboard">Back to Dashboard</a>
      </div>
    </div>
</div>
@endsection