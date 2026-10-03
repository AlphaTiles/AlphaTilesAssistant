@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="container">

	<div class="prose">
		<h1>{{ __('Export Language Pack') }}</h1>
	</div>

	<x-validation-errors
		:languagePack="$languagePack"
		:errors="$errors"
		:tab=null
	/>	

	<form id="zip-export-form" method="post" action="/languagepack/export/{{ $languagePack->id }}" data-warning-message="{{ $exportWarningMessage }}" data-warning-level="{{ $exportWarningLevel }}">
		@csrf

		<div class="mt-5 mb-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $languagePack->id }}" />
			<input type="submit" name="btnExport" value="{{ __('Download language pack') }}" class="btn-sm btn-primary cursor-pointer" />
		</div>		
	</form>

	<hr>

	<div class="mt-4 w-9/12">
		<div class="flex items-start" id="driveExportControls">
			<div>
				<a href="#" id="authorize_button" class="btn-primary cursor-pointer inline-flex h-auto min-h-12 items-center justify-center rounded px-3 py-2 text-center text-sm leading-tight no-underline text-white font-normal" onclick="connectGoogleDrive(); return false;">{{ __('Select Google Drive Folder') }}</a>
				<button type="button" id="stopExportButton" class="btn-sm btn-error hidden whitespace-nowrap px-4" onclick="stopExport()">{{ __('Stop export') }}</button>
			</div>
			<div class="ml-5" id="driveExportHelpText">
				{{ __('This will export all the media files into folders on Google Drive and the data into a Google sheet.') }}
				{{ __('You will be able to select your target Google Drive folder.') }} 
			</div>
		</div>

		<div class="mt-4" id="result" style="visibility: hidden;">
			<div><span class="font-bold">{{ __('Selected folder:') }}</span> <span id="folderName"></span></div>
		</div>

		<div class="mt-4 mb-5" id="exportprogress" style="display: none;">
			<div class="text-green-700 font-medium mb-2">
				<p id="exportProgressMessage">{{ __('The export is in progress. You will find your exported files in your Google Drive shortly.') }}</p>
				<br>
				<a href="#" id="driveFolderLink" target="_blank" class="text-blue-600 underline">{{ __('Go to Google Drive Folder') }}</a>
			</div>

			<h3 class="mt-3 font-semibold">{{ __('Export status:') }} <span id="exportStatus">{{ __('Loading...') }}</span></h3>
			<textarea id="logMessages" class="w-full text-sm font-mono mt-2 p-2 border rounded" rows="8">{{ __('Loading...') }}</textarea>
		</div>
	</div>

	@if ($driveExport)
		<div class="mt-6 w-9/12">
			<h2 class="text-lg font-semibold">{{ __('Last Google Drive export') }}</h2>
			<p class="mt-2">
				<a href="{{ $driveExport->drive_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 underline">{{ $driveExport->folder_name }}</a>
				<span class="ml-2 text-sm text-gray-600">{{ $driveExport->updated_at->format('Y-m-d H:i') }}</span>
				<button type="button" class="ml-2 cursor-pointer border-0 bg-transparent p-0 text-blue-600" onclick="showDriveSearchInfo()" aria-label="{{ $driveExport->is_shared ? __('Shared folder') : __('Personal folder') }}" title="{{ $driveExport->is_shared ? __('Shared folder') : __('Personal folder') }}">
					<i class="fa-solid {{ $driveExport->is_shared ? 'fa-users' : 'fa-user' }}" aria-hidden="true"></i>
				</button>
			</p>
		</div>
	@endif

	@php
		$availabilityBadgeClass = match ($appAvailability) {
			'App available' => 'bg-green-100 text-green-800',
			'Testing' => 'bg-amber-100 text-amber-800',
			default => 'bg-gray-100 text-gray-800',
		};
	@endphp
	<div class="mt-4 inline-flex max-w-full items-center gap-4 rounded border border-gray-200 bg-white p-3 shadow-sm">
		<div>
			<div class="text-xs font-medium text-gray-600">{{ __('App availability') }}</div>
			<span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-sm font-semibold {{ $availabilityBadgeClass }}">{{ __($appAvailability) }}</span>
		</div>
		<a href="/languagepack/game_settings/{{ $languagePack->id }}#setting-app_availability" class="inline-flex items-center justify-center whitespace-nowrap no-underline btn-sm btn-secondary font-normal">{{ __('Change') }}</a>
	</div>

	<div class="mt-4">
		<a href="/dashboard">{{ __('Back to Dashboard') }}</a>
	</div>
</div>

@endsection

@section('scripts')
@include('partials.drive-folder-browser')
<script>
document.addEventListener('DOMContentLoaded', function () {
	var form = document.getElementById('zip-export-form');
	if (!form) {
		return;
	}

	var warningMessage = form.dataset.warningMessage || '';
	var warningLevel = form.dataset.warningLevel || '';
	if (!warningMessage) {
		return;
	}

	var isCriticalWarning = warningLevel === 'critical';
	var confirmButtonColor = isCriticalWarning ? '#dc2626' : '#d97706';

	form.addEventListener('submit', function (event) {
		event.preventDefault();

		Swal.fire({
			title: "{{ __('Proceed with export?') }}",
			text: warningMessage,
			icon: isCriticalWarning ? 'warning' : 'info',
			showCancelButton: true,
			confirmButtonText: "{{ __('Proceed') }}",
			cancelButtonText: "{{ __('Cancel') }}",
			confirmButtonColor: confirmButtonColor,
			reverseButtons: true,
		}).then(function (result) {
			if (result.isConfirmed) {
				form.submit();
			}
		});
	});
});

  let accessToken = @json($accessToken ?? '');
  let refreshToken = @json($refreshToken ?? '');
  let userId = @json($userId ?? 0);
  let languagePackId = @json($languagePack->id);
  let pollingInterval = null;
  const hasDrivePermissions = @json((bool) $hasDrivePermissions);
  const resumeFolderBrowserKey = `resume-drive-folder-browser-${languagePackId}`;

  async function connectGoogleDrive() {
    if (!hasDrivePermissions) {
      sessionStorage.setItem(resumeFolderBrowserKey, '1');
      window.location.href = `/drive/export/${languagePackId}`;
      return;
    }

    try {
      let destinationFolder = await selectDriveFolder({
        title: @json(__('Select a folder for export')),
        allowCreate: true,
        onUnauthorized: () => {
          sessionStorage.setItem(resumeFolderBrowserKey, '1');
          window.location.href = `/drive/export/${languagePackId}`;
        }
      });
      if (!destinationFolder) return;

      document.getElementById('result').style.visibility = 'visible';
      document.getElementById('folderName').innerText = destinationFolder.name;
      const driveLink = document.getElementById('driveFolderLink');
      if (driveLink) driveLink.href = `https://drive.google.com/drive/folders/${destinationFolder.id}?usp=drive_link`;

      const response = await fetch('/api/drive/dispatchexport', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
        body: JSON.stringify({ userId, token: accessToken, refreshToken, languagePackId, folderId: destinationFolder.id })
      });
      const data = await response.json();
      if (response.status === 401) {
        sessionStorage.setItem(resumeFolderBrowserKey, '1');
        window.location.href = `/drive/export/${languagePackId}`;
        return;
      }
      if (!response.ok) throw new Error(data.message || 'Network response was not ok');

      document.getElementById('authorize_button').style.display = 'none';
      document.getElementById('driveExportHelpText').style.display = 'none';
      document.getElementById('stopExportButton').classList.remove('hidden');
      document.getElementById('exportprogress').style.display = 'block';
      if (pollingInterval) clearInterval(pollingInterval);
      pollingInterval = setInterval(updateLogMessages, 2000);
      updateLogMessages();
    } catch (error) {
      console.error('There was a problem starting the export:', error);
      Swal.fire({ icon: 'error', title: @json(__('Export could not be started')), text: error.message });
    }
  }

  function stopExport() {
    const stopButton = document.getElementById('stopExportButton');
    stopButton.disabled = true;
    fetch(`/languagepack/export/${languagePackId}/cancel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
    })
      .then(response => response.json())
      .then(data => {
        if (!data.success) {
          stopButton.disabled = false;
          return;
        }
        document.getElementById('exportStatus').innerText = data.status;
        stopButton.style.display = 'none';
        if (pollingInterval) clearInterval(pollingInterval);
        updateLogMessages();
      })
      .catch(error => {
        stopButton.disabled = false;
        console.error('There was a problem stopping the export:', error);
      });
  }

  function updateLogMessages() {
    fetch(`/api/export-logs?languagepackid=${languagePackId}`)
        .then(response => response.json())
        .then(data => {
            const logDiv = document.getElementById('logMessages');
            const exportStatus = document.getElementById('exportStatus');            
            if (data.messages && data.messages.length > 0) {
                let messages = data.messages;
                if(data.status === 'failed') {
                    messages += "\n{{ __('Export failed.') }}";
                }
                logDiv.value = messages;
                logDiv.scrollTop = logDiv.scrollHeight;
                exportStatus.innerText = data.status;

                if (data.status === 'success' || data.status === 'failed' || data.status === 'cancelled') {
                    document.getElementById('stopExportButton').style.display = 'none';
                    if (data.status === 'failed') {
                      document.getElementById('authorize_button').style.display = '';
                      document.getElementById('driveExportHelpText').style.display = '';
                      document.getElementById('exportProgressMessage').innerText = "{{ __('The export failed. Please try again.') }}";
                    } else if (data.status === 'cancelled') {
                      document.getElementById('authorize_button').style.display = '';
                      document.getElementById('driveExportHelpText').style.display = '';
                    } else if (data.status === 'success') {
                      document.getElementById('authorize_button').style.display = '';
                      document.getElementById('driveExportHelpText').style.display = '';
                      document.getElementById('exportProgressMessage').innerText = "{{ __('The export is complete. Your files are available in Google Drive.') }}";
                    }
                    if (pollingInterval) {
                      clearInterval(pollingInterval);
                    }
                    console.log('Export completed with status:', data.status);
                }
            } else {
                logDiv.value = "{{ __('No messages yet...') }}";
            }
        });
  }

  function showDriveSearchInfo() {
    Swal.fire({
      icon: 'info',
      title: @json(__('Google Drive export')),
      html: @json(__('Only exports saved in the shared Alpha Tiles folder are included in the')) + ' <a href="https://alphatilesapps.org/search.php" target="_blank" rel="noopener noreferrer">' + @json(__('web search tool')) + '</a>.',
      padding: '2rem'
    });
  }

  if (sessionStorage.getItem(resumeFolderBrowserKey) === '1') {
    sessionStorage.removeItem(resumeFolderBrowserKey);
    if (hasDrivePermissions) connectGoogleDrive();
  }
</script>
@endsection
