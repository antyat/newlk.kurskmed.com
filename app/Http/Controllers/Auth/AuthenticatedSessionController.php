<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param \App\Http\Requests\Auth\LoginRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $email = trim($request->get('email'));
        if (strripos($email, '@kursksmu.net')) {
            // это сотрудник
            $usertype = 'user_ad';
        } else {
            $usertype = 'user_e-mail';
        } // if (strripos($email, '@kursksmu.net'))
        if ($usertype == 'user_e-mail') {
            // это учащийся
            $request->authenticate();
            $request->session()->regenerate();
            return redirect()->intended(RouteServiceProvider::HOME);
        } elseif ($usertype == 'user_ad') {
            $results = $this->validate($request, [
                'email' => 'required',
                'password' => 'required'
            ]);
            $user_email = $request->email;
            $user_psw = $request->password;
            try {
                $dn = 'cn=ldapadmin,dc=kursksmu,dc=net';
                $admpassword = '1q2w3e$RA';
                $ldaphost = "10.0.0.106";
                $ldapport = "389";
                // $ldap
                $ldap_con = ldap_connect($ldaphost, $ldapport) or die("Cant connect to LDAP Server");
            } catch (\Exception $exception) {
                return back()->with('error', 'Невозможно подключиться к LDAP серверу. Пожалуйста, попробуйте авторизоваться позднее или свяжитесь с администрацией портала.' . '</br>' . $exception->getMessage())->withInput();
            }
            try {
                //Включаем LDAP протокол версии 3
                ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);
                // устанавливаем ключ следовать ли автоматическим рефрерралам LDAP
                ldap_set_option($ldap_con, LDAP_OPT_REFERRALS, 0) or die('Unable to set LDAP opt referrals');

                // Пытаемся войти в LDAP при помощи введенных логина и пароля
                $bind = ldap_bind($ldap_con, $dn, $admpassword) ?? false;
            } catch (\Exception $exception) {
                return back()->with('error', 'Произошёл сбой при авторизации LDAP. Пожалуйста, попробуйте авторизоваться позднее или свяжитесь с администрацией портала.'
                    . '</br>' . $exception->getMessage())->withInput();
            }
            try {
                    //Работа с LDAPOM
                if ($bind) {
                    // фильтр по uid
                    // $uid_filter = '(uid=' . $user_login . ')';
                    // фильтр по email
                    $email_filter = '(&(objectclass=*)(mail=' . $user_email . '))';
                    $password_hash = '{SHA}' . base64_encode(sha1($user_psw, TRUE));
                    // ищем пользователя
                    $result = ldap_search($ldap_con, 'dc=kursksmu,dc=net', $email_filter) or exit('no search');
                    $searchresult = ldap_get_entries($ldap_con, $result);
                    // dd( $searchresult[0]['userpassword'][0], $password_hash);
                    // $compare = ldap_compare($ldap_con, $dn,$searchresult );
                    // dd($searchresult/*, $searchresult[0]['userpassword'][0], $password_hash*/);
                    if ($searchresult['count'] == 0) {
                        return back()->with('error', 'Вас нет в LDAP, обратитесь в 302 кабинет в стомат. корпусе');
                    }
                    // проверяем пароль в LDAP
                    if ($searchresult[0]['userpassword'][0] !== $password_hash) {
                        // если такой пароль не подходит
                        $request->authenticate();
                        return redirect()->intended(RouteServiceProvider::HOME);
                    } else {
                        // Пароль правильный авторизуем ldap пользователя
                        $ldapuser = User::where('email', $email)->first();
                        Auth::login($ldapuser);
                        return redirect(RouteServiceProvider::HOME);

                        /*Если пользователь не зарегистрировался, то он добавляется, при условии что он есть в АД, Затем снова попытка войти в систему*/
                        // if ($result->isSuccessful()) {
                        //
                        // return redirect()->route('main');
                        //
                        // } else {
                        // return back()->with('error', 'всё сломалось');
                        // }
                    } // if ($searchresult[0]['userpassword'][0] !== $password_hash)
                } //end РАБОТА С ЛДАПОМ
                else {
                    return back()->with('error', 'Неправильный логин или пароль')->withInput();
                }
            } catch (\Exception $exception) {
                return back()->with('error', 'Произошёл сбой при проверке пользователя. Пожалуйста, попробуйте авторизоваться позднее или свяжитесь с администрацией портала.'
                    . '</br>' . $exception->getMessage())->withInput();
            }
        } // elseif ($usertype == 'user_ad')
    }

    /**
     * Destroy an authenticated session.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
