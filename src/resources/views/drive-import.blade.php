@extends('layouts.app')

@section('content')
<div class="prose">
    <h1>{{ __('Import Language Pack from Google Drive') }}</h1>
    <div class="mt-5">
        {{ __('This is for importing all data including the media for creating a language pack. At the very least you will need to have a Google sheet in the root folder.') }}
    </div>
    <div class="mt-5">
        <a href="#" id="authorize_button" class="btn-primary cursor-pointer inline-flex h-auto min-h-12 items-center justify-center rounded px-3 py-2 text-center text-sm leading-tight no-underline text-white font-normal" onclick="connectGoogleDrive(); return false;">{{ __('Select Google Drive Folder') }}</a>
    </div>
    <div class="mt-5" id="result" style="display: none;">
        <div><span class="font-bold">{{ __('Selected folder:') }}</span> <span id="folderName"></span></div>
        <div class="mt-5 text-blue-700" id="selectionSuccess" style="display: none;">
            {{ __('Import in progress. You will find the imported language pack listed on the dashboard. You may close this page now.') }}
        </div>
        <div class="mt-5 text-red-700" id="selectionError" style="display: none;"></div>
        <div class="mt-5">
            <a href="/dashboard">{{ __('Back to Dashboard') }}</a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('partials.drive-folder-browser')
<script>
  const accessToken = @json($accessToken ?? '');
  const refreshToken = @json($refreshToken ?? '');
  const userId = @json($userId ?? 0);

  async function connectGoogleDrive() {
    const errorElement = document.getElementById('selectionError');
    errorElement.style.display = 'none';

    try {
      const folder = await selectDriveFolder({
        title: @json(__('Select a folder to import')),
        onUnauthorized: () => { window.location.href = @json(route('drive.import')); }
      });
      if (!folder) return;

      document.getElementById('result').style.display = 'block';
      document.getElementById('folderName').innerText = folder.name;
      const response = await fetch('/api/drive/dispatchimport', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId, token: accessToken, refreshToken, folderId: folder.id })
      });
      const data = await response.json();
      if (response.status === 401) {
        window.location.href = @json(route('drive.import'));
        return;
      }
      if (!response.ok) throw new Error(data.message || @json(__('Could not start the import.')));

      document.getElementById('selectionSuccess').style.display = 'block';
      document.getElementById('authorize_button').style.display = 'none';
    } catch (error) {
      errorElement.textContent = error.message;
      errorElement.style.display = 'block';
    }
  }
</script>
@endsection
