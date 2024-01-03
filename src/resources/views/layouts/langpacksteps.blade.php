<?php 
$langInfo = 'Lang Info';
if(isset($languagePack) && $languagePack->langInfo->count() > 0 && end($completedSteps) !== 'lang_info') {
  $langInfo = "<a href=\"/languagepack/edit/$languagePack->id\">Lang Info</a>";
}

$tiles = 'Tiles';
if(isset($languagePack) && $languagePack->tiles->count() > 0 && end($completedSteps) !== 'tiles') {
  $tiles = "<a href=\"/languagepack/tiles/$languagePack->id\">Tiles</a>";
}

$wordlist = 'Wordlist';
if(isset($languagePack) && $languagePack->words->count() > 0 && end($completedSteps) !== 'wordlist') {
  $wordlist = "<a href=\"/languagepack/wordlist/$languagePack->id\">Wordlist</a>";
}

$export = 'Export';
if(isset($languagePack) && $languagePack->words->count() > 0 && end($completedSteps) !== 'export') {
  $export = "<a href=\"/languagepack/export/$languagePack->id\">Export</a>";
}
?>
<ul class="steps steps-vertical sm:steps-horizontal w-full">
  <li class="step {{ in_array('lang_info', $completedSteps) ? 'step-primary' : '' }}">{!! $langInfo !!}</li>  
  <li class="step  {{ in_array('tiles', $completedSteps) ? 'step-primary' : '' }}">{!! $tiles !!}</li>
  <li class="step {{ in_array('wordlist', $completedSteps) ? 'step-primary' : '' }}">{!! $wordlist !!}</li>
  <li class="step {{ in_array('export', $completedSteps) ? 'step-primary' : '' }}">{!! $export !!}</li>
</ul>