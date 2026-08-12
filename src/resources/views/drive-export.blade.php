@extends('layouts.app')

@section('content')
<div class="prose">
    <h1>{{ __('Export Language Pack to Google Drive') }}</h1>
    <div class="mt-5 mb-5" id="exportprogress">
          {{ __('The export is in progress. You will find your exported files in your Google Drive shortly.') }}
          <br>
          <a href="https://drive.google.com/drive/folders/{{$driveRootFolderId}}?usp=drive_link" target="_blank">{{ __('Go to Google Drive Folder') }}</a>
    </div>

    <h3>{{ __('Export status:') }} <span id="exportStatus">{{ __('Loading...') }}</span></h3>
    <textarea id="logMessages" class="w-full" rows="10">
        {{ __('Loading...') }}
    </textarea>
    <div class="mt-5">
        <a href="/dashboard">{{ __('Back to Dashboard') }}</a>
    </div>
</div>

<script>
let pollingInterval;

function updateLogMessages() {
    fetch(`/api/export-logs?languagepackid={{ $languagepack->id }}`)
        .then(response => response.json())
        .then(data => {
            const logDiv = document.getElementById('logMessages');
            const exportStatus = document.getElementById('exportStatus');            
            if (data.messages && data.messages.length > 0) {
                messages = data.messages;
                if(data.status === 'failed') {
                    messages += "\n{{ __('Export failed.') }}";
                }
                logDiv.value = messages;
                // Scroll to bottom of textarea
                logDiv.scrollTop = logDiv.scrollHeight;
                exportStatus.innerText = data.status;

                // Check status and stop polling if completed
                if (data.status === 'success' || data.status === 'failed') {
                    clearInterval(pollingInterval);
                    console.log('Export completed with status:', data.status);
                }
            } else {
                logDiv.value = "{{ __('No messages yet...') }}";
            }
        });
}

// Start polling every 2 seconds
pollingInterval = setInterval(updateLogMessages, 2000);
// Initial update
updateLogMessages();
</script>
@endsection