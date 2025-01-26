<?php
$repositoryClass = \App\Repositories\GameSettingsRepository::class;
$languagePackId = $languagePack ? $languagePack->id : '';
?>
@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Game Settings</h1>

	<x-edit-settings 
		:language-pack-id=$languagePackId
		:settings=$settings
		:repository-class="$repositoryClass"
		:show-next=true
		form-path="game_settings"
		next-path="export"
		back-path="resources"
	/>
	
	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

@endsection
