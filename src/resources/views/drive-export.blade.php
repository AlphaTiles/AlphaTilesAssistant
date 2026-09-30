@extends('layouts.app')

@section('content')
<div class="prose">
    <h1>{{ __('Export Language Pack to Google Drive') }}</h1>
    <div class="mt-5">
        {{ __('Select the Google Drive folder where you want to export your language pack data.') }}
    </div>
    <div class="mt-5">
        <a href="#" id="authorize_button" class="btn-primary cursor-pointer inline-flex h-auto min-h-12 items-center justify-center rounded px-3 py-2 text-center text-sm leading-tight no-underline text-white font-normal" onclick="connectGoogleDrive()">{{ __('Select Google Drive Folder') }}</a>
    </div>

    <div class="mt-5" id="result" style="visibility: hidden;">
        <div><span class="font-bold">{{ __('Selected folder:') }}</span> <span id="folderName"></span></div>
    </div>

    <div class="mt-5 mb-5" id="exportprogress" style="display: none;">
        {{ __('The export is in progress. You will find your exported files in your Google Drive shortly.') }}
        <br>
        <a href="#" id="driveFolderLink" target="_blank">{{ __('Go to Google Drive Folder') }}</a>

        <h3 class="mt-5">{{ __('Export status:') }} <span id="exportStatus">{{ __('Loading...') }}</span></h3>
        <textarea id="logMessages" class="w-full" rows="10">{{ __('Loading...') }}</textarea>
    </div>

    <div class="mt-5">
        <a href="/dashboard">{{ __('Back to Dashboard') }}</a>
    </div>
</div>
@endsection

@section('scripts')
<script>
  const SCOPES = 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/spreadsheets';

  const CLIENT_ID = '<?php echo env('GOOGLE_CLIENT_ID'); ?>';
  const API_KEY = '<?php echo env('GOOGLE_DRIVE_API_KEY'); ?>';
  const APP_ID = 'alpha-tiles-assistant';

  let tokenClient;
  let accessToken = '<?php echo $accessToken ?? ''; ?>';
  let refreshToken = '<?php echo $refreshToken ?? ''; ?>';
  let userId = <?php echo $userId ?? 0; ?>;
  let languagePackId = <?php echo $languagepack->id; ?>;
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
      await createPicker();
    };

    if (!accessToken) {
      tokenClient.requestAccessToken({prompt: 'consent'});
    } else {
      tokenClient.requestAccessToken({prompt: ''});
    }
  }

  function createPicker() {
    // My Drive folders view (default view)
    const myDriveFoldersView = new google.picker.DocsView(google.picker.ViewId.FOLDERS)
       .setParent('root')
       .setOwnedByMe(true)
       .setIncludeFolders(true)       
       .setSelectFolderEnabled(true)
       .setMimeTypes('application/vnd.google-apps.folder')
       .setLabel('My folders');

    // Shared folders view
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
        .addView(new google.picker.DocsUploadView())
        .setCallback(pickerCallback)
        .build();
    picker.setVisible(true);
  }

  async function pickerCallback(data) {
    if (data.action === google.picker.Action.PICKED) {
      let folder = data.docs[0];
      let folderId = folder.id;
      window.document.getElementById('result').style.visibility = 'visible';
      window.document.getElementById('folderName').innerText = folder.name;

      let driveLink = window.document.getElementById('driveFolderLink');
      if (driveLink) {
        driveLink.href = `https://drive.google.com/drive/folders/${folderId}?usp=drive_link`;
      }

      let dataToSend = {
        userId: userId,
        token: accessToken,
        refreshToken: refreshToken,
        languagePackId: languagePackId,
        folderId: folderId
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

                if (data.status === 'success' || data.status === 'failed') {
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
</script>
<script async defer src="https://apis.google.com/js/api.js" onload="gapiLoaded()"></script>
<script async defer src="https://accounts.google.com/gsi/client" onload="gisLoaded()"></script>
@endsection
