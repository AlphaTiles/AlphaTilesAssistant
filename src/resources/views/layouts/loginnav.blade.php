@guest
    <a href="{{ url('login/google')}}"><span class="btn-inner--icon"><img src="/images/btn_google_signin_light_normal_web.png"></span></a>
    @if (Route::has('register'))
        <li>
            <a href="{{ route('register') }}" class="btn btn-primary">{{ __('Sign up') }}</a>
        </li>
    @endif
@else
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