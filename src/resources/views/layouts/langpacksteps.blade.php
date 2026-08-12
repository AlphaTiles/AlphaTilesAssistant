<?php 
$sections = [
  'lang_info' => ['label' => __('Lang Info'), 'attribute' => 'langInfo', 'route' => 'edit'],
  'tiles' => ['label' => __('Tiles'), 'attribute' => 'tiles', 'route' => 'tiles'],
  'wordlist' => ['label' => __('Wordlist'), 'attribute' => 'words', 'route' => 'wordlist'],
  'keyboard' => ['label' => __('Keyboard'), 'attribute' => 'keys', 'route' => 'keyboard'],
  'syllables' => ['label' => __('Syllables'), 'attribute' => 'syllables', 'route' => 'syllables'],
  'resources' => ['label' => __('Resources'), 'attribute' => 'resources', 'route' => 'resources'],
  'game_settings' => ['label' => __('Settings'), 'attribute' => 'gameSettings', 'route' => 'game_settings'],
  'games' => ['label' => __('Games'), 'attribute' => 'games', 'route' => 'games', 'games'],
  'export' => ['label' => __('Export'), 'attribute' => 'keys', 'route' => 'export'],
];

$links = [];
foreach ($sections as $key => $details) {
    $label = $details['label'];
    $attribute = $details['attribute'];
    $route = $details['route'];

    $links[$key] = $label; 
    if (isset($languagePack) && end($completedSteps) !== $key) {
        $links[$key] = "<a href=\"#\" onClick='autoSavePage(\"/languagepack/$route/$languagePack->id\");'>$label</a>";
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