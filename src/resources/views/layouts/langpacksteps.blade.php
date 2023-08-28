<ul class="steps steps-vertical sm:steps-horizontal w-full">
<li class="step {{ in_array('lang_info', $completedSteps) ? 'step-primary' : '' }}">Lang info</li>
  <li class="step  {{ in_array('tiles', $completedSteps) ? 'step-primary' : '' }}">Tiles</li>
  <li class="step {{ in_array('wordlist', $completedSteps) ? 'step-primary' : '' }}">Wordlist</li>
  <li class="step {{ in_array('export', $completedSteps) ? 'step-primary' : '' }}">Export</li>
</ul>