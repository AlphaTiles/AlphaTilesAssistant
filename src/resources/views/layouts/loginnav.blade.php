@guest
    @if (Route::has('login'))
        <li>
            <a href="{{ route('login') }}" class="no-underline">{{ __('Login') }}</a>
        </li>
    @endif

    @if (Route::has('register'))
        <li>
            <a href="{{ route('register') }}" class="btn btn-primary">{{ __('Sign up') }}</a>
        </li>
    @endif
@else
    <li>
            <a href="/account" class="no-underline">Account</a>
    </li>

    <li>
            <a href="{{ route('logout') }}" class="no-underline"
                onclick="event.preventDefault();
                                document.getElementById('logout-form').submit();">
                {{ __('Logout') }}
            </a>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
    </li>
@endguest