<script>
  function escapeDriveBrowserHtml(value) {
    return String(value).replace(/[&<>"']/g, character => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[character]);
  }

  async function selectDriveFolder({ title, allowCreate = false, onUnauthorized }) {
    let location = 'shared-with-me';
    let driveId = null;
    let driveName = @json(__('Shared with me'));
    let path = [];
    let popup;
    const headers = {
      'X-CSRF-TOKEN': @json(csrf_token()),
      'Accept': 'application/json'
    };
    const folderUrl = @json(url('/drive/folders'));
    const sharedDrivesUrl = @json(url('/drive/shared-drives'));

    const render = () => {
      const locationSelect = popup.querySelector('#drive-browser-location');
      const sharedDriveSelect = popup.querySelector('#drive-browser-shared-drive');
      const foldersContainer = popup.querySelector('#drive-browser-folders');
      const currentLabel = popup.querySelector('#drive-browser-current');
      currentLabel.textContent = path.length ? path[path.length - 1].name : driveName;
      currentLabel.classList.toggle('hidden', path.length === 0);
      popup.querySelector('#drive-browser-up').classList.toggle('hidden', path.length === 0);
      locationSelect.value = location === 'shared-drive' ? 'shared-drives' : location;
      sharedDriveSelect.classList.toggle('hidden', location !== 'shared-drive');
      if (driveId) sharedDriveSelect.value = driveId;
      foldersContainer.innerHTML = `<p class="p-3 text-sm">${@json(__('Loading folders...'))}</p>`;

      const params = new URLSearchParams({ location });
      if (path.length) params.set('parent_id', path[path.length - 1].id);
      if (driveId) params.set('drive_id', driveId);
      fetch(`${folderUrl}?${params}`, { headers })
        .then(async response => {
          const data = await response.json();
          if (response.status === 401) {
            onUnauthorized();
            throw new Error(data.message || @json(__('Reconnect Google Drive to continue.')));
          }
          if (!response.ok) throw new Error(data.message || @json(__('Could not load Google Drive folders.')));
          return data;
        })
        .then(data => {
          if (!data.folders.length) {
            foldersContainer.innerHTML = `<p class="p-3 text-sm text-gray-600">${@json(__('No folders found.'))}</p>`;
            return;
          }
          foldersContainer.innerHTML = data.folders.map(folder => `
            <div class="flex items-center justify-between gap-3 border-b p-2">
              <button type="button" class="min-w-0 flex-1 truncate text-left" data-open-folder="${escapeDriveBrowserHtml(folder.id)}" data-folder-name="${escapeDriveBrowserHtml(folder.name)}">${escapeDriveBrowserHtml(folder.name)}</button>
              <button type="button" class="btn-sm btn-secondary" data-select-folder="${escapeDriveBrowserHtml(folder.id)}" data-folder-name="${escapeDriveBrowserHtml(folder.name)}">${@json(__('Select'))}</button>
            </div>`).join('');
        })
        .catch(error => {
          foldersContainer.innerHTML = `<p class="p-3 text-sm text-red-700">${escapeDriveBrowserHtml(error.message)}</p>`;
        });
    };

    let selected = await new Promise(resolve => {
      let settled = false;
      const finish = folder => {
        if (settled) return;
        settled = true;
        resolve(folder);
        Swal.close();
      };

      Swal.fire({
        title,
        padding: '1.5rem',
        width: '42rem',
        html: `
          <div class="text-left">
            <label class="mb-1 block text-sm font-semibold" for="drive-browser-location">${@json(__('Location'))}</label>
            <select id="drive-browser-location" class="mb-3 w-full rounded border p-2">
              <option value="my-drive">${@json(__('My Drive'))}</option>
              <option value="shared-with-me">${@json(__('Shared with me'))}</option>
              <option value="shared-drives">${@json(__('Shared drives'))}</option>
            </select>
            <select id="drive-browser-shared-drive" class="mb-3 hidden w-full rounded border p-2"></select>
            <div class="mb-2 flex items-center gap-2 border-b pb-2">
              <button id="drive-browser-up" type="button" class="btn-sm btn-secondary hidden">${@json(__('Back'))}</button>
              <span id="drive-browser-current" class="hidden font-semibold"></span>
            </div>
            <div id="drive-browser-folders" class="max-h-72 overflow-y-auto rounded border"></div>
            <p class="mt-2 text-sm text-gray-600">${@json(__('Click a folder name to browse inside it, or select it to continue.'))}</p>
          </div>`,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: @json(__('Cancel')),
        didOpen: element => {
          popup = element;
          const locationSelect = popup.querySelector('#drive-browser-location');
          const sharedDriveSelect = popup.querySelector('#drive-browser-shared-drive');
          popup.addEventListener('click', event => {
            const selectButton = event.target.closest('[data-select-folder]');
            const openButton = event.target.closest('[data-open-folder]');
            if (selectButton) {
              finish({ id: selectButton.dataset.selectFolder, name: selectButton.dataset.folderName });
            } else if (openButton) {
              path.push({ id: openButton.dataset.openFolder, name: openButton.dataset.folderName });
              render();
            } else if (event.target.closest('#drive-browser-up') && path.length) {
              path.pop();
              render();
            }
          });
          locationSelect.addEventListener('change', async () => {
            path = [];
            driveId = null;
            if (locationSelect.value === 'shared-drives') {
              try {
                const response = await fetch(sharedDrivesUrl, { headers });
                const data = await response.json();
                if (response.status === 401) {
                  onUnauthorized();
                  return;
                }
                if (!response.ok) throw new Error(data.message || @json(__('Could not load Google Drive folders.')));
                if (!data.drives.length) throw new Error(@json(__('No shared drives found.')));
                sharedDriveSelect.innerHTML = data.drives.map(drive => `<option value="${escapeDriveBrowserHtml(drive.id)}">${escapeDriveBrowserHtml(drive.name)}</option>`).join('');
                location = 'shared-drive';
                driveId = data.drives[0].id;
                driveName = data.drives[0].name;
                render();
              } catch (error) {
                popup.querySelector('#drive-browser-folders').innerHTML = `<p class="p-3 text-sm text-red-700">${escapeDriveBrowserHtml(error.message)}</p>`;
              }
              return;
            }
            location = locationSelect.value;
            driveName = location === 'my-drive' ? @json(__('My Drive')) : @json(__('Shared with me'));
            render();
          });
          sharedDriveSelect.addEventListener('change', () => {
            const option = sharedDriveSelect.selectedOptions[0];
            driveId = option.value;
            driveName = option.textContent;
            path = [];
            render();
          });
          render();
        }
      }).then(() => {
        if (!settled) resolve(null);
      });
    });

    if (!selected || !allowCreate) return selected;

    const createResult = await Swal.fire({
      title: @json(__('Choose a folder')),
      padding: '2rem',
      input: 'text',
      inputLabel: `${@json(__('Optional: create a new subfolder inside'))} ${selected.name}`,
      inputPlaceholder: @json(__('Leave blank to use the selected folder')),
      showCancelButton: true,
      confirmButtonText: @json(__('Continue')),
      cancelButtonText: @json(__('Cancel')),
    });
    if (!createResult.isConfirmed) return null;

    const name = (createResult.value || '').trim();
    if (!name) return selected;
    const response = await fetch(folderUrl, {
      method: 'POST',
      headers: { ...headers, 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, parent_id: selected.id })
    });
    const data = await response.json();
    if (response.status === 401) {
      onUnauthorized();
      return null;
    }
    if (!response.ok) throw new Error(data.message || @json(__('Could not create folder in Google Drive.')));
    return data.folder;
  }
</script>
