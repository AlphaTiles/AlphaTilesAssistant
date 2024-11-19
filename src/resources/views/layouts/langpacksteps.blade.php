<?php 
$langInfo = 'Lang Info';
if(isset($languagePack) && $languagePack->langInfo->count() > 0 && end($completedSteps) !== 'lang_info') {
  $langInfo = "<a href=\"#\" onClick='autoSavePage(\"/languagepack/edit/$languagePack->id\");'>Lang Info</a>";
}

$tiles = 'Tiles';
if(isset($languagePack) && $languagePack->tiles->count() > 0 && end($completedSteps) !== 'tiles') {
  $tiles = "<a href=\"#\" onClick='autoSavePage(\"/languagepack/tiles/$languagePack->id\");'>Tiles</a>";
}

$wordlist = 'Wordlist';
if(isset($languagePack) && $languagePack->words->count() > 0 && end($completedSteps) !== 'wordlist') {
  $wordlist = "<a href=\"#\" onClick='autoSavePage(\"/languagepack/wordlist/$languagePack->id\");'>Wordlist</a>";
}

$keyboard = 'Keyboard';
if(isset($languagePack) && $languagePack->keys->count() > 0 && end($completedSteps) !== 'keyboard') {
  $keyboard = "<a href=\"#\" onClick='autoSavePage(\"/languagepack/keyboard/$languagePack->id\");'>Keyboard</a>";
}

$gameSettings = 'Settings';
if(isset($languagePack) && $languagePack->keys->count() > 0 && end($completedSteps) !== 'game_settings') {
  $gameSettings = "<a href=\"#\" onClick='autoSavePage(\"/languagepack/game_settings/$languagePack->id\");'>Settings</a>";
}

$export = 'Export';
if(isset($languagePack) && $languagePack->keys->count() > 0 && end($completedSteps) !== 'export') {
  $export = "<a href=\"#\" onClick='autoSavePage(\"/languagepack/export/$languagePack->id\");'>Export</a>";
}
?>
<ul class="steps steps-vertical sm:steps-horizontal w-full">
  <li class="step {{ in_array('lang_info', $completedSteps) ? 'step-primary' : '' }}">{!! $langInfo !!}</li>  
  <li class="step  {{ in_array('tiles', $completedSteps) ? 'step-primary' : '' }}">{!! $tiles !!}</li>
  <li class="step {{ in_array('wordlist', $completedSteps) ? 'step-primary' : '' }}">{!! $wordlist !!}</li>
  <li class="step {{ in_array('keyboard', $completedSteps) ? 'step-primary' : '' }}">{!! $keyboard !!}</li>
  <li class="step {{ in_array('game_settings', $completedSteps) ? 'step-primary' : '' }}">{!! $gameSettings !!}</li>
  <li class="step {{ in_array('export', $completedSteps) ? 'step-primary' : '' }}">{!! $export !!}</li>
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