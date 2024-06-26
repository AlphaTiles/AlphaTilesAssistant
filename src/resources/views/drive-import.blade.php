@extends('layouts.app')

@section('content')
<div class="prose">
    <h1>Import Language Pack from Google Drive</h1>
    <div class="mt-5">
          This is for importing all data including the media for creating a language pack. At the very least you will need to have a Google sheet in the root folder.
    </div>
    <div class="mt-5">
          <a href="#" id="authorize_button" class="btn btn-primary w-40 mt-1 pt-0.5 text-white font-normal no-underline" onclick="connectGoogleDrive()">Select Google Drive Folder</a>
    </div>
    <div class="mt-5" id="result" style="visibility: hidden;">
      <div><span class="font-bold">Selected folder:</span> <span id="folderName"></span></div>
      <div class="mt-5 text-blue-700" id="selectionSuccess" style="visibility: hidden;">        
        Import in progress. You will find the imported language pack listed on the dashboard. You may close this page now.
      </div>
      <div class="mt-5 text-red-700" id="selectionError" style="visibility: hidden;">Error: No Google Sheet or XLSX file found in the selected folder.</div>
      <div class="mt-5">
        <a href="/dashboard">Back to Dashboard</a>
      </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
 // Authorization scopes required by the API; multiple scopes can be
  // included, separated by spaces.
  const SCOPES = 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/spreadsheets';

  // TODO(developer): Set to client ID and API key from the Developer Console
  const CLIENT_ID = '<?php echo env('GOOGLE_CLIENT_ID'); ?>'
  const API_KEY = '<?php echo env('GOOGLE_DRIVE_API_KEY'); ?>'

  // TODO(developer): Replace with your own project number from console.developers.google.com.
  const APP_ID = 'alpha-tiles-assistant';

  let tokenClient;
  let accessToken = '<?php echo $accessToken; ?>';
  let userId = <?php echo $userId; ?>;
  let pickerInited = false;
  let gisInited = false;


  document.getElementById('authorize_button').style.visibility = 'hidden';

  /**
   * Callback after api.js is loaded.
   */
  function gapiLoaded() {
    gapi.load('client:picker', initializePicker);
  }

  /**
   * Callback after the API client is loaded. Loads the
   * discovery doc to initialize the API.
   */
  async function initializePicker() {
    await gapi.client.load('https://www.googleapis.com/discovery/v1/apis/drive/v3/rest');
    pickerInited = true;
    maybeEnableButtons();
  }

  /**
   * Callback after Google Identity Services are loaded.
   */
  function gisLoaded() {
    tokenClient = google.accounts.oauth2.initTokenClient({
      client_id: CLIENT_ID,
      scope: SCOPES,
      callback: '', // defined later
    });
    gisInited = true;
    maybeEnableButtons();
  }

  /**
   * Enables user interaction after all libraries are loaded.
   */
  function maybeEnableButtons() {
    if (pickerInited && gisInited) {
      document.getElementById('authorize_button').style.visibility = 'visible';
    }
  }

  /**
   *  Sign in the user upon button click.
   */
  function connectGoogleDrive() {
    tokenClient.callback = async (response) => {
      if (response.error !== undefined) {
        throw (response);
      }
      await createPicker();
    };

    if (accessToken === null) {
      // Prompt the user to select a Google Account and ask for consent to share their data
      // when establishing a new session.
      tokenClient.requestAccessToken({prompt: 'consent'});
    } else {
      // Skip display of account chooser and consent dialog for an existing session.
      tokenClient.requestAccessToken({prompt: ''});
    }
  }

  /**
   *  Create and render a Picker object for searching images.
   */
  function createPicker() {
    const docsView = new google.picker.DocsView(google.picker.ViewId.DOCS)
       .setParent('root')
       .setIncludeFolders(true)       
       .setSelectFolderEnabled(true)
       .setMimeTypes('application/vnd.google-apps.folder');
        
    const picker = new google.picker.PickerBuilder()
        .enableFeature(google.picker.Feature.NAV_HIDDEN)
        .setDeveloperKey(API_KEY)
        .setAppId(APP_ID)
        .setOAuthToken(accessToken)
        .setTitle("Select a folder") 
        .addView(docsView)
        .addView(new google.picker.DocsUploadView())
        .setCallback(pickerCallback)
        .build();
    picker.setVisible(true);
  }

  /**
   * Displays the file details of the user's selection.
   * @param {object} data - Containers the user selection from the picker
   */
  async function pickerCallback(data) {
    if (data.action === google.picker.Action.PICKED) {
      // let text = `Picker response: \n${JSON.stringify(data, null, 2)}\n`;
      let folder = data.docs[0];
      folderId = folder.id;
      window.document.getElementById('result').style.visibility = 'visible';
      window.document.getElementById('folderName').innerText = folder.name;
      let folderHasSheet = await checkFolderHasSheet();
      if(folderHasSheet) {
        let dataToSend = {
          userId: userId,
          token: accessToken,
          folderId: folderId
        };
        fetch('/api/drive/dispatchimport', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(dataToSend)
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json(); // Parse the response body as JSON
        })
        .then(data => {
          // Handle the response data
          console.log(data);
        })
        .catch(error => {
          // Handle any errors
          console.error('There was a problem with the fetch operation:', error);
        });        
      }

    }
  }

  async function checkFolderHasSheet() {
      return gapi.client.drive.files.list({
        'q': "'" + folderId + "' in parents and (mimeType='application/vnd.google-apps.spreadsheet' " +
           " or mimeType='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')",
        'fields': 'files(name, mimeType)'
      }).then(function(response) {
        var files = response.result.files;
        if (files.length === 0) {
          window.document.getElementById('selectionSuccess').style.visibility = 'hidden';
          window.document.getElementById('selectionError').style.visibility = 'visible';
          return false;
        } 

        window.document.getElementById('selectionError').style.visibility = 'hidden';
        window.document.getElementById('selectionSuccess').style.visibility = 'visible';
        return true;
      });
    }  
</script>
<script async defer src="https://apis.google.com/js/api.js" onload="gapiLoaded()"></script>
<script async defer src="https://accounts.google.com/gsi/client" onload="gisLoaded()"></script>

@endsection