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
				<a href="#" id="authorize_button" class="btn-primary cursor-pointer inline-flex h-auto min-h-12 items-center justify-center rounded px-3 py-2 text-center text-sm leading-tight no-underline text-white font-normal" onclick="connectGoogleDrive()">{{ __('Select Google Drive Folder') }}</a>
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

  const SCOPES = 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/spreadsheets';

  const CLIENT_ID = '<?php echo env('GOOGLE_CLIENT_ID'); ?>';
  const API_KEY = '<?php echo env('GOOGLE_DRIVE_API_KEY'); ?>';
  const APP_ID = 'alpha-tiles-assistant';

  let tokenClient;
  let accessToken = '<?php echo $accessToken ?? ''; ?>';
  let refreshToken = '<?php echo $refreshToken ?? ''; ?>';
  let userId = <?php echo $userId ?? 0; ?>;
  let languagePackId = <?php echo $languagePack->id; ?>;
  let pickerInited = false;
  let gisInited = false;
  let pollingInterval = null;

  document.getElementById('authorize_button').style.visibility = 'hidden';

  function gapiLoaded() {
    gapi.load('client:picker', initializePicker);
  }

  async function initializePicker() {
    await gapi.client.load('https://www.googleapis.com/discovery/v1/apis/drive/v3/rest');
    pickerInited = true;
    maybeEnableButtons();
  }

  function gisLoaded() {
    tokenClient = google.accounts.oauth2.initTokenClient({
      client_id: CLIENT_ID,
      scope: SCOPES,
      callback: '',
    });
    gisInited = true;
    maybeEnableButtons();
  }

  function maybeEnableButtons() {
    if (pickerInited && gisInited) {
      document.getElementById('authorize_button').style.visibility = 'visible';
    }
  }

  function connectGoogleDrive() {
    tokenClient.callback = async (response) => {
      if (response.error !== undefined) {
        throw (response);
      }
      accessToken = response.access_token;
      await createPicker();
    };

    if (!accessToken) {
      tokenClient.requestAccessToken({prompt: 'consent'});
    } else {
      tokenClient.requestAccessToken({prompt: ''});
    }
  }

  function createPicker() {
    const myDriveFoldersView = new google.picker.DocsView(google.picker.ViewId.FOLDERS)
       .setParent('root')
       .setOwnedByMe(true)
       .setIncludeFolders(true)       
       .setSelectFolderEnabled(true)
       .setMimeTypes('application/vnd.google-apps.folder')
       .setLabel('My folders');

    const sharedFoldersView = new google.picker.DocsView(google.picker.ViewId.FOLDERS)
       .setOwnedByMe(false)
       .setIncludeFolders(true)       
       .setSelectFolderEnabled(true)
       .setMimeTypes('application/vnd.google-apps.folder')
       .setLabel('Shared Folders');
        
    const picker = new google.picker.PickerBuilder()
        .setDeveloperKey(API_KEY)
        .setAppId(APP_ID)
        .setOAuthToken(accessToken)
        .setTitle("{{ __('Select a folder for export') }}") 
        .addView(sharedFoldersView)
        .addView(myDriveFoldersView)
        .setCallback(pickerCallback)
        .build();
    picker.setVisible(true);
  }

  async function pickerCallback(data) {
    if (data.action === google.picker.Action.PICKED) {
      const parentFolder = data.docs[0];
      const result = await Swal.fire({
        title: "{{ __('Choose an export folder') }}",
        padding: '2rem',
        input: 'text',
        inputLabel: "{{ __('Optional: create a new subfolder inside') }} " + parentFolder.name,
        inputPlaceholder: "{{ __('Leave blank to use the selected folder') }}",
        showCancelButton: true,
        confirmButtonText: "{{ __('Continue') }}",
        cancelButtonText: "{{ __('Cancel') }}",
      });

      if (!result.isConfirmed) {
        return;
      }

      let destinationFolder = parentFolder;
      const newFolderName = (result.value || '').trim();
      if (newFolderName) {
        try {
          const response = await fetch('https://www.googleapis.com/drive/v3/files?supportsAllDrives=true&fields=id,name', {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${accessToken}`,
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              name: newFolderName,
              mimeType: 'application/vnd.google-apps.folder',
              parents: [parentFolder.id]
            })
          });

          if (!response.ok) {
            throw new Error(await response.text());
          }
          destinationFolder = await response.json();
        } catch (error) {
          console.error('There was a problem creating the Google Drive folder:', error);
          await Swal.fire({
            icon: 'error',
            title: "{{ __('Could not create folder') }}",
            text: error.message
          });
          return;
        }
      }

      window.document.getElementById('result').style.visibility = 'visible';
      window.document.getElementById('folderName').innerText = destinationFolder.name;

      let driveLink = window.document.getElementById('driveFolderLink');
      if (driveLink) {
        driveLink.href = `https://drive.google.com/drive/folders/${destinationFolder.id}?usp=drive_link`;
      }

      let dataToSend = {
        userId: userId,
        token: accessToken,
        refreshToken: refreshToken,
        languagePackId: languagePackId,
        folderId: destinationFolder.id
      };

      fetch('/api/drive/dispatchexport', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(dataToSend)
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        document.getElementById('authorize_button').style.display = 'none';
        document.getElementById('driveExportHelpText').style.display = 'none';
        document.getElementById('stopExportButton').classList.remove('hidden');
        window.document.getElementById('exportprogress').style.display = 'block';
        if (pollingInterval) {
          clearInterval(pollingInterval);
        }
        pollingInterval = setInterval(updateLogMessages, 2000);
        updateLogMessages();
      })
      .catch(error => {
        console.error('There was a problem starting the export:', error);
      });
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
</script>
<script async defer src="https://apis.google.com/js/api.js" onload="gapiLoaded()"></script>
<script async defer src="https://accounts.google.com/gsi/client" onload="gisLoaded()"></script>
@endsection
