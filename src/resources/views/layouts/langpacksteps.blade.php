<?php 
$sections = [
  'lang_info' => ['label' => 'Lang Info', 'attribute' => 'langInfo', 'route' => 'edit'],
  'tiles' => ['label' => 'Tiles', 'attribute' => 'tiles', 'route' => 'tiles'],
  'wordlist' => ['label' => 'Wordlist', 'attribute' => 'words', 'route' => 'wordlist'],
  'keyboard' => ['label' => 'Keyboard', 'attribute' => 'keys', 'route' => 'keyboard'],
  'syllables' => ['label' => 'Syllables', 'attribute' => 'keys', 'route' => 'syllables'],
  'game_settings' => ['label' => 'Settings', 'attribute' => 'keys', 'route' => 'game_settings'],
  'export' => ['label' => 'Export', 'attribute' => 'keys', 'route' => 'export'],
];

$links = [];
if (isset($languagePack)) {
  foreach ($sections as $key => $details) {
      $label = $details['label'];
      $attribute = $details['attribute'];
      $route = $details['route'];

      $links[$key] = $label; 
      if ($languagePack->$attribute->count() > 0 && end($completedSteps) !== $key) {
          $links[$key] = "<a href=\"#\" onClick='autoSavePage(\"/languagepack/$route/$languagePack->id\");'>$label</a>";
      }
  }
}
?>
<ul class="steps steps-vertical sm:steps-horizontal w-full">
<?php foreach ($sections as $key => $details): ?>
  <li class="step {{ in_array($key, $completedSteps) ? 'step-primary' : '' }}">{!! $links[$key] !!}</li>
<?php endforeach; ?>
</ul>

<script>
  
  document.addEventListener('DOMContentLoaded', function() {  
    var errors = document.querySelector('.alert-error');

    if(localStorage.getItem('redirectUrl') && !errors) {
      let url = localStorage.getItem('redirectUrl');
      localStorage.removeItem('redirectUrl');

      window.location.href = url;
    }
  });

  function autoSavePage(url) {
    var saveButton = document.getElementById('saveButton');
    if(saveButton) {
      saveButton.click();
      localStorage.setItem('redirectUrl', url);
    } else {
      window.location.href = url;
    }    
  }

  function handleSaveReset() {
    localStorage.removeItem('redirectUrl');    
  }

</script>