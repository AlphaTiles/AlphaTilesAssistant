@extends('layouts.app')

@section('content')

<main class="sm:container sm:mx-auto sm:max-w-lg sm:mt-10">
    <div class="flex">
        <div class="w-full">
            <section class="flex flex-col break-words bg-white sm:border-1 sm:rounded-md sm:shadow-sm sm:shadow-lg">

                <header class="font-semibold bg-gray-200 text-gray-700 py-5 px-6 sm:py-6 sm:px-8 sm:rounded-t-md">
                    {{ __('Delete Account') }}
                </header>

                <form class="w-full px-6 space-y-6 sm:px-10 sm:space-y-8" method="POST"
                    action="{{ route('profile.delete') }}">
                    @csrf
                    <h3 class="text-lg mb-3">Are you sure that you want to delete your account?
                        <br>All the data related to your account will be deleted.</h3>
                   
                    <div>Please let us know why you are deleting your account, so that the product can be improved:</div>
                    <textarea name="message" rows=3 cols=40></textarea>
                    <br>
                    <div class="flex flex-wrap">
                        <button type="submit"
                            class="w-full btn btn-warning">
                            {{ __('Confirm Deletion') }}
                        </button>
                    </div>
                    <br>
                </form>

            </section>
        </div>
    </div>
</main>

@endsection